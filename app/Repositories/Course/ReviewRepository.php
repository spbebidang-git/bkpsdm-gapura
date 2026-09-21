<?php

namespace App\Repositories\Course;

use App\Models\Course\Review;
use App\Repositories\Course\Contracts\ReviewRepositoryInterface;
use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;

class ReviewRepository extends EloquentRepository implements ReviewRepositoryInterface
{
    public function __construct(Review $model)
    {
        parent::__construct($model);
    }

    public function getByCourse(int|string $courseId): Collection
    {
        return $this->query()->with('user')->where('course_id', $courseId)->latest()->get();
    }

    public function getForAdmin(): Collection
    {
        return $this->query()->with(['user', 'course'])->latest()->get();
    }
}
