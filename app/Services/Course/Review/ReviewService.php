<?php

namespace App\Services\Course\Review;

use App\Models\Course\Review;

class ReviewService
{
    public function getByAdmin()
    {
        return Review::query()
            ->with(['course:id,title', 'user:id,name'])
            ->latest()
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'course' => $review->course->title ?? '-',
                    'user' => $review->user->name ?? '-',
                    'star' => $review->star,
                    'description' => $review->description,
                ];
            });
    }

    public function delete($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();
        return true;
    }
}
