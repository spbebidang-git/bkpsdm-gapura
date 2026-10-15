<?php

namespace App\Repositories\Course\Contracts;

use App\Models\Course\Course;
use App\Repositories\Contracts\BaseRepositoryInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Identity\User;

interface CourseRepositoryInterface extends BaseRepositoryInterface
{
    public function findBySlug(string $slug, array $relations = []): ?Course;

    public function findPublishedBySlug(string $slug, array $relations = []): ?Course;

    public function getPublishedAvailable(?DateTimeInterface $at = null, ?int $limit = null, array $filters = [], ?User $user = null): Collection;

    public function getFeaturedPublishedCourses(DateTimeInterface $at, int $limit = 6, ?User $user = null): Collection;

    public function findVisibleBySlug(string $slug, ?User $user = null, array $relations = []): ?Course;

    public function getForInstructor(int|string|null $instructorId = null): Collection;

    public function getByParticipant(int|string $participantId): Collection;

    public function findForManagementOverview(int|string $courseId): ?Course;

    public function findForManagementDetail(int|string $courseId): ?Course;

    public function isManagedByTrainer(Course $course, int|string $userId): bool;

    public function syncEmployeeStatuses(Course $course, array $employeeStatusIds): void;

    public function syncTrainers(Course $course, array $trainerIds): void;
}
