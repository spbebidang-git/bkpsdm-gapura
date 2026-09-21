<?php

namespace App\Imports;

use App\Models\Course\Activity\Assessment\QuestionVariant;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;

class VariantSheetImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    protected $questionId;

    public function __construct($questionId)
    {
        $this->questionId = $questionId;
    }

    public function model(array $row)
    {
        $variant = QuestionVariant::create([
            'code' => $row['code'],
            'question_id' => $this->questionId,
            'variant' => $row['variant'],
            'mix' => $row['mix'],
        ]);

        return $variant;
    }
}
