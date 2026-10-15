<?php

namespace App\Http\Controllers\Participant;

use App\Models\Course\Course;
use App\Models\Learning\ParticipantNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController
{
    public function count(Request $request)
    {
        return response()->json(['unread_count' => config('participant.notifications_enabled', true) ? $this->query($request)->whereNull('read_at')->count() : 0]);
    }

    public function latest(Request $request)
    {
        return response()->json([
            'notifications' => $this->presentMany($this->query($request)->latest()->limit(10)->get()),
            'unread_count' => config('participant.notifications_enabled', true) ? $this->query($request)->whereNull('read_at')->count() : 0,
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['filter' => ['nullable', 'in:all,unread']]);
        $query = $this->query($request)->latest();
        if (! config('participant.notifications_enabled', true)) $query->whereRaw('1 = 0');
        if (($filters['filter'] ?? 'all') === 'unread') $query->whereNull('read_at');

        return Inertia::render('Participant/Notifications/Index', [
            'notifications' => $this->presentPage($query->paginate(20)->withQueryString()),
            'filter' => $filters['filter'] ?? 'all',
            'unreadCount' => $this->query($request)->whereNull('read_at')->count(),
        ]);
    }

    public function read(Request $request, string $notification)
    {
        $this->query($request)->whereKey($notification)->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request)
    {
        $this->query($request)->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    private function query(Request $request)
    {
        return ParticipantNotification::query()->where('participant_id', $request->user()->id);
    }

    private function present(ParticipantNotification $item): array
    {
        $data = $item->data ?? [];
        $enrollment = $item->getRelation('notification_enrollment');
        $stale = $item->type === 'deadline' && (
            ! $enrollment || $enrollment->status !== 'active' || $enrollment->learn?->finished_at
            || ($enrollment->access_ends_at && $enrollment->access_ends_at->isPast())
        );
        $course = $item->getRelation('notification_course');

        return [
            'id' => $item->id,
            'type' => $item->type,
            'title' => $data['title'] ?? 'Notifikasi',
            'message' => $data['message'] ?? '',
            'read_at' => $item->read_at?->toISOString(),
            'created_at' => $item->created_at?->toISOString(),
            'deadline_at' => $data['deadline_at'] ?? null,
            'action_url' => $course ? route('participant.course.show', $course->slug) : null,
            'action_label' => $stale ? null : match ($item->type) {
                'completed' => 'Lihat hasil',
                'certificate' => 'Lihat sertifikat',
                'deadline' => 'Lanjutkan kelas',
                'grace', 'expired' => 'Lihat kelas',
                default => 'Lihat kelas',
            },
        ];
    }

    private function presentMany($items)
    {
        $this->loadEnrollments($items);
        return $items->map(fn ($item) => $this->present($item));
    }

    private function presentPage($page)
    {
        $this->loadEnrollments($page->getCollection());
        $page->setCollection($page->getCollection()->map(fn ($item) => $this->present($item)));
        return $page;
    }

    private function loadEnrollments($items): void
    {
        $ids = $items->map(fn ($item) => $item->data['enrollment_id'] ?? null)->filter()->unique();
        $enrollments = $ids->isNotEmpty()
            ? \App\Models\Learning\Enrollment::query()->with('learn')->whereIn('id', $ids)->get()->keyBy('id')
            : collect();
        $courseIds = $items->map(fn ($item) => $item->data['course_id'] ?? null)->filter()->unique();
        $courses = $courseIds->isNotEmpty() ? Course::query()->whereIn('id', $courseIds)->get()->keyBy('id') : collect();
        $items->each(function ($item) use ($enrollments, $courses) {
            $item->setRelation('notification_enrollment', $enrollments->get($item->data['enrollment_id'] ?? ''));
            $item->setRelation('notification_course', $courses->get($item->data['course_id'] ?? ''));
        });
    }
}
