<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Requests\Management\NotificationTemplateRequest;
use App\Http\Requests\Management\NotificationTestRequest;
use App\Services\Learning\NotificationTemplateService;
use App\Services\Learning\ParticipantNotificationService;
use Inertia\Inertia;

class NotificationMessageController
{
    public function __construct(private readonly NotificationTemplateService $templates) {}

    public function index()
    {
        return Inertia::render('Admin/Management/NotificationMessage/Index', [
            'templates' => $this->templates->templates(),
            'participants' => $this->templates->participants(),
            'courses' => $this->templates->courses(),
        ]);
    }

    public function test(NotificationTestRequest $request, ParticipantNotificationService $notifications)
    {
        $data = $request->validated();
        $this->templates->assertParticipant($data['participant_id']);

        $notifications->sendTest($data['event_key'], $data['participant_id'], $data['course_id']);

        return to_route('admin.management.notification-messages.index')->with('success', 'Notifikasi uji berhasil dikirim ke pengguna.');
    }

    public function update(NotificationTemplateRequest $request)
    {
        $this->templates->save($request->validated());

        return to_route('admin.management.notification-messages.index')
            ->with('success', 'Pesan notifikasi berhasil disimpan.');
    }
}
