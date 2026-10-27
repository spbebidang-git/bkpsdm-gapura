<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Requests\Management\SettingRequest;
use App\Services\System\SettingService;
use Inertia\Inertia;

class SettingController
{
    public function __construct(private readonly SettingService $settings) {}

    public function index()
    {
        $logo = $this->settings->get('site_logo');

        return Inertia::render('Admin/Management/Setting/Index', [
            'settings' => [
                'site_title' => $this->settings->get('site_title') ?: '',
                'site_description' => $this->settings->get('site_description') ?: '',
                'site_logo_url' => $logo
                    ? asset('assets/media/images/managements/settings/'.$logo)
                    : null,
            ],
        ]);
    }

    public function store(SettingRequest $request)
    {
        try {
            $this->settings->saveSiteSettings($request->validated());

            return redirect()->route('admin.management.setting.index')->with('success', 'Setting has been saved.');
        } catch (\Throwable) {
            return redirect()->route('admin.management.setting.index')->with('danger', 'Something went wrong.');
        }
    }
}
