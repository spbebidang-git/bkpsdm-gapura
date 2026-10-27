<?php

namespace App\Repositories\Learning;

use App\Models\Learning\NotificationMessageTemplate;
use App\Repositories\EloquentRepository;
use App\Repositories\Learning\Contracts\NotificationMessageTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class NotificationMessageTemplateRepository extends EloquentRepository implements NotificationMessageTemplateRepositoryInterface
{
    public function __construct(NotificationMessageTemplate $model) { parent::__construct($model); }

    public function allByEventKey(): Collection
    {
        return $this->query()->get()->keyBy('event_key');
    }

    public function put(string $eventKey, array $attributes): void
    {
        $this->query()->updateOrCreate(['event_key' => $eventKey], $attributes);
    }
}
