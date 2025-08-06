<?php

namespace App\Services\Course;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleUserService
{
    public function getActivationDate($scheduleId, $participantId)
    {
        $query = DB::table('schedule_user')
            ->where('schedule_id', $scheduleId)
            ->where('participant_id', $participantId)
            ->first();

        return Carbon::parse($query->created_at)->format('Y-m-d');
    }
}
