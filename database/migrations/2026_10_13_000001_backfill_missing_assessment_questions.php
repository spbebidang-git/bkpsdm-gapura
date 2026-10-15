<?php

use App\Models\Course\Activity\Activity;
use App\Models\Course\Activity\Assessment\Question;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Activity::query()->whereIn('type', ['pre-test', 'post-test'])
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('questions')
                    ->whereColumn('questions.activity_id', 'activities.id');
            })->each(function (Activity $activity) {
                Question::create([
                    'activity_id' => $activity->id,
                    'description' => $activity->title,
                    'type' => $activity->type === 'pre-test' ? 'pre' : 'post',
                    'min_score' => 0,
                    'is_loop' => 0,
                ]);
            });
    }

    public function down(): void
    {
        // Keep recovered settings and any variants subsequently added by admins.
    }
};
