<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentsExport implements FromQuery, WithCustomChunkSize, WithHeadings, WithMapping
{
    /** @param Builder<Payment> $query */
    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query->with([
            'invoice.studentProfile.user',
            'invoice.studentProfile.schoolClass',
        ]);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            'Nomor Kuitansi',
            'Nomor Tagihan',
            'Nama Murid',
            'NIS',
            'Kelas',
            'Jenjang',
            'Jenis Tagihan',
            'Periode',
            'Nominal',
            'Metode',
            'Penyedia',
            'Status',
            'Tanggal Bayar',
        ];
    }

    /** @return list<string|int> */
    public function map($payment): array
    {
        $values = [
            $payment->receipt_number ?? '',
            $payment->invoice->invoice_number,
            $payment->invoice->studentProfile->user->name,
            $payment->invoice->studentProfile->nis ?? '',
            $payment->invoice->studentProfile->schoolClass->name,
            $payment->invoice->studentProfile->schoolClass->school_level,
            $payment->invoice->bill_type_name,
            $payment->invoice->billing_period,
            $payment->amount,
            $payment->method,
            $payment->gateway ?? 'manual',
            $payment->status,
            $payment->paid_at?->format('d/m/Y H:i') ?? '',
        ];

        return array_map(static function (string|int $value): string|int {
            if (is_string($value) && preg_match('/^[\s]*[=+\-@]/u', $value) === 1) {
                return "'".$value;
            }

            return $value;
        }, $values);
    }
}
