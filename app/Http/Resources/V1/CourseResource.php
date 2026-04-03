<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CourseResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'status' => $this->status,
            'short_description' => $this->short_description,
            'description' => $this->when($request->routeIs('api.v1.courses.show'), $this->description),
            'thumbnail' => $this->thumbnail_src,
            'jam_pelajaran' => $this->jam_pelajaran,
            'outline_topic' => $this->when($request->routeIs('api.v1.courses.show'), $this->outline_topic),
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'title' => $this->category->title,
                'code' => $this->category->code,
            ]),
            'instructor' => $this->whenLoaded('instructor', fn() => [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
            ]),
            'employee_status' => $this->whenLoaded('employeeStatus', fn() =>
                $this->employeeStatus->pluck('title')
            ),
            'schedules' => $this->whenLoaded('schedules', fn() =>
                $this->schedules->map(fn($s) => [
                    'id' => $s->id,
                    'date' => $s->getRawOriginal('date'),
                    'time_from' => $s->time_from,
                    'time_to' => $s->time_to,
                    'quota' => $s->getRawOriginal('quota'),
                ])
            ),
            'activities' => $this->whenLoaded('activities', fn() =>
                $this->activities->map(fn($a) => [
                    'id' => $a->id,
                    'type' => $a->type,
                    'title' => $a->title,
                    'value' => $a->value,
                ])
            ),
            'total_participant' => $this->total_participant,
            'total_passed' => $this->total_passed,
            'efektifitas' => $this->efektifitas . '%',
            'rating' => $this->getRating(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
