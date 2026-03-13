<?php

namespace App\Http\Controllers\Instructor\Course\Question;

use App\Models\Course\Course;
use App\Models\Course\Question\QuestionVariant;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\Question\VariantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
        if (!$this->variant->getVariant($activityId)) {
            return to_route('instructor.course.show', $course->slug);
        }

        return Inertia::render('Instructor/Course/Variant/Index', [
            'slug' => $course->slug,
            'activity' => $this->activity->getDetailActivity($activityId),
            'variants' => $this->variant->getVariant($activityId),
            'variantWarning' => $this->variant->getVariantWarning($activityId),
        ]);
    }

    public function create(Course $course, $activityId)
    {
        return Inertia::render('Instructor/Course/Variant/Create', [
            'slug' => $course->slug,
            'variant' => $this->variant->generateVariantNumber($activityId),
            'activityId' => $activityId,
        ]);
    }

    public function store(Course $course, $activityId, Request $request)
    {
        try {
            DB::beginTransaction();

            $this->variant->createVariant($activityId, $request->all());

            DB::commit();

            return to_route('instructor.course.activity.variant.index', [$course->slug, $activityId])->with('success', 'Variant created successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.activity.variant.index', [$course->slug, $activityId])->with('danger', 'Something went wrong.');
        }
    }

    public function edit(Course $course, $activityId, QuestionVariant $variant)
    {
        return Inertia::render('Instructor/Course/Variant/Edit', [
            'slug' => $course->slug,
            'activityId' => $activityId,
            'variant' => $variant
        ]);
    }

    public function update(Course $course, $activityId, QuestionVariant $variant, Request $request)
    {
        try {
            DB::beginTransaction();

            $variant->update([
                'mix' => $request->mix,
                'code' => $request->code,
                'variant' => $request->variant,
            ]);

            DB::commit();

            return to_route('instructor.course.activity.variant.index', [$course->slug, $activityId])->with('success', 'Variant updated successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.activity.variant.index', [$course->slug, $activityId])->with('danger', 'Something went wrong.');
        }
    }

    public function destroy(Course $course, $activityId, QuestionVariant $variant)
    {
        try {
            DB::beginTransaction();

            $variant->delete();

            DB::commit();

            return to_route('instructor.course.activity.variant.index', [$course->slug, $activityId]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.activity.variant.index', [$course->slug, $activityId])->with('danger', 'Something went wrong.');
        }
    }
}
