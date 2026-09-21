<?php

namespace App\Repositories\Course\Contracts;

use App\Models\Course\Course;
use App\Repositories\Contracts\BaseRepositoryInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

interface CourseRepositoryInterface extends BaseRepositoryInterface
{
    public function findBySlug(string $slug, array $relations = []): ?Course;

    public function findPublishedBySlug(string $slug, array $relations = []): ?Course;

    public function getPublishedAvailable(?DateTimeInterface $at = null, ?int $limit = null): Collection;

    public function getPublishedActiveAt(DateTimeInterface $at, int $limit = 8): Collection;

    public function getForInstructor(int|string|null $instructorId = null): Collection;

    public function getByParticipant(int|string $participantId): Collection;

    public function syncEmployeeStatuses(Course $course, array $employeeStatusIds): void;

    public function syncTrainers(Course $course, array $trainerIds): void;
}
