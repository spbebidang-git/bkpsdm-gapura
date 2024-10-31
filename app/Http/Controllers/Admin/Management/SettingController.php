<?php

namespace App\Http\Controllers\Admin\Management;

use App\Helper\UploadMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController
{
    public function index()
    {
        return view('admin.setting.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'site_title' => ['required', 'string',],
            'site_description' => ['required', 'string',],
            'site_logo' => ['nullable', 'image', 'mimes:png,jpg', 'max:10240'],
        ]);

        try {
            DB::beginTransaction();

            update_setting_meta('site_title', $request->site_title);
            update_setting_meta('site_description', $request->site_description);
            if ($request->site_logo) {
                update_setting_meta('site_logo', UploadMedia::file($request->site_logo, 'assets/media/images/managements/settings/'));
            }

            DB::commit();

            return redirect()->route('admin.management.setting.index')->with('success', 'Setting has been saved.');
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return redirect()->route('admin.management.setting.index')->with('danger', 'Something went wrong.');
        }
    }
}
