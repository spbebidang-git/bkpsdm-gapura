<?php

namespace App\Services\Learning;

use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use App\Repositories\Learning\Contracts\NotificationMessageTemplateRepositoryInterface;
use Illuminate\Support\Facades\DB;

class NotificationTemplateService
{
    public function __construct(
        private readonly NotificationMessageTemplateRepositoryInterface $templates,
        private readonly UserRepositoryInterface $users,
        private readonly CourseRepositoryInterface $courses,
    ) {}

    public function templates()
    {
        $stored = $this->templates->allByEventKey();
        return collect(config('notification_messages', []))->map(fn (array $template, string $key) => [
            'event_key' => $key, 'label' => $template['label'], 'group' => $template['group'],
            'title' => $stored->get($key)?->title ?? $template['title'],
            'message' => $stored->get($key)?->message ?? $template['message'],
        ])->values();
    }

    public function participants()
    {
        return $this->users->queryByRole('participant')->get(['id', 'name', 'nip']);
    }

    public function courses()
    {
        return $this->courses->getPublishedOptions();
    }

    public function assertParticipant(string $id): void
    {
        abort_unless($this->users->queryByRole('participant')->whereKey($id)->exists(), 422);
    }

    public function save(array $data): void
    {
        DB::transaction(function () use ($data): void {
            foreach (array_keys(config('notification_messages', [])) as $key) {
                $this->templates->put($key, [
                    'title' => $data['templates'][$key]['title'],
                    'message' => $data['templates'][$key]['message'],
                ]);
            }
        });
    }
}
