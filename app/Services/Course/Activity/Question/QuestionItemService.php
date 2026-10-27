<?php

namespace App\Services\Course\Activity\Question;

use App\Repositories\Course\Activity\Assessment\Contracts\QuestionItemRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionOptionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionVariantRepositoryInterface;
use App\Repositories\Course\Activity\Contracts\ActivityRepositoryInterface;
use App\Models\Course\Activity\Assessment\QuestionItem;
use Illuminate\Support\Facades\DB;

class QuestionItemService
{
    public function __construct(
        private readonly QuestionItemRepositoryInterface $questionItems,
        private readonly QuestionRepositoryInterface $questions,
        private readonly QuestionOptionRepositoryInterface $options,
        private readonly QuestionVariantRepositoryInterface $variants,
        private readonly ActivityRepositoryInterface $activities,
    ) {}

    public function getQuestionItems($questionId)
    {
        return $this->questionItems->getByVariant($questionId)
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'updated_at' => $row->updated_at,
                    'correct_answer' => (string) ($row->questionOptions->search(fn ($option) => (bool) $option->is_correct) + 1),
                    'question' => $row->description,
                    'score' => $row->score,
                    'option1' => $row->questionOptions[0]->option,
                    'option2' => $row->questionOptions[1]->option,
                    'option3' => $row->questionOptions[2]->option,
                    'option4' => $row->questionOptions[3]->option,
                    'is_correct' => $row->questionOptions->where('is_correct', 1)->first()->option,
                ];
            });
    }

    public function createQuestionItem(string $variantId, array $data): void
    {
        DB::transaction(function () use ($variantId, $data): void {
            $item = $this->questionItems->create([
                'question_variant_id' => $variantId,
                'description' => sanitize_plain_text($data['description']),
                'score' => $data['score'],
            ]);
            for ($i = 1; $i <= 4; $i++) {
                $this->options->create([
                    'question_item_id' => $item->id,
                    'sort_order' => $i,
                    'option' => $data['options']['option'.$i],
                    'is_correct' => (string) $i === (string) $data['correct_answer'],
                ]);
            }
        });
    }

    public function updateQuestionItem(QuestionItem $item, array $data): void
    {
        DB::transaction(function () use ($item, $data): void {
            $this->questionItems->update($item, [
                'description' => sanitize_plain_text($data['description']),
                'score' => $data['score'],
            ]);
            foreach ($this->options->getByQuestionItem($item->id) as $index => $option) {
                $this->options->update($option, [
                    'option' => $data['options']['option'.($index + 1)],
                    'sort_order' => $index + 1,
                    'is_correct' => (string) ($index + 1) === (string) $data['correct_answer'],
                ]);
            }
        });
    }

    public function deleteQuestionItem(QuestionItem $item): void
    {
        DB::transaction(fn () => $this->questionItems->delete($item));
    }

    public function assertVariant(string $courseId, string $activityId, string $variantId): void
    {
        $activity = $this->activities->find($activityId);
        abort_unless($activity && (string) $activity->course_id === $courseId
            && in_array($activity->type, ['pre-test', 'post-test'], true), 404);
        $question = $this->questions->findByActivity($activityId);
        abort_unless($question, 404);
        $variant = $this->variants->find($variantId);
        abort_unless($variant && (string) $variant->question_id === (string) $question->id, 404);
    }

    public function getActivity($activityId, $variant)
    {
        return $this->questions->findActivityVariant($activityId, $variant);
    }
}
