<?php

namespace App\Imports;

use App\Models\Course\Question\QuestionItem;
use App\Models\Course\Question\QuestionOption;
use App\Models\Course\Question\QuestionVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VariantDetailImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function collection(Collection $rows)
    {
        // dd($rows);
        foreach ($rows as $row) {
            $variant = QuestionVariant::where('code', $this->code)->first();
            $questionItem = QuestionItem::create([
                'question_variant_id' => $variant->id,
                'description' => $row['description'],
                'score' => $row['score'],
            ]);

            QuestionOption::insert([
                [
                    'question_item_id' => $questionItem->id,
                    'option' => $row['option_a'],
                    'is_correct' => $row['option_a'] == $row['correct'] ? 1 : 0
                ],
                [
                    'question_item_id' => $questionItem->id,
                    'option' => $row['option_b'],
                    'is_correct' => $row['option_b'] == $row['correct'] ? 1 : 0

                ],
                [
                    'question_item_id' => $questionItem->id,
                    'option' => $row['option_c'],
                    'is_correct' => $row['option_c'] == $row['correct'] ? 1 : 0

                ],
                [
                    'question_item_id' => $questionItem->id,
                    'option' => $row['option_d'],
                    'is_correct' => $row['option_d'] == $row['correct'] ? 1 : 0
                ],
            ]);
        }
    }

    // public function model(array $row)
    // {
    //     $variant = QuestionVariant::where('code', $this->code)->first();

    //     return new VariantDetail([
    //         'question_variant_id' => $variant ? $variant->id : null,
    //         'description' => $row['description'],
    //         'score' => $row['score'],
    //         'option_a' => $row['option_a'],
    //         'option_b' => $row['option_b'],
    //         'option_c' => $row['option_c'],
    //         'option_d' => $row['option_d'],
    //         'is_correct' => $row['is_correct'],
    //     ]);
    // }
    // protected $sheetName
    // public function __construct($sheetName)
    // {
    //     $this->sheetName = $sheetName;
    // }
}
