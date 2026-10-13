<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\StudentInvoice;
use App\Services\Payments\PaymentAuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireOverdueInvoices extends Command
{
    protected $signature = 'payments:expire-overdue';

    protected $description = 'Tandai tagihan yang melewati jatuh tempo sebagai kedaluwarsa.';

    public function handle(PaymentAuditLogger $audit): int
    {
        StudentInvoice::query()
            ->whereIn('status', [StudentInvoice::STATUS_UNPAID, StudentInvoice::STATUS_PENDING])
            ->whereDate('due_date', '<', today())
            ->orderBy('id')
            ->chunkById(100, function ($invoices) use ($audit): void {
                foreach ($invoices as $invoice) {
                    DB::transaction(function () use ($invoice, $audit): void {
                        $lockedInvoice = StudentInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                        if (
                            ! in_array($lockedInvoice->status, [StudentInvoice::STATUS_UNPAID, StudentInvoice::STATUS_PENDING], true)
                            || ! $lockedInvoice->due_date->lt(today())
                        ) {
                            return;
                        }

                        foreach ($lockedInvoice->payments()->where('status', Payment::STATUS_PENDING)->lockForUpdate()->get() as $payment) {
                            $payment->update(['status' => Payment::STATUS_EXPIRED]);
                            $audit->record(null, 'payment_status_changed', $payment, 'Pembayaran kedaluwarsa karena tagihan melewati jatuh tempo.', [
                                'from' => Payment::STATUS_PENDING,
                                'to' => Payment::STATUS_EXPIRED,
                            ]);
                        }

                        $previousStatus = $lockedInvoice->status;
                        $lockedInvoice->update(['status' => StudentInvoice::STATUS_EXPIRED]);
                        $audit->record(null, 'invoice_status_changed', $lockedInvoice, 'Tagihan kedaluwarsa setelah melewati jatuh tempo.', [
                            'from' => $previousStatus,
                            'to' => StudentInvoice::STATUS_EXPIRED,
                        ]);
                    });
                }
            });

        return self::SUCCESS;
    }
}
