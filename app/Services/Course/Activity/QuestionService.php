<?php

namespace App\Services\Course\Activity;

use App\Repositories\Course\Activity\Assessment\Contracts\QuestionItemRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionVariantRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use App\Services\Learning\LearnService;

class QuestionService
{
    public function __construct(
        private readonly LearnService $learn,
        private readonly QuestionRepositoryInterface $questions,
        private readonly QuestionItemRepositoryInterface $questionItems,
        private readonly QuestionVariantRepositoryInterface $variants,
        private readonly LearnRepositoryInterface $learns,
    ) {}

    public function createQuestion($data)
    {
        return $this->questions->create($data);
    }

    public function getPreTest($activity)
    {
        $question = $this->questions->findByActivity($activity->id, ['questionVariant.questionItems.questionOptions']);

        // $questionVariants = $question->questionVariant->first()->questionItems;

        // $questionItems = $questionVariants->map(function ($row) {
        //     return [
        //         'question_id' => $row->id,
        //         'question' => $row->description,
        //         'option' => [
        //             'a' => $row->questionOptions[0]->option,
        //             'b' => $row->questionOptions[1]->option,
        //             'c' => $row->questionOptions[2]->option,
        //             'd' => $row->questionOptions[3]->option,
        //         ]
        //     ];
        // });

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'description' => $question->description,
            'type' => 'pre-test',
            // 'status' => 0,
            // 'data' => $questionItems
        ];
    }

    public function getStatus($participantId, $activityId)
    {
        $learn = $this->learns->findByParticipant($participantId, [
            'learnItems' => fn ($query) => $query->where('activity_id', $activityId),
        ]);

        // dd($learn->learnItems);
        if (! $learn) {
            return 0;
        }

        return $learn->learnItems->first()->status;
    }

    public function getPreTestParticipant($participantId, $activity)
    {
        $question = $this->questions->findByActivity($activity->id, ['questionVariant.questionItems.questionOptions']);

        $preTestVariantId = get_user_meta($participantId, $activity->id);

        $questionVariant = $question->questionVariant->where('id', $preTestVariantId)->first();

        $questionItems = $question->questionVariant->where('id', $preTestVariantId)->first()->questionItems;

        $mappedQuestionItems = $questionItems->map(function ($row) {
            return [
                'question_id' => $row->id,
                'question' => $row->description,
                'option' => [
                    'a' => $row->questionOptions[0]->option,
                    'b' => $row->questionOptions[1]->option,
                    'c' => $row->questionOptions[2]->option,
                    'd' => $row->questionOptions[3]->option,
                ],
            ];
        });

        // dd($activity->learnItem->getStatus($participantId));

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'timer' => $activity->timer,
            'description' => $question->description,
            'type' => 'pre-test',
            'status' => $this->getStatus($participantId, $activity->id),
            'data' => [
                'question_items' => $mappedQuestionItems,
                'mix' => $questionVariant->mix,
                'is_loop' => $question->is_loop,
                'min_score' => $question->min_score,
            ],
        ];
    }

    public function getPostTest($activity)
    {
        $question = $this->questions->findByActivity($activity->id, ['questionVariant.questionItems.questionOptions']);

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'description' => $question->description,
            'type' => 'pre-test',
            // 'status' => 0,
            // 'data' => $questionItems
        ];
    }

    public function getPostTestParticipant($participantId, $activity)
    {
        // $question = Question::with('questionVariant.questionItems.questionOptions')
        //     ->where('activity_id', $activity->id)
        //     ->first();

        // // $questionVariants = $question->questionVariant->first()->questionItems;

        // $questionItems = $questionVariants->map(function ($row) {
        //     return [
        //         'question_id' => $row->id,
        //         'question' => $row->description,
        //         'option' => [
        //             'a' => $row->questionOptions[0]->option,
        //             'b' => $row->questionOptions[1]->option,
        //             'c' => $row->questionOptions[2]->option,
        //             'd' => $row->questionOptions[3]->option,
        //         ]
        //     ];
        // });

        // return [
        //     'activity_id' => $activity->id,
        //     'title' => $activity->title,
        //     'description' => $question->description,
        //     'type' => 'post-test',
        //     'status' => $this->getStatus($participantId, $activity->id),
        //     'data' => [
        //         'min_score' => $question->min_score,
        //         'question_items' => $questionItems,
        //         'mix' => $question->questionVariant->first()->mix,
        //         'is_loop' => $question->is_loop
        //     ]
        // ];

        $question = $this->questions->findByActivity($activity->id, ['questionVariant.questionItems.questionOptions']);

        $preTestVariantId = get_user_meta($participantId, $activity->id);

        $questionVariant = $question->questionVariant->where('id', $preTestVariantId)->first();

        $questionItems = $question->questionVariant->where('id', $preTestVariantId)->first()->questionItems;

        $mappedQuestionItems = $questionItems->map(function ($row) {
            return [
                'question_id' => $row->id,
                'question' => $row->description,
                'option' => [
                    'a' => $row->questionOptions[0]->option,
                    'b' => $row->questionOptions[1]->option,
                    'c' => $row->questionOptions[2]->option,
                    'd' => $row->questionOptions[3]->option,
                ],
            ];
        });

        // dd($activity->learnItem->getStatus($participantId));

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'timer' => $activity->timer,
            'description' => $question->description,
            'type' => 'pre-test',
            'status' => $this->getStatus($participantId, $activity->id),
            'data' => [
                'question_items' => $mappedQuestionItems,
                'mix' => $questionVariant->mix,
                'is_loop' => $question->is_loop,
                'min_score' => $question->min_score,
            ],
        ];
    }

    public function storePreTest($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type' => 'pre-test',
            'status' => 'done',
            'response' => $this->getResultQuestion($request),
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, $learnItem);
    }

    public function storePostTest($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type' => 'post-test',
            'status' => 'done',
            'response' => $this->getResultQuestion($request),
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, $learnItem);
    }

    public function getResultQuestion($request)
    {
        $data = [];

        for ($i = 0; $i < count($request); $i++) {
            $question = $request[$i]['question'];
            $option_select = $request[$i]['option_select'];
            $question_id = $request[$i]['question_id'];

            $score = $this->calculateScore($question_id, $option_select);
            $is_correct = $score > 0 ? true : false;

            // Tambahkan ke array $data
            $data[] = [
                'question' => $question,
                'option_select' => $option_select,
                'score' => $score,
                'is_correct' => $is_correct,
            ];
        }

        return $data;
    }

    public function calculateScore($question_id, $answer)
    {
        $questionItem = $this->questionItems->findWithOptions($question_id);

        $correctAnswer = $questionItem->questionOptions->where('is_correct', 1)->pluck('option')->first();

        if ($correctAnswer == $answer) {
            return $questionItem->score;
        }

        return 0;
    }

    public function updateQuestion($request)
    {
        $question = $this->questions->findByActivity($request['activity_id']);

        // Some legacy activities were created without their companion question
        // record. Create it on first save so those activities remain editable.
        if (! $question) {
            return $this->questions->create([
                'activity_id' => $request['activity_id'],
                'description' => $request['description'],
                'type' => $request['type'],
                'min_score' => $request['min_score'],
                'is_loop' => $request['is_loop'],
            ]);
        }

        if ($request['type'] == 'pre') {
            $this->questions->update($question, [
                'description' => $request['description'],
                'is_loop' => $request['is_loop'],
                'min_score' => $request['min_score'],
            ]);

            return true;
        }

        $this->questions->update($question, [
            'description' => $request['description'],
            'min_score' => $request['min_score'],
            'is_loop' => $request['is_loop'],
        ]);

        return true;
    }

    public function getQuestion($activity)
    {
        $question = $this->questions->findByActivity($activity->id);

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'value' => $activity->value,
            'timer' => $activity->timer,
            'description' => $question?->description ?? '',
            'instruction' => $activity->instruction,
            'min_score' => $question?->min_score ?? 0,
            'is_loop' => $question?->is_loop ?? false,
            'show_result' => $question?->show_result ?? false,
        ];
    }

    public function validateQuestion($countResponse, $userId, $activityId)
    {

        $questionVariant = $this->variants->findWithItems(get_user_meta($userId, $activityId));

        if ($countResponse < $questionVariant->questionItems->count()) {
            return false;
        }

        return true;
    }
}
