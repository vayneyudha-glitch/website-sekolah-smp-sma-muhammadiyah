<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\StudentInvoice;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;

class MidtransGateway implements PaymentGateway
{
    public function isConfigured(): bool
    {
        return filled(config('payments.midtrans.server_key'));
    }

    public function createCheckout(StudentInvoice $invoice, Payment $payment, string $returnUrl, string $method): GatewayCheckout
    {
        $enabledPayments = match ($method) {
            'virtual_account' => ['bca_va', 'bni_va', 'bri_va', 'permata_va', 'echannel'],
            'qris' => ['qris'],
            'e_wallet' => ['gopay', 'shopeepay'],
        };

        try {
            $response = Http::withBasicAuth((string) config('payments.midtrans.server_key'), '')
                ->acceptJson()
                ->timeout(20)
                ->withHeaders(['Idempotency-Key' => $payment->gateway_reference])
                ->post(rtrim((string) config('payments.midtrans.api_url'), '/').'/snap/v1/transactions', [
                    'transaction_details' => [
                        'order_id' => $payment->gateway_reference,
                        'gross_amount' => $invoice->amount,
                    ],
                    'item_details' => [[
                        'id' => (string) $invoice->id,
                        'price' => $invoice->amount,
                        'quantity' => 1,
                        'name' => $invoice->bill_type_name,
                    ]],
                    'enabled_payments' => $enabledPayments,
                    'callbacks' => ['finish' => $returnUrl],
                ])->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new PaymentGatewayException('Gateway pembayaran tidak dapat dihubungi. Silakan coba kembali.', previous: $exception);
        }

        $body = $response->json();
        if (
            ! is_array($body)
            || ! is_string($body['redirect_url'] ?? null)
            || ! filter_var($body['redirect_url'], FILTER_VALIDATE_URL)
            || parse_url($body['redirect_url'], PHP_URL_SCHEME) !== 'https'
        ) {
            throw new PaymentGatewayException('Gateway tidak mengembalikan tautan pembayaran yang valid.');
        }

        return new GatewayCheckout($payment->gateway_reference, $body['redirect_url'], $body['token'] ?? null);
    }

    public function verifyWebhook(string $rawBody, array $headers): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $payload = $this->decode($rawBody);
        $signature = $payload['signature_key'] ?? null;
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;

        if (! is_string($signature) || ! is_string($orderId) || ! is_string($statusCode) || ! is_string($grossAmount)) {
            return false;
        }

        $expected = hash(
            'sha512',
            $orderId.$statusCode.$grossAmount.(string) config('payments.midtrans.server_key'),
        );

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(string $rawBody): ?GatewayNotification
    {
        $payload = $this->decode($rawBody);
        $reference = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;

        if (! is_string($reference) || ! is_string($transactionStatus) || ! is_numeric($grossAmount)) {
            return null;
        }

        $status = match ($transactionStatus) {
            'capture', 'settlement' => (($payload['fraud_status'] ?? 'accept') === 'accept') ? 'paid' : 'pending',
            'pending' => 'pending',
            'expire' => 'expired',
            'cancel', 'deny' => 'cancelled',
            default => 'ignored',
        };

        return new GatewayNotification($reference, $status, (float) $grossAmount, 'IDR');
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
