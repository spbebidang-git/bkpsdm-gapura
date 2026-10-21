<?php

namespace App\Http\Controllers\Admin\Management;

use App\Models\Learning\NotificationMessageTemplate;
use App\Models\Course\Course;
use App\Models\Identity\User;
use App\Services\Learning\ParticipantNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class NotificationMessageController
{
    public function index()
    {
        $stored = NotificationMessageTemplate::query()->get()->keyBy('event_key');
        $templates = collect(config('notification_messages', []))
            ->map(fn (array $template, string $eventKey) => [
                'event_key' => $eventKey,
                'label' => $template['label'],
                'group' => $template['group'],
                'title' => $stored->get($eventKey)?->title ?? $template['title'],
                'message' => $stored->get($eventKey)?->message ?? $template['message'],
            ])
            ->values();

        return Inertia::render('Admin/Management/NotificationMessage/Index', [
            'templates' => $templates,
            'participants' => User::query()->role('participant')->orderBy('name')->get(['id', 'name', 'nip']),
            'courses' => Course::query()->where('status', 'publish')->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function test(Request $request, ParticipantNotificationService $notifications)
    {
        $eventKeys = array_keys(config('notification_messages', []));
        $data = $request->validate([
            'participant_id' => ['required', 'string', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'course_id' => ['required', 'string', Rule::exists('courses', 'id')->where('status', 'publish')->whereNull('deleted_at')],
            'event_key' => ['required', Rule::in($eventKeys)],
        ]);
        abort_unless(User::query()->whereKey($data['participant_id'])->role('participant')->exists(), 422);

        $notifications->sendTest($data['event_key'], $data['participant_id'], $data['course_id']);

        return to_route('admin.management.notification-messages.index')->with('success', 'Notifikasi uji berhasil dikirim ke peserta.');
    }

    public function update(Request $request)
    {
        $defaults = config('notification_messages', []);
        $eventKeys = array_keys($defaults);
        $rules = [
            'templates' => ['required', 'array:'.implode(',', $eventKeys)],
        ];

        foreach ($eventKeys as $eventKey) {
            $rules["templates.{$eventKey}.title"] = ['required', 'string', 'max:160'];
            $rules["templates.{$eventKey}.message"] = ['required', 'string', 'max:2000'];
        }

        $validated = $request->validate($rules);

        foreach ($eventKeys as $eventKey) {
            NotificationMessageTemplate::query()->updateOrCreate(
                ['event_key' => $eventKey],
                [
                    'title' => $validated['templates'][$eventKey]['title'],
                    'message' => $validated['templates'][$eventKey]['message'],
                ],
            );
        }

        return to_route('admin.management.notification-messages.index')
            ->with('success', 'Pesan notifikasi berhasil disimpan.');
    }
}
