<?php

namespace App\Services\Learning;

use App\Models\Learning\Enrollment;
use App\Models\Learning\Learn;
use App\Models\Learning\NotificationMessageTemplate;
use App\Models\Learning\ParticipantNotification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ParticipantNotificationService
{
    private array $templateCache = [];

    public function create(string $eventKey, string $type, string $participantId, array $data): void
    {
        if (! config('participant.notifications_enabled', true)) return;
        ParticipantNotification::query()->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'participant_id' => $participantId,
            'event_key' => $eventKey,
            'type' => $type,
            'data' => json_encode($data, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function sendTest(string $eventKey, string $participantId, string $courseId): void
    {
        if (! config('participant.notifications_enabled', true)) {
            throw ValidationException::withMessages(['event_key' => 'Notifikasi pengguna sedang dinonaktifkan.']);
        }

        $course = \App\Models\Course\Course::query()->findOrFail($courseId);
        $copy = $this->copy($eventKey, $course->title);
        if (! $copy) throw ValidationException::withMessages(['event_key' => 'Jenis notifikasi tidak tersedia.']);

        $this->create('admin-test:'.Str::ulid(), $copy['type'], $participantId, [
            'title' => $copy['title'],
            'message' => $copy['message'],
            'course_id' => $course->id,
            'course_slug' => $course->slug,
            'test' => true,
            'occurred_at' => now()->toISOString(),
        ]);
    }

    public function completed(Learn $learn): void
    {
        $course = $learn->course;
        if (! $course) return;

        $copy = $this->copy('completed', $course->title);
        if (! $copy) return;

        $this->create('learn:'.$learn->id.':completed', $copy['type'], $learn->participant_id, [
            'title' => $copy['title'],
            'message' => $copy['message'],
            'course_id' => $course->id,
            'course_slug' => $course->slug,
            'learn_id' => $learn->id,
            'occurred_at' => ($learn->finished_at ?? now())->toISOString(),
        ]);
    }

    public function certificate(Learn $learn): void
    {
        $course = $learn->course;
        if (! $course || ! $this->validCertificateUrl($learn->url_certificate)) return;

        $copy = $this->copy('certificate', $course->title);
        if (! $copy) return;

        $this->create('learn:'.$learn->id.':certificate', $copy['type'], $learn->participant_id, [
            'title' => $copy['title'],
            'message' => $copy['message'],
            'course_id' => $course->id,
            'course_slug' => $course->slug,
            'learn_id' => $learn->id,
            'occurred_at' => now()->toISOString(),
        ]);
    }

    public function validCertificateUrl(?string $url): bool
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    public function deadline(Enrollment $enrollment, string $kind): void
    {
        $course = $enrollment->course;
        if (! $course) return;

        $copy = $this->copy($kind, $course->title);
        if (! $copy) return;

        $this->create('enrollment:'.$enrollment->id.':'.$kind, $copy['type'], $enrollment->participant_id, [
            'title' => $copy['title'],
            'message' => $copy['message'],
            'course_id' => $course->id,
            'course_slug' => $course->slug,
            'enrollment_id' => $enrollment->id,
            'deadline_at' => $enrollment->access_ends_at?->toISOString(),
            'occurred_at' => now()->toISOString(),
        ]);
    }

    /** @return array{type:string,title:string,message:string}|null */
    private function copy(string $eventKey, string $courseName): ?array
    {
        if (! array_key_exists($eventKey, $this->templateCache)) {
            $default = config("notification_messages.{$eventKey}");
            if (! is_array($default)) return null;

            $stored = NotificationMessageTemplate::query()->find($eventKey);
            $this->templateCache[$eventKey] = [
                'type' => $default['type'],
                'title' => $stored?->title ?? $default['title'],
                'message' => $stored?->message ?? $default['message'],
            ];
        }

        $copy = $this->templateCache[$eventKey];
        $replacements = [':course_name' => $courseName];

        return [
            'type' => $copy['type'],
            'title' => str_replace(array_keys($replacements), array_values($replacements), $copy['title']),
            'message' => str_replace(array_keys($replacements), array_values($replacements), $copy['message']),
        ];
    }
}
