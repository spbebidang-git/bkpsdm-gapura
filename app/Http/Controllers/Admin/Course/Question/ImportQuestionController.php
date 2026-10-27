<?php

namespace App\Http\Controllers\Admin\Course\Question;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Http\Requests\Course\ImportQuestionRequest;
use App\Services\Course\Activity\Question\QuestionImportService;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class ImportQuestionController
{
    public function __construct(private readonly QuestionImportService $imports) {}

    public function index(Course $course, Activity $activity)
    {
        abort_unless($activity->course_id === $course->id && in_array($activity->type, ['pre-test', 'post-test'], true), 404);
        return Inertia::render('Admin/Course/Question/ImportQuestion', [
            'canImport' => $this->imports->canImport((string) $activity->id),
            'course' => $course,
            'activity' => $activity,
        ]);
    }

    public function store(Course $course, Activity $activity, ImportQuestionRequest $request)
    {
        abort_unless($activity->course_id === $course->id && in_array($activity->type, ['pre-test', 'post-test'], true), 404);
        try {
            $this->imports->import((string) $activity->id, $request->file('content'));

            return to_route('admin.course.activity.variant.index', [$course->slug, $activity->id])->with('success', 'Varian dan soal berhasil diimpor.');
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['content' => implode(' ', $exception->validator->errors()->all())]);
        } catch (\Throwable $th) {

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
