<?php

namespace App\Services\Learning;

use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use App\Repositories\Learning\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Learning\Contracts\ParticipantNotificationRepositoryInterface;

class NotificationInboxService
{
    public function __construct(
        private readonly ParticipantNotificationRepositoryInterface $notifications,
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly CourseRepositoryInterface $courses,
    ) {}

    public function unreadCount(string $participantId): int
    {
        return config('participant.notifications_enabled', true)
            ? $this->notifications->unreadCount($participantId) : 0;
    }

    public function latest(string $participantId)
    {
        return $this->hydrate($this->notifications->latestFor($participantId));
    }

    public function page(string $participantId, bool $unread)
    {
        $page = $this->notifications->pageFor($participantId, $unread, 20, config('participant.notifications_enabled', true));
        $page->setCollection($this->hydrate($page->getCollection()));
        return $page;
    }

    public function markRead(string $participantId, ?string $id = null): void
    {
        $this->notifications->markRead($participantId, $id);
    }

    private function hydrate($items)
    {
        $ids = $items->map(fn ($item) => $item->data['enrollment_id'] ?? null)->filter()->unique()->all();
        $enrollments = $ids ? $this->enrollments->withLearnByIds($ids)->keyBy('id') : collect();
        $courseIds = $items->map(fn ($item) => $item->data['course_id'] ?? null)->filter()->unique()->all();
        $courses = $courseIds ? $this->courses->findMany($courseIds)->keyBy('id') : collect();
        $items->each(function ($item) use ($enrollments, $courses): void {
            $item->setRelation('notification_enrollment', $enrollments->get($item->data['enrollment_id'] ?? ''));
            $item->setRelation('notification_course', $courses->get($item->data['course_id'] ?? ''));
        });
        return $items;
    }
}
