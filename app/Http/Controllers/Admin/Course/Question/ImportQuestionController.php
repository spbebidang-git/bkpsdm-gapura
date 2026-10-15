<?php

namespace App\Http\Controllers\Admin\Course\Question;

use App\Imports\VariantImport;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Models\Course\Activity\Assessment\Question;
use App\Models\Course\Activity\Assessment\QuestionVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ImportQuestionController
{
    public function index(Course $course, Activity $activity)
    {
        abort_unless($activity->course_id === $course->id && in_array($activity->type, ['pre-test', 'post-test'], true), 404);
        $question = Question::where('activity_id', $activity->id)->firstOrFail();
        return Inertia::render('Admin/Course/Question/ImportQuestion', [
            'canImport' => !$question->has_imported && !$question->questionVariant()->exists(),
            'course' => $course,
            'activity' => $activity,
        ]);
    }

    public function store(Course $course, Activity $activity, Request $request)
    {
        abort_unless($activity->course_id === $course->id && in_array($activity->type, ['pre-test', 'post-test'], true), 404);
        # validation 
        $request->validate([
            'content' => 'required|mimes:xlsx,xls|max:2048', // Pastikan hanya menerima file Excel
        ], [], ['content' => 'Berkas soal']);
        try {
            DB::beginTransaction();

            $question = Question::where('activity_id', $activity->id)->lockForUpdate()->firstOrFail();
            if ($question->has_imported || $question->questionVariant()->exists()) {
                throw ValidationException::withMessages(['content' => 'Impor hanya tersedia sebelum varian dibuat dan sebelum impor pertama.']);
            }
            # import variant 
            Excel::import(new VariantImport($question->id), $request->file('content'));

            # get variant imported 
            $variants = QuestionVariant::where('question_id', $question->id)->pluck('id', 'code');
            if ($variants->isEmpty()) {
                throw ValidationException::withMessages(['content' => 'File tidak berisi varian soal.']);
            }

            Excel::import(new VariantImport($variants), $request->file('content'));

            # add flag has imported 
            $question->has_imported = 1;
            $question->save();

            DB::commit();

            return to_route('admin.course.activity.variant.index', [$course->slug, $activity->id])->with('success', 'Varian dan soal berhasil diimpor.');
        } catch (ValidationException $exception) {
            DB::rollBack();
            throw ValidationException::withMessages(['content' => implode(' ', $exception->validator->errors()->all())]);
        } catch (\Throwable $th) {
            DB::rollBack();

            report($th);
            throw ValidationException::withMessages(['content' => 'File gagal diimpor. Periksa nama sheet, kolom, dan data sesuai template.']);
        }
    }

    public function download()
    {
        $filePath = public_path('/assets/media/documents/static/gapura_import_variant_question.xlsx');

        return response()->download($filePath);
    }
}
