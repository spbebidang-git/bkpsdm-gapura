<?php

namespace App\Http\Controllers\Admin\Management;

use App\Helper\UploadMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SettingController
{
    public function index()
    {
        $logo = get_setting_meta('site_logo');

        return Inertia::render('Admin/Management/Setting/Index', [
            'settings' => [
                'site_title' => get_setting_meta('site_title') ?: '',
                'site_description' => get_setting_meta('site_description') ?: '',
                'site_logo_url' => $logo
                    ? asset('assets/media/images/managements/settings/'.$logo)
                    : null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'site_title' => ['required', 'string'],
            'site_description' => ['required', 'string'],
            'site_logo' => ['nullable', 'image', 'mimes:png,jpg', 'max:10240'],
        ]);

        try {
            DB::beginTransaction();

            update_setting_meta('site_title', $request->site_title);
            update_setting_meta('site_description', $request->site_description);
            if ($request->site_logo) {
                update_setting_meta(
                    'site_logo',
                    UploadMedia::file($request->site_logo, 'assets/media/images/managements/settings/')
                );
            }

            DB::commit();

            return redirect()->route('admin.management.setting.index')->with('success', 'Setting has been saved.');
        } catch (\Throwable) {
            DB::rollBack();

            return redirect()->route('admin.management.setting.index')->with('danger', 'Something went wrong.');
        }
    }
}
