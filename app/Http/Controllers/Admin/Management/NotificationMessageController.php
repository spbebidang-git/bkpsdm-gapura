<?php

namespace App\Http\Controllers\Admin\Management;

use App\Models\Learning\NotificationMessageTemplate;
use Illuminate\Http\Request;
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
        ]);
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
