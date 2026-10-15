<?php

namespace App\Http\Controllers\Admin\Management;

use App\Helper\UploadMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
            'home_banner' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:15360'],
        ]);

        try {
            DB::beginTransaction();

            update_setting_meta('site_title', $request->site_title);
            update_setting_meta('site_description', $request->site_description);
            $settingsPath = public_path('assets/media/images/managements/settings');
            File::ensureDirectoryExists($settingsPath);

            if ($request->site_logo) {
                update_setting_meta(
                    'site_logo',
                    UploadMedia::file($request->site_logo, $settingsPath.DIRECTORY_SEPARATOR)
                );
            }

            if ($request->home_banner) {
                update_setting_meta(
                    'home_banner',
                    UploadMedia::file($request->home_banner, $settingsPath.DIRECTORY_SEPARATOR)
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
