<?php

namespace App\Repositories\Learning\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface NotificationMessageTemplateRepositoryInterface extends BaseRepositoryInterface
{
    public function allByEventKey(): Collection;

    public function put(string $eventKey, array $attributes): void;
}
