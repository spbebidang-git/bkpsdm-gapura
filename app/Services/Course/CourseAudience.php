<?php

namespace App\Services\Course;

use App\Models\Course\Course;
use App\Models\Course\Trainer;
use App\Models\Identity\User;
use Illuminate\Database\Eloquent\Builder;

class CourseAudience
{
    public function registered(Builder $query, User $user): void
    {
        $query->where(function (Builder $registered) use ($user) {
            $registered->whereHas('enrollments', fn ($q) => $q->where('participant_id', $user->id))
                ->orWhereHas('schedules.participants', fn ($q) => $q->where('users.id', $user->id));
        });
    }

    public function isRegistered(Course $course, User $user): bool
    {
        return Course::query()->whereKey($course->id)->where(fn ($q) => $this->registered($q, $user))->exists();
    }

    public function employeeStatus(User $user): ?string
    {
        $status = $user->employeeStatus?->title ?? get_user_meta($user->id, 'status_pegawai');
        return filled($status) ? mb_strtoupper(trim($status)) : null;
    }

    public function matchesStatus(Course $course, User $user): bool
    {
        $statuses = $course->employeeStatus->pluck('title')->map(fn ($title) => mb_strtoupper(trim($title)));
        if ($statuses->isEmpty()) return $course->visibility === 'public';
        $status = $this->employeeStatus($user);
        return $status !== null && $statuses->contains($status);
    }

    public function visible(Builder $query, ?User $user): void
    {
        if ($user?->hasAnyRole(['super_admin', 'administrator'])) return;
        $query->where(function (Builder $audience) use ($user) {
            $audience->where('courses.visibility', 'public');
            if (! $user) return;
            $audience->orWhere(fn ($q) => $this->registered($q, $user));
            $status = $this->employeeStatus($user);
            if (! $user->opd?->is_active || ! $status) return;
            $audience->orWhere(function ($private) use ($user, $status) {
                $private->where('courses.visibility', 'private')
                    ->whereHas('opds', fn ($q) => $q->where('opds.id', $user->opd_id)->where('is_active', true))
                    ->whereHas('employeeStatus', fn ($q) => $q->whereRaw('UPPER(TRIM(title)) = ?', [$status]));
            });
        });
    }

    public function viewable(Builder $query, ?User $user): void
    {
        $query->where(function ($published) use ($user) {
            $published->where('courses.status', 'publish');
            if ($user) $published->orWhere(fn ($q) => $this->registered($q, $user));
        });
        $this->visible($query, $user);
    }

    public function canEnroll(Course $course, User $user): bool
    {
        if ($this->isRegistered($course, $user)) return true;
        if ($course->status !== 'publish' || ! $this->matchesStatus($course, $user)) return false;
        return $course->visibility === 'public'
            || ($course->visibility === 'private' && $user->opd?->is_active
                && $course->opds()->where('opds.id', $user->opd_id)->where('is_active', true)->exists());
    }

    public function canManage(Course $course, User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'administrator'])
            || (Trainer::activeOpdIdsForUser($user->id)
                && $course->trainers()->where('user_id', $user->id)->exists());
    }
}
