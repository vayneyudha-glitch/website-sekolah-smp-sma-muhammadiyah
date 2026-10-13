<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ImportTemplateInstructionsSheet implements FromArray, WithTitle
{
    public function __construct(private readonly array $instructions) {}

    public function array(): array
    {
        return $this->instructions;
    }

    public function title(): string
    {
        return 'Petunjuk';
    }
}
