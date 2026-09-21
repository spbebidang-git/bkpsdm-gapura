<?php

namespace App\Repositories\System;

use App\Models\System\Setting;
use App\Repositories\EloquentRepository;
use App\Repositories\System\Contracts\SettingRepositoryInterface;

class SettingRepository extends EloquentRepository implements SettingRepositoryInterface
{
    public function __construct(Setting $model)
    {
        parent::__construct($model);
    }

    public function findByKey(string $key, bool $withTrashed = false): ?Setting
    {
        $query = $this->query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->where('key', $key)->first();
    }

    public function restore(Setting $setting): Setting
    {
        $setting->restore();

        return $setting->refresh();
    }
}
