<?php

namespace App\Imports;

use App\Services\Course\Activity\Question\QuestionItemService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VariantDetailImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public function __construct(private readonly string $variantId) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = Validator::make($row->toArray(), [
                'description' => 'required|string',
                'score' => 'required|integer|min:1|max:100',
                'option_a' => 'required', 'option_b' => 'required',
                'option_c' => 'required', 'option_d' => 'required',
                'correct' => 'required',
            ])->validate();
            $options = array_map('strval', [$data['option_a'], $data['option_b'], $data['option_c'], $data['option_d']]);
            if (count(array_keys($options, (string) $data['correct'], true)) !== 1) {
                throw ValidationException::withMessages(['content' => 'Kunci jawaban harus cocok dengan tepat satu pilihan pada setiap soal.']);
            }
            app(QuestionItemService::class)->createQuestionItem($this->variantId, [
                'description' => $data['description'],
                'score' => $data['score'],
                'options' => [
                    'option1' => $options[0], 'option2' => $options[1],
                    'option3' => $options[2], 'option4' => $options[3],
                ],
                'correct_answer' => (string) (array_search((string) $data['correct'], $options, true) + 1),
            ]);
        }
    }
}
