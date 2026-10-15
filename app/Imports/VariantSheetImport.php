<?php

namespace App\Imports;

use App\Models\Course\Activity\Assessment\QuestionVariant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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

    public function model(array $row): QuestionVariant
    {
        $row['mix'] = $row['mix'] ?? 0;
        $row = Validator::make($row, [
            'code' => ['required', 'string', 'max:31', Rule::unique('question_variants', 'code')->where('question_id', $this->questionId)],
            'variant' => ['required', 'integer', 'min:1', Rule::unique('question_variants', 'variant')->where('question_id', $this->questionId)],
            'mix' => 'required|boolean',
        ])->validate();
        $variant = QuestionVariant::create([
            'code' => $row['code'],
            'question_id' => $this->questionId,
            'variant' => $row['variant'],
            'mix' => $row['mix'],
        ]);

        return $variant;
    }
}
