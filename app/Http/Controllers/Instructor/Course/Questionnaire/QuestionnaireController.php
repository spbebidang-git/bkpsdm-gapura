<?php

namespace App\Http\Controllers\Instructor\Course\Questionnaire;

use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\QuestionnaireService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class QuestionnaireController
{
    protected $activity, $questionnaire;

    public function __construct(ActivityService $activityService, QuestionnaireService $questionnaireService)
    {
        $this->activity = $activityService;
        $this->questionnaire = $questionnaireService;
    }

    public function create(Course $course)
    {
        return Inertia::render('Instructor/Course/Questionnaire/Create', [
            'slug' => $course->slug,
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $data = $this->validated($request);

        try {
            DB::beginTransaction();

            $activity = $this->activity->createActivity([
                'course_id'   => $course->id,
                'type'        => 'questionnaire',
                'title'       => $request->title,
                'value'       => $request->value,
                'instruction' => $request->instruction,
            ]);

            $this->questionnaire->createQuestionnaire([
                'activity_id'  => $activity->id,
                'sub_title'    => $request->sub_title,
                'intro'        => $request->intro,
                'is_anonymous' => $request->boolean('is_anonymous'),
                'duration'     => $request->duration ?? 0,
                'questions'    => $data['questions'],
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug)
                ->with('success', 'Kuesioner berhasil ditambahkan.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Questionnaire store failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);

            return to_route('instructor.course.show', $course->slug)
                ->with('danger', 'Gagal menambahkan kuesioner: ' . $th->getMessage());
        }
    }

    public function edit(Course $course, Activity $questionnaire)
    {
        return Inertia::render('Instructor/Course/Questionnaire/Edit', [
            'slug'     => $course->slug,
            'activity' => $this->questionnaire->getQuestionnaireForEdit($questionnaire),
        ]);
    }

    public function update(Request $request, Course $course, Activity $questionnaire)
    {
        $data = $this->validated($request);

        try {
            DB::beginTransaction();

            $this->activity->updateActivity([
                'activity_id' => $questionnaire->id,
                'title'       => $request->title,
                'value'       => $request->value,
                'instruction' => $request->instruction,
            ]);

            $this->questionnaire->updateQuestionnaire([
                'activity_id'  => $questionnaire->id,
                'sub_title'    => $request->sub_title,
                'intro'        => $request->intro,
                'is_anonymous' => $request->boolean('is_anonymous'),
                'duration'     => $request->duration ?? 0,
                'questions'    => $data['questions'],
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug)
                ->with('success', 'Kuesioner berhasil diperbarui.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Questionnaire update failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);

            return to_route('instructor.course.show', $course->slug)
                ->with('danger', 'Gagal memperbarui kuesioner: ' . $th->getMessage());
        }
    }

    /** Rekap jawaban peserta. */
    public function result(Course $course, $activityId)
    {
        $activity = Activity::where('course_id', $course->id)->findOrFail($activityId);

        return Inertia::render('Instructor/Course/Questionnaire/Result', [
            'slug'     => $course->slug,
            'activity' => [
                'id'    => $activity->id,
                'title' => $activity->title,
            ],
            'recap'    => $this->questionnaire->getRecap($activity),
        ]);
    }

    /** Unduh jawaban mentah sebagai CSV. */
    public function export(Course $course, $activityId)
    {
        $activity = Activity::where('course_id', $course->id)->findOrFail($activityId);
        $table = $this->questionnaire->getExportTable($activity);

        $filename = Str::slug($activity->title ?: 'kuesioner') . '.csv';

        return response()->streamDownload(function () use ($table) {
            $handle = fopen('php://output', 'w');

            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $table['headers'], ';');
            foreach ($table['rows'] as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Pertanyaan dikirim sebagai JSON string karena jumlahnya bisa puluhan
     * (kuesioner hasil impor Moodle) dan bentuknya bersarang.
     */
    protected function validated(Request $request): array
    {
        $request->validate([
            'title'     => 'required|string',
            'sub_title' => 'nullable|string',
            'value'     => 'required|numeric',
            'intro'     => 'nullable|string',
            'duration'  => 'nullable|numeric',
            'questions' => 'required|string',
        ]);

        $questions = json_decode($request->questions, true);

        if (!is_array($questions) || empty($questions)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'questions' => 'Kuesioner harus punya minimal satu pertanyaan.',
            ]);
        }

        return ['questions' => $questions];
    }
}
