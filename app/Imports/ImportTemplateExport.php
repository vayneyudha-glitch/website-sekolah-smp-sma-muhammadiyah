<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $columns,
        private readonly array $instructions,
    ) {}

    public function sheets(): array
    {
        return [
            new ImportTemplateDataSheet($this->columns),
            new ImportTemplateInstructionsSheet($this->instructions),
        ];
    }
}
