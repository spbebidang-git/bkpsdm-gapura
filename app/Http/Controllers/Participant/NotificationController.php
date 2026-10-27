<?php

namespace App\Http\Controllers\Participant;

use App\Models\Learning\ParticipantNotification;
use Illuminate\Http\Request;
use App\Http\Requests\Participant\NotificationIndexRequest;
use App\Services\Learning\NotificationInboxService;
use Inertia\Inertia;

class NotificationController
{
    public function __construct(private readonly NotificationInboxService $inbox) {}

    public function count(Request $request)
    {
        return response()->json(['unread_count' => $this->inbox->unreadCount((string) $request->user()->id)]);
    }

    public function latest(Request $request)
    {
        return response()->json([
            'notifications' => $this->presentMany($this->inbox->latest((string) $request->user()->id)),
            'unread_count' => $this->inbox->unreadCount((string) $request->user()->id),
        ]);
    }

    public function index(NotificationIndexRequest $request)
    {
        $filters = $request->validated();

        return Inertia::render('Participant/Notifications/Index', [
            'notifications' => $this->presentPage($this->inbox->page((string) $request->user()->id, ($filters['filter'] ?? 'all') === 'unread')),
            'filter' => $filters['filter'] ?? 'all',
            'unreadCount' => $this->inbox->unreadCount((string) $request->user()->id),
        ]);
    }

    public function read(Request $request, string $notification)
    {
        $this->inbox->markRead((string) $request->user()->id, $notification);
        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request)
    {
        $this->inbox->markRead((string) $request->user()->id);
        return response()->json(['ok' => true]);
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
        return $items->map(fn ($item) => $this->present($item));
    }

    private function presentPage($page)
    {
        $page->setCollection($page->getCollection()->map(fn ($item) => $this->present($item)));
        return $page;
    }

}
