<?php

namespace App\Services\Support;

use App\Repositories\Support\Contracts\HelpRepositoryInterface;
use Illuminate\Support\Collection;

class HelpService
{
    public function __construct(private readonly HelpRepositoryInterface $helps) {}

    public function getForAdmin(): Collection
    {
        return $this->helps->getLatestForAdmin();
    }

    public function getForPortal(): Collection
    {
        return $this->helps->getActive()
            ->map(fn ($help) => $help->only(['id', 'question', 'answer']))
            ->values();
    }

    public function create(array $attributes): mixed
    {
        return $this->helps->create($attributes);
    }

    public function update(mixed $help, array $attributes): mixed
    {
        return $this->helps->update($help, $attributes);
    }

    public function delete($id): bool
    {
        return $this->helps->delete($id);
    }
}