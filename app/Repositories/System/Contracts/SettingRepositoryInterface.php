<?php

namespace App\Repositories\System\Contracts;

use App\Models\System\Setting;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface SettingRepositoryInterface extends BaseRepositoryInterface
{
    public function findByKey(string $key, bool $withTrashed = false): ?Setting;

    public function restore(Setting $setting): Setting;
}
