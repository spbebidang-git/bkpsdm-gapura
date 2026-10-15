<?php

namespace App\Imports;

use App\Models\Course\Activity\Assessment\QuestionItem;
use App\Models\Course\Activity\Assessment\QuestionOption;
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
            $item = QuestionItem::create([
                'question_variant_id' => $this->variantId,
                'description' => sanitize_plain_text($data['description']),
                'score' => $data['score'],
            ]);
            foreach ($options as $index => $option) {
                QuestionOption::create([
                    'question_item_id' => $item->id,
                    'option' => $option,
                    'sort_order' => $index + 1,
                    'is_correct' => $option === (string) $data['correct'],
                ]);
            }
        }
    }
}
