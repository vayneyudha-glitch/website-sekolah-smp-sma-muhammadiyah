<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentWebhookLog;
use App\Models\StudentInvoice;
use App\Services\SchoolNotifier;
use Illuminate\Support\Facades\DB;

class PaymentWebhookProcessor
{
    public function __construct(
        private readonly PaymentAuditLogger $audit,
        private readonly SchoolNotifier $notifier,
    ) {}

    public function process(PaymentWebhookLog $log, string $provider, GatewayNotification $notification): string
    {
        if ($notification->status === 'ignored') {
            $log->update(['processing_status' => 'diabaikan', 'processed_at' => now()]);

            return 'diabaikan';
        }

        if (
            $notification->currency !== null
            && strtoupper($notification->currency) !== strtoupper((string) config('payments.currency', 'IDR'))
        ) {
            $log->update([
                'processing_status' => 'nominal_tidak_cocok',
                'error_message' => 'Mata uang notifikasi berbeda dengan konfigurasi pembayaran.',
                'processed_at' => now(),
            ]);

            return 'nominal_tidak_cocok';
        }

        $payment = Payment::query()
            ->where('gateway', $provider)
            ->where('gateway_reference', $notification->reference)
            ->with('invoice')
            ->first();

        if ($payment === null) {
            $log->update([
                'processing_status' => 'pembayaran_tidak_ditemukan',
                'error_message' => 'Referensi pembayaran tidak ditemukan.',
                'processed_at' => now(),
            ]);

            return 'pembayaran_tidak_ditemukan';
        }

        if (round($notification->amount, 2) !== (float) $payment->amount) {
            $log->update([
                'processing_status' => 'nominal_tidak_cocok',
                'error_message' => 'Nominal notifikasi tidak sama dengan nominal pembayaran.',
                'processed_at' => now(),
            ]);

            return 'nominal_tidak_cocok';
        }

        return DB::transaction(function () use ($log, $payment, $notification): string {
            $invoice = StudentInvoice::query()->whereKey($payment->student_invoice_id)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status === Payment::STATUS_PAID || (
                in_array($payment->status, [Payment::STATUS_FAILED, Payment::STATUS_EXPIRED, Payment::STATUS_CANCELLED], true)
                && $notification->status !== 'paid'
            )) {
                $log->update(['processing_status' => 'duplikat', 'processed_at' => now()]);

                return 'duplikat';
            }

            if (
                $invoice->status === StudentInvoice::STATUS_PAID
                && $invoice->payments()
                    ->where('status', Payment::STATUS_PAID)
                    ->where('payments.id', '!=', $payment->id)
                    ->exists()
            ) {
                $log->update([
                    'processing_status' => 'tagihan_sudah_lunas',
                    'error_message' => 'Tagihan telah dilunasi melalui pembayaran lain.',
                    'processed_at' => now(),
                ]);

                return 'tagihan_sudah_lunas';
            }

            $paymentStatus = match ($notification->status) {
                'paid' => Payment::STATUS_PAID,
                'pending' => Payment::STATUS_PENDING,
                'expired' => Payment::STATUS_EXPIRED,
                'cancelled' => Payment::STATUS_CANCELLED,
                default => Payment::STATUS_FAILED,
            };
            $invoiceStatus = match ($paymentStatus) {
                Payment::STATUS_PAID => StudentInvoice::STATUS_PAID,
                Payment::STATUS_PENDING => StudentInvoice::STATUS_PENDING,
                Payment::STATUS_EXPIRED => StudentInvoice::STATUS_EXPIRED,
                Payment::STATUS_CANCELLED => StudentInvoice::STATUS_UNPAID,
                default => StudentInvoice::STATUS_UNPAID,
            };

            if ($payment->status !== $paymentStatus) {
                $previousStatus = $payment->status;
                $payment->status = $paymentStatus;
                if ($paymentStatus === Payment::STATUS_PAID) {
                    $payment->paid_at = now();
                    $payment->receipt_number = 'KWT-'.now()->format('Ymd').'-'.str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT);
                }
                $payment->save();
                $this->audit->record(null, 'payment_status_changed', $payment, 'Status pembayaran gateway berubah.', [
                    'from' => $previousStatus,
                    'to' => $paymentStatus,
                    'provider' => $payment->gateway,
                ]);
            }

            if ($invoice->status !== $invoiceStatus) {
                $previousStatus = $invoice->status;
                $invoice->update(['status' => $invoiceStatus]);
                $this->audit->record(null, 'invoice_status_changed', $invoice, 'Status tagihan berubah setelah notifikasi gateway terverifikasi.', [
                    'from' => $previousStatus,
                    'to' => $invoiceStatus,
                    'payment_id' => $payment->id,
                ]);
                if ($invoiceStatus === StudentInvoice::STATUS_PAID) {
                    $this->notifier->paymentPaid($invoice);
                }
            }

            $log->update(['processing_status' => 'diproses', 'processed_at' => now()]);

            return 'diproses';
        });
    }
}
