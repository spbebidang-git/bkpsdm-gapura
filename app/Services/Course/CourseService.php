<?php

namespace App\Services\Course;

use App\Helper\UploadMedia;
use App\Models\Course\Course;
use App\Models\Course\Schedule;

class CourseService
{
    public function getRandomCourse()
    {
        return Course::inRandomOrder()
            ->limit(8)
            ->where('status', 'publish')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => $row->category->title,
                    'instructor' => $row->instructor->name,
                    'total_participant' => $row->total_participant,
                    'status' => $row->status,
                    'star' => $row->getRating()['star']
                ];
            });
    }

    public function getAllCourse()
    {
        return Course::OrderBy('id')
            ->where('status', 'publish')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => $row->category->title,
                    'instructor' => $row->instructor->name,
                    'total_participant' => $row->total_participant,
                    'status' => $row->status,
                    'star' => $row->getRating()['star']
                ];
            });
    }

    public function getByInstructor($instructodId)
    {
        return Course::where('instructor_id', $instructodId)
            // ->with('schedules')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => $row->category->title,
                    'instructor' => $row->instructor->name,
                    'total_participant' => $row->total_participant,
                    'status' => $row->status,
                ];
            });
    }

    public function getByParticipant($participantId)
    {
        return Schedule::whereRelation('participants', 'participant_id', '=', $participantId)
            ->with(['course', 'course.learns' => function ($query) use ($participantId) {
                $query->where('participant_id', $participantId);
            }, 'course.activities'])
            ->get()
            ->map(function ($row) {
                // dd($row->course->activities->first()->id);
                return [
                    'id' => $row->course->id,
                    'type' => $row->course->type,
                    'title' => $row->course->title,
                    'slug' => $row->course->slug,
                    'quota' => $row->course->quota,
                    'thumbnail' => $row->course->thumbnail_src,
                    'short_description' => $row->course->short_description,
                    'description' => $row->course->description,
                    'category' => $row->course->category->title,
                    'instructor' => $row->course->instructor->name,
                    'total_participant' => $row->course->total_participant,
                    'status' => $row->course->status,
                    'schedule_id' => 1,
                    'initial_activity' => $row->course->activities->first() ? $row->course->activities->first()->id : 0,
                    'finished_at' => $row->course->learns->first()->finished_at ?? null
                ];
            });
    }

    public function getByAdmin()
    {
        return Course::get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'slug' => $row->slug,
                    'quota' => $row->quota,
                    'thumbnail' => $row->thumbnail_src,
                    'category' => $row->category->title,
                    'instructor' => $row->instructor->name,
                    'total_participant' => $row->total_participant,
                    'status' => $row->status,
                ];
            });
    }

    public function findBySlug($slug)
    {
        return Course::where('slug', $slug)
            ->with('instructor', 'reviews')
            ->first();
    }

    public function createCourse($instructorId, $data, $schedules = null)
    {
        # Create Course
        $course = Course::create([
            'category_id' => $data['category'],
            'instructor_id' => $instructorId,
            'type' => $data['type'],
            'title' => $data['title'],
            'slug' => str()->slug($data['title']),
            'short_description' => $data['short_description'],
            'description' => $data['description'],
            'thumbnail' => UploadMedia::file($data['thumbnail'], Course::$directoryImage),
            'teaser_video' => $data['teaser_video'],
            // 'outline_topic' => $data['outline_topic'],
            // 'status' => $data->status,
        ]);

        # Create Schedule
        if ($schedules) {
            foreach ($schedules as $item) {
                Schedule::create([
                    'course_id' => $course->id,
                    'date' => $item['date'],
                    'time_from' => $item['time_from'],
                    'time_to' => $item['time_to'],
                    'quota' => $item['quota'],
                ]);
            }

            return true;
        }

        Schedule::create([
            'course_id' => $course->id
        ]);

        return true;
    }

    public function updateCourse($course, $instructorId, $data, $schedules = null)
    {
        // dd($data['status']);
        $course->update([
            'category_id' => $data['category'],
            'type' => $data['type'],
            'title' => $data['title'],
            'slug' => str()->slug($data['title']),
            'short_description' => $data['short_description'],
            'description' => $data['description'],
            'teaser_video' => $data['teaser_video'],
            // 'outline_topic' => $data['outline_topic'],
            'status' => $data['status'],
        ]);

        if ($data['thumbnail']) {
            // $course->thumbnail => UploadMedia::file($data['thumbnail'], Course::$directoryImage)

        }

        if ($data['thumbnail']) {
            remove_file(Course::$directoryImage . $course->thumbnail);

            $course->thumbnail = UploadMedia::file($data['thumbnail'], Course::$directoryImage);
            $course->save();
        }
    }
}
