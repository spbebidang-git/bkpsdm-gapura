<?php

namespace App\Http\Controllers\Instructor\Course\Question;

use App\Imports\VariantImport;
use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Models\Course\Question;
use App\Models\Course\Question\QuestionVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ImportQuestionController
{
    public function index(Course $course, Activity $activity)
    {
        return Inertia::render('Instructor/Course/Question/Import', [
            'course' => $course,
            'activity' => $activity,
        ]);
    }

    public function store(Course $course, Activity $activity, Request $request)
    {
        # validation 
        $request->validate([
            'content' => 'required|mimes:xlsx,xls|max:2048', // Pastikan hanya menerima file Excel
        ]);
        try {
            DB::beginTransaction();

            $question = Question::where('activity_id', $activity->id)->first();
            # import variant 
            Excel::import(new VariantImport($question->id), $request->file('content'));

            # get variant imported 
            $variants = QuestionVariant::where('question_id', $activity->id)->pluck('code');

            Excel::import(new VariantImport($variants), $request->file('content'));

            # add flag has imported 
            $question = Question::where('activity_id', $activity->id)->first();
            $question->has_imported = 1;
            $question->save();

            DB::commit();

            return to_route('instructor.course.show', [$course->slug])->with('success', 'Import question successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.activity.import-question.index', [$course->slug, $activity->id])->with('danger', $th->getMessage());
        }
    }

    public function download()
    {
        $filePath = public_path('/assets/media/documents/static/gapura_import_variant_question.xlsx');

        return response()->download($filePath);
    }
}
