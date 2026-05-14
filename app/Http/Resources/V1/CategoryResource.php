<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'custom_unique_id' => Str::random(4),
            'data_date' => now()->format('Y-m-d H:i:s'),
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'slug' => $this->slug,
            'is_primary' => (bool) $this->is_primary,
            'courses_count' => $this->when(isset($this->courses_count), $this->courses_count),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
