<?php

namespace App\Http\Controllers\Admin\Course\Question;

use App\Models\Course\Course;
use App\Models\Course\Activity\Assessment\QuestionVariant;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\Question\VariantService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Course\Activity\Activity;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Course\Activity\Assessment\Question;

class VariantController
{
    protected $variant, $activity;

    public function __construct(VariantService $variantService, ActivityService $activityService)
    {
        $this->variant = $variantService;
        $this->activity = $activityService;
    }

    public function index(Course $course, $activityId)
    {
        $this->assertActivity($course, $activityId);
        if (!$this->variant->getVariant($activityId)) {
            return to_route('admin.course.activities.index', $course->slug);
        }

        if (request()->ajax() && !request()->header('X-Inertia')) {
            return DataTables::of($this->variant->getVariant($activityId))->make();
        }

        $question = Question::where('activity_id', $activityId)->firstOrFail();
        return Inertia::render('Instructor/Course/Variant/Index', [
            'canImport' => !$question->has_imported && !$question->questionVariant()->exists(),
            'slug' => $course->slug,
            'activity' => $this->activity->getDetailActivity($activityId),
            'variants' => $this->variant->getVariant($activityId),
            'variantWarning' => $this->variant->getVariantWarning($activityId),
        ]);
    }

    public function create(Course $course, $activityId)
    {
        $this->assertActivity($course, $activityId);
        return Inertia::render('Instructor/Course/Variant/Create', [
            'slug' => $course->slug,
            'variant' => $this->variant->generateVariantNumber($activityId),
            'activityId' => $activityId,
        ]);
    }

    public function store(Course $course, $activityId, Request $request)
    {
        $this->assertActivity($course, $activityId);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('question_variants', 'code')->where('question_id', $this->variant->getQuestionId($activityId))],
            'variant' => 'required|integer|min:1',
            'mix' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $this->variant->createVariant($activityId, $data);

            DB::commit();

            return to_route('admin.course.activity.variant.index', [$course->slug, $activityId])->with('success', 'Varian berhasil ditambahkan.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.course.activity.variant.index', [$course->slug, $activityId])->with('danger', 'Varian gagal disimpan.');
        }
    }

    public function edit(Course $course, $activityId, QuestionVariant $variant)
    {
        $this->assertActivity($course, $activityId);
        abort_unless($variant->question_id === $this->variant->getQuestionId($activityId), 404);
        return Inertia::render('Instructor/Course/Variant/Edit', [
            'slug' => $course->slug,
            'activityId' => $activityId,
            'variant' => $variant
        ]);
    }

    public function update(Course $course, $activityId, QuestionVariant $variant, Request $request)
    {
        $this->assertActivity($course, $activityId);
        abort_unless($variant->question_id === $this->variant->getQuestionId($activityId), 404);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('question_variants', 'code')->where('question_id', $variant->question_id)->ignore($variant->id)],
            'variant' => 'required|integer|min:1',
            'mix' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $variant->update([
                'mix' => $data['mix'],
                'code' => $data['code'],
                'variant' => $data['variant'],
            ]);

            DB::commit();

            return to_route('admin.course.activity.variant.index', [$course->slug, $activityId])->with('success', 'Varian berhasil diperbarui.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.course.activity.variant.index', [$course->slug, $activityId])->with('danger', 'Varian gagal disimpan.');
        }
    }

    public function destroy(Course $course, $activityId, QuestionVariant $variant)
    {
        $this->assertActivity($course, $activityId);
        abort_unless($variant->question_id === $this->variant->getQuestionId($activityId), 404);
        try {
            DB::beginTransaction();

            $variant->delete();

            DB::commit();

            return to_route('admin.course.activity.variant.index', [$course->slug, $activityId])->with('success', 'Varian berhasil dihapus.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.course.activity.variant.index', [$course->slug, $activityId])->with('danger', 'Varian gagal disimpan.');
        }
    }
    private function assertActivity(Course $course, string $activityId): void
    {
        abort_unless(Activity::whereKey($activityId)->where('course_id', $course->id)
            ->whereIn('type', ['pre-test', 'post-test'])->exists(), 404);
    }

}
