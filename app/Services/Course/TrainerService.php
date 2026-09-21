<?php

namespace App\Services\Course;

use App\Helper\UploadMedia;
use App\Models\Course\Trainer;
use App\Repositories\Course\Contracts\TrainerRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class TrainerService
{
    public function __construct(private readonly TrainerRepositoryInterface $trainers) {}

    public function getForAdmin(): Collection
    {
        return $this->trainers->getLatestForAdmin();
    }

    /** Daftar trainer untuk form (select/checkbox). */
    public function getForForm(): Collection
    {
        return $this->trainers->getAllOrderedByName();
    }

    public function create(array $attributes): mixed
    {
        if (($attributes['photo'] ?? null) instanceof UploadedFile) {
            $attributes['photo'] = UploadMedia::file($attributes['photo'], Trainer::$directoryImage);
        } else {
            unset($attributes['photo']);
        }

        return $this->trainers->create($attributes);
    }

    public function update(mixed $trainer, array $attributes): mixed
    {
        if (($attributes['photo'] ?? null) instanceof UploadedFile) {
            if ($trainer->photo && is_file_exists(Trainer::$directoryImage.$trainer->photo)) {
                remove_file(Trainer::$directoryImage.$trainer->photo);
            }

            $attributes['photo'] = UploadMedia::file($attributes['photo'], Trainer::$directoryImage);
        } else {
            unset($attributes['photo']);
        }

        return $this->trainers->update($trainer, $attributes);
    }

    public function delete($id): bool
    {
        return $this->trainers->delete($id);
    }
}
