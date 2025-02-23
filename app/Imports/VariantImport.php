<?php

namespace App\Imports;

use App\Models\Course\Question\QuestionVariant;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class VariantImport implements WithMultipleSheets, WithHeadingRow
{
    protected $codes;

    public function __construct($codes = null)
    {
        $this->codes = $codes;
    }

    public function sheets(): array
    {
        if (is_int($this->codes)) {
            return [
                0 => new VariantSheetImport($this->codes), // Mengambil sheet pertama (index 0)
            ];
        }

        $sheets = [];

        foreach ($this->codes as $code) {
            $sheets[$code] = new VariantDetailImport($code);
        }

        return $sheets;
    }
}
