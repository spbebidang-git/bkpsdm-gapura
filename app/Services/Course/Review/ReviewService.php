<?php

namespace App\Services\Course\Review;

use App\Repositories\Course\Contracts\ReviewRepositoryInterface;

class ReviewService
{
    public function __construct(private readonly ReviewRepositoryInterface $reviews) {}

    public function getByAdmin()
    {
        return $this->reviews->getForAdmin()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'course' => $review->course->title ?? '-',
                    'user' => $review->user->name ?? '-',
                    'star' => $review->star,
                    'description' => $review->description,
                    'updated_at' => $review->updated_at,
                ];
            });
    }

    public function delete($id)
    {
        return $this->reviews->delete($id);
    }
}
