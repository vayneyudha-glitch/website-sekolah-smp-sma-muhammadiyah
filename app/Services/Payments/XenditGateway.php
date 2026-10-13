<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\StudentInvoice;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;

class XenditGateway implements PaymentGateway
{
    public function isConfigured(): bool
    {
        return filled(config('payments.xendit.secret_key'))
            && filled(config('payments.xendit.webhook_token'));
    }

    public function createCheckout(StudentInvoice $invoice, Payment $payment, string $returnUrl, string $method): GatewayCheckout
    {
        $methods = match ($method) {
            'virtual_account' => ['BCA', 'BNI', 'BRI', 'MANDIRI', 'PERMATA'],
            'qris' => ['QRIS'],
            'e_wallet' => ['OVO', 'DANA', 'LINKAJA', 'SHOPEEPAY'],
        };

        try {
            $response = Http::withBasicAuth((string) config('payments.xendit.secret_key'), '')
                ->acceptJson()
                ->timeout(20)
                ->withHeaders(['Idempotency-key' => $payment->gateway_reference])
                ->post(rtrim((string) config('payments.xendit.api_url'), '/').'/v2/invoices', [
                    'external_id' => $payment->gateway_reference,
                    'amount' => $invoice->amount,
                    'currency' => config('payments.currency', 'IDR'),
                    'description' => $invoice->bill_type_name.' - '.$invoice->invoice_number,
                    'invoice_duration' => max(3600, now()->diffInSeconds($invoice->due_date->endOfDay(), false)),
                    'payment_methods' => $methods,
                    'success_redirect_url' => $returnUrl,
                    'failure_redirect_url' => $returnUrl,
                ])->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new PaymentGatewayException('Gateway pembayaran tidak dapat dihubungi. Silakan coba kembali.', previous: $exception);
        }

        $body = $response->json();
        if (
            ! is_array($body)
            || ! is_string($body['invoice_url'] ?? null)
            || ! filter_var($body['invoice_url'], FILTER_VALIDATE_URL)
            || parse_url($body['invoice_url'], PHP_URL_SCHEME) !== 'https'
        ) {
            throw new PaymentGatewayException('Gateway tidak mengembalikan tautan pembayaran yang valid.');
        }

        return new GatewayCheckout($payment->gateway_reference, $body['invoice_url'], $body['id'] ?? null);
    }

    public function verifyWebhook(string $rawBody, array $headers): bool
    {
        $token = null;
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === 'x-callback-token') {
                $token = is_array($value) ? ($value[0] ?? null) : $value;
                break;
            }
        }

        return is_string($token)
            && filled(config('payments.xendit.webhook_token'))
            && hash_equals((string) config('payments.xendit.webhook_token'), $token);
    }

    public function parseWebhook(string $rawBody): ?GatewayNotification
    {
        $payload = $this->decode($rawBody);
        $reference = $payload['external_id'] ?? null;
        $statusValue = $payload['status'] ?? null;
        $amount = $payload['paid_amount'] ?? $payload['amount'] ?? null;

        if (! is_string($reference) || ! is_string($statusValue) || ! is_numeric($amount)) {
            return null;
        }

        $status = match (strtoupper($statusValue)) {
            'PAID', 'SETTLED' => 'paid',
            'PENDING' => 'pending',
            'EXPIRED' => 'expired',
            'FAILED', 'CANCELED', 'CANCELLED' => 'cancelled',
            default => 'ignored',
        };

        return new GatewayNotification($reference, $status, (float) $amount, is_string($payload['currency'] ?? null) ? $payload['currency'] : 'IDR');
    }

    /** @return array<string, mixed> */
    private function decode(string $rawBody): array
    {
        try {
            $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PaymentGatewayException('Isi notifikasi gateway bukan JSON yang valid.', previous: $exception);
        }

        if (! is_array($payload)) {
            throw new PaymentGatewayException('Format notifikasi gateway tidak valid.');
        }

        return $payload;
    }
}
