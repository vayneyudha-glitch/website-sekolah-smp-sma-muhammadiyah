<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentWebhookRequest;
use App\Models\PaymentWebhookLog;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentWebhookProcessor;
use Illuminate\Http\JsonResponse;

class PaymentWebhookController extends Controller
{
    public function __invoke(
        PaymentWebhookRequest $request,
        string $provider,
        PaymentGatewayManager $gateways,
        PaymentWebhookProcessor $processor,
    ): JsonResponse {
        $log = PaymentWebhookLog::query()->create([
            'provider' => $provider,
            'raw_body' => $request->getContent(),
            'processing_status' => 'diterima',
        ]);

        try {
            $gateway = $gateways->forProvider($provider);
            $valid = $gateway->verifyWebhook($request->getContent(), $request->headers->all());
        } catch (PaymentGatewayException $exception) {
            $log->update([
                'processing_status' => 'payload_tidak_valid',
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);

            return response()->json(['message' => 'Format notifikasi tidak valid.'], 400);
        }

        if (! $valid) {
            $log->update([
                'processing_status' => 'tanda_tangan_tidak_valid',
                'error_message' => 'Verifikasi tanda tangan gateway gagal.',
                'processed_at' => now(),
            ]);

            return response()->json(['message' => 'Tanda tangan notifikasi tidak valid.'], 401);
        }

        $log->update(['signature_valid' => true]);

        try {
            $notification = $gateway->parseWebhook($request->getContent());
        } catch (PaymentGatewayException $exception) {
            $log->update([
                'processing_status' => 'payload_tidak_valid',
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);

            return response()->json(['message' => 'Format notifikasi tidak valid.'], 400);
        }

        if ($notification === null) {
            $log->update(['processing_status' => 'payload_tidak_dikenali', 'processed_at' => now()]);

            return response()->json(['message' => 'Notifikasi diabaikan.']);
        }

        $result = $processor->process($log, $provider, $notification);
        $statusCode = in_array($result, ['nominal_tidak_cocok', 'pembayaran_tidak_ditemukan'], true) ? 422 : 200;

        return response()->json(['message' => $result], $statusCode);
    }
}
