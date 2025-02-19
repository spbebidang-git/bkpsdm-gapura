<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Question;
use App\Models\Course\Question\QuestionItem;
use App\Models\Course\Question\QuestionVariant;
use App\Models\Learn\Learn;
use App\Models\User;
use App\Services\Learn\LearnService;

class QuestionService
{
    protected $learn;

    public function __construct(LearnService $learnService)
    {
        $this->learn = $learnService;
    }

    public function createQuestion($data)
    {
        return Question::create($data);
    }

    public function getPreTest($activity)
    {
        $question = Question::with('questionVariant.questionItems.questionOptions')
            ->where('activity_id', $activity->id)
            ->first();

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
        $learn = Learn::with('learnItems')
            ->whereRelation('learnItems', 'activity_id', '=', $activityId)
            ->where('participant_id', $participantId)->first();

        // dd($learn->learnItems);
        if (!$learn) {
            return 0;
        }

        return $learn->learnItems->first()->status;
    }

    public function getPreTestParticipant($participantId, $activity)
    {
        $question = Question::with('questionVariant.questionItems.questionOptions')
            ->where('activity_id', $activity->id)
            ->first();

        $participant = User::where('id', $participantId)->first();

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
                ]
            ];
        });

        // dd($activity->learnItem->getStatus($participantId));

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'description' => $question->description,
            'type' => 'pre-test',
            'status' => $this->getStatus($participantId, $activity->id),
            'data' => [
                'question_items' => $mappedQuestionItems,
                'mix' => $questionVariant->mix,
                'is_loop' => $question->is_loop,
                'min_score' => 100
            ]
        ];
    }

    public function getPostTest($activity)
    {
        $question = Question::with('questionVariant.questionItems.questionOptions')
            ->where('activity_id', $activity->id)
            ->first();

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

        $question = Question::with('questionVariant.questionItems.questionOptions')
            ->where('activity_id', $activity->id)
            ->first();

        $participant = User::where('id', $participantId)->first();

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
                ]
            ];
        });

        // dd($activity->learnItem->getStatus($participantId));

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'description' => $question->description,
            'type' => 'pre-test',
            'status' => $this->getStatus($participantId, $activity->id),
            'data' => [
                'question_items' => $mappedQuestionItems,
                'mix' => $questionVariant->mix,
                'is_loop' => $question->is_loop,
                'min_score' => $question->min_score
            ]
        ];
    }

    public function storePreTest($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type' => 'pre-test',
            'status' => 'done',
            'response' =>  $this->getResultQuestion($request)
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, null, $learnItem);
    }

    public function storePostTest($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type' => 'post-test',
            'status' => 'done',
            'response' =>  $this->getResultQuestion($request)
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, null, $learnItem);
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
                'is_correct' => $is_correct
            ];
        }

        return $data;
    }

    public function calculateScore($question_id, $answer)
    {
        $questionItem = QuestionItem::with('questionOptions')->findOrFail($question_id);

        $correctAnswer = $questionItem->questionOptions->where('is_correct', 1)->pluck('option')->first();

        if ($correctAnswer == $answer) {
            return $questionItem->score;
        }

        return 0;
    }

    public function updateQuestion($request)
    {
        $question = Question::where('activity_id', $request['activity_id'])->first();

        if ($request['type'] == 'pre') {
            $question->update([
                'description' => $request['description'],
                'is_loop' => $request['is_loop'],
            ]);

            return true;
        }

        $question->update([
            'description' => $request['description'],
            'min_score' => $request['min_score'],
            'is_loop' => $request['is_loop'],
        ]);

        return true;
    }

    public function getQuestion($activity)
    {
        $question = Question::where('activity_id', $activity->id)->first();

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'value' => $activity->value,
            'timer' => $activity->timer,
            'description' => $question->description,
            'instruction' => $activity->instruction,
            'min_score' => $question->min_score,
            'is_loop' => $question->is_loop,
            'show_result' => $question->show_result,
        ];
    }

    public function validateQuestion($countResponse, $userId, $activityId)
    {

        $questionVariant = QuestionVariant::with('questionItems')->where('id', get_user_meta($userId, $activityId))->first();

        if ($countResponse < $questionVariant->questionItems->count()) {
            return false;
        }

        return true;
    }
}
