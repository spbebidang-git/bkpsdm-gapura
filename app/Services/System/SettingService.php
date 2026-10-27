<?php

namespace App\Services\System;

use App\Helper\UploadMedia;
use App\Models\System\Setting;
use App\Repositories\System\Contracts\SettingRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SettingService
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    public function get(string $key): mixed
    {
        return $this->settings->findByKey($key)?->value ?: false;
    }

    public function put(string $key, mixed $value): Setting
    {
        $setting = $this->settings->findByKey($key, true);
        if ($setting) {
            if ($setting->trashed()) $this->settings->restore($setting);
            return $this->settings->update($setting, ['value' => $value]);
        }
        return $this->settings->create(['key' => $key, 'value' => $value]);
    }

    public function saveSiteSettings(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $this->put('site_title', $data['site_title']);
            $this->put('site_description', $data['site_description']);
            $path = public_path('assets/media/images/managements/settings');
            File::ensureDirectoryExists($path);
            foreach (['site_logo', 'home_banner'] as $key) {
                if (isset($data[$key])) $this->put($key, UploadMedia::file($data[$key], $path.DIRECTORY_SEPARATOR));
            }
        });
    }
}
