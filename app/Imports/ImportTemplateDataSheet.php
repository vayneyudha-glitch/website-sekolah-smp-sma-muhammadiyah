<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ImportTemplateDataSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $columns) {}

    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function title(): string
    {
        return 'Data';
    }
}
