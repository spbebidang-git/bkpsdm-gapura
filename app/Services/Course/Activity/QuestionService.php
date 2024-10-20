<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Question;
use App\Models\Course\Question\QuestionItem;
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

    public function getPreTestParticipant($activity)
    {
        $question = Question::with('questionVariant.questionItems.questionOptions')
            ->where('activity_id', $activity->id)
            ->first();

        $questionVariants = $question->questionVariant->first()->questionItems;

        $questionItems = $questionVariants->map(function ($row) {
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

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'description' => $question->description,
            'type' => 'pre-test',
            'status' => 0,
            'data' => $questionItems
        ];
    }

    public function getPostTest($activityId)
    {
        $question = Question::with('questionVariant.questionItems.questionOptions')
            ->where('activity_id', $activityId)
            ->first();

        $questionVariants = $question->questionVariant->first()->questionItems;

        $questionItems = $questionVariants->map(function ($row) {
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

        return [
            'activity_id' => $question->id,
            'type' => 'post-test',
            'data' => $questionItems
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
            'show_result' => $request['show_result'],
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
            'description' => $question->description,
            'min_score' => $question->min_score,
            'is_loop' => $question->is_loop,
            'show_result' => $question->show_result,
        ];
    }
}
