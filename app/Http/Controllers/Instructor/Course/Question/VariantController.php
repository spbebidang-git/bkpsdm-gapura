<?php

namespace App\Http\Controllers\Instructor\Course\Question;

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

    public function index($slug, $activityId)
    {
        if (!$this->variant->getVariant($activityId)) {
            return to_route('instructor.course.show', $slug);
        }

        return Inertia::render('Instructor/Course/Variant/Index', [
            'slug' => $slug,
            'activity' => $this->activity->getDetailActivity($activityId),
            'variants' => $this->variant->getVariant($activityId)
        ]);
    }

    public function create($slug, $activityId)
    {
        return Inertia::render('Instructor/Course/Variant/Create', [
            'slug' => $slug,
            'activityId' => $activityId,
        ]);
    }

    public function store($slug, $activityId, Request $request)
    {
        try {
            DB::beginTransaction();

            $this->variant->createVariant($slug, $activityId, $request->all());

            DB::commit();

            return to_route('instructor.course.activity.variant.index', [$slug, $activityId]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.activity.variant.index', [$slug, $activityId])->with('danger', 'Something went wrong.');
        }
    }

    public function edit($slug, $activityId, QuestionVariant $variant)
    {
        return Inertia::render('Instructor/Course/Variant/Edit', [
            'slug' => $slug,
            'activityId' => $activityId,
            'variant' => $variant
        ]);
    }

    public function update($slug, $activityId, QuestionVariant $variant, Request $request)
    {
        try {
            DB::beginTransaction();

            $variant->update([
                'mix' => $request->mix,
                'loop' => $request->loop,
            ]);

            DB::commit();

            return to_route('instructor.course.activity.variant.index', [$slug, $activityId]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.activity.variant.index', [$slug, $activityId])->with('danger', 'Something went wrong.');
        }
    }

    public function destroy($slug, $activityId, QuestionVariant $variant)
    {
        try {
            DB::beginTransaction();

            $variant->delete();

            DB::commit();

            return to_route('instructor.course.activity.variant.index', [$slug, $activityId]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.activity.variant.index', [$slug, $activityId])->with('danger', 'Something went wrong.');
        }
    }
}
