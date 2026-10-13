<?php

namespace App\Services;

use App\Models\PpdbApplication;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PpdbReceiptPdf
{
    public function response(PpdbApplication $application): Response
    {
        $lines = [
            'BUKTI PENDAFTARAN PPDB',
            'SMP SMA MUHAMMADIYAH SUBANG',
            '',
            'Nomor pendaftaran: '.$application->registration_number,
            'Nama calon murid: '.$application->student_name,
            'NISN: '.($application->nisn ?? '-'),
            'Jenjang: '.($application->school_level ?? '-'),
            'Jurusan: '.($application->major ?: '-'),
            'Asal sekolah: '.($application->previous_school ?: '-'),
            'Tanggal pendaftaran: '.$application->submitted_at?->format('d/m/Y H:i'),
            'Status: '.match ($application->status) {
                PpdbApplication::STATUS_ACCEPTED => 'Diterima',
                PpdbApplication::STATUS_REJECTED => 'Ditolak',
                default => 'Dalam proses verifikasi',
            },
            '',
            'Simpan bukti ini untuk keperluan administrasi PPDB.',
        ];

        $stream = "BT\n/F1 18 Tf\n50 790 Td\n(".$this->escape(array_shift($lines)).") Tj\n";
        $stream .= "/F1 11 Tf\n0 -24 Td\n(".$this->escape(array_shift($lines)).") Tj\n";
        foreach ($lines as $line) {
            foreach ($this->wrap($line) as $wrappedLine) {
                $stream .= "0 -22 Td\n(".$this->escape($wrappedLine).") Tj\n";
            }
        }
        $stream .= "ET\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."endstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$application->registration_number.'-bukti.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function escape(string $text): string
    {
        $singleLine = preg_replace('/[\x00-\x1F\x7F]+/', ' ', Str::ascii($text)) ?? '';

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $singleLine);
    }

    private function wrap(string $text): array
    {
        $ascii = preg_replace('/[\x00-\x1F\x7F]+/', ' ', Str::ascii($text)) ?? '';

        return explode("\n", wordwrap($ascii, 80, "\n", true));
    }
}
