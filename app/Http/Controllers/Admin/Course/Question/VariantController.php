<?php

namespace App\Http\Controllers\Admin\Course\Question;

use App\Http\Requests\Admin\VariantStoreRequest;
use App\Http\Requests\Admin\VariantUpdateRequest;
use App\Models\Course\Activity\Assessment\QuestionVariant;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\Question\VariantService;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class VariantController
{
    protected $variant;

    protected $activity;

    public function __construct(VariantService $variantService, ActivityService $activityService)
    {
        $this->variant = $variantService;
        $this->activity = $activityService;
    }

    public function index(Course $course, $activityId)
    {
        $this->assertActivity($course, $activityId);
        if (! $this->variant->getVariant($activityId)) {
            return to_route('admin.course.activities.index', $course->slug);
        }

        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($this->variant->getVariant($activityId))->make();
        }

        return Inertia::render('Instructor/Course/Variant/Index', [
            'canImport' => $this->variant->canImport($activityId),
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

    public function store(Course $course, $activityId, VariantStoreRequest $request)
    {
        $this->assertActivity($course, $activityId);
        $data = $request->validated();

        try {

            $this->variant->storeVariant($activityId, $data);

            return to_route('admin.course.activity.variant.index', [$course->slug, $activityId])->with('success', 'Varian berhasil ditambahkan.');
        } catch (\Throwable $th) {

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
            'variant' => $variant,
        ]);
    }

    public function update(Course $course, $activityId, QuestionVariant $variant, VariantUpdateRequest $request)
    {
        $this->assertActivity($course, $activityId);
        abort_unless($variant->question_id === $this->variant->getQuestionId($activityId), 404);
        $data = $request->validated();

        try {

            $this->variant->updateVariant($variant, [
                'mix' => $data['mix'],
                'code' => $data['code'],
                'variant' => $data['variant'],
            ]);

            return to_route('admin.course.activity.variant.index', [$course->slug, $activityId])->with('success', 'Varian berhasil diperbarui.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activity.variant.index', [$course->slug, $activityId])->with('danger', 'Varian gagal disimpan.');
        }
    }

    public function destroy(Course $course, $activityId, QuestionVariant $variant)
    {
        $this->assertActivity($course, $activityId);
        abort_unless($variant->question_id === $this->variant->getQuestionId($activityId), 404);
        try {

            $this->variant->deleteVariant($variant);

            return to_route('admin.course.activity.variant.index', [$course->slug, $activityId])->with('success', 'Varian berhasil dihapus.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activity.variant.index', [$course->slug, $activityId])->with('danger', 'Varian gagal disimpan.');
        }
    }

    private function assertActivity(Course $course, string $activityId): void
    {
        $this->variant->assertActivity($course, $activityId);
    }
}
