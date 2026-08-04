public function syncPush()
{
    if (request()->boolean('rollback')) {
        $courseId = request()->input('course_id');
        $learnId  = request()->input('learn_id');

        $query = Learn::where('is_post_certificate', 1);
        if ($learnId) $query->where('id', $learnId);
        elseif ($courseId) $query->where('course_id', $courseId);

        $query->update(['is_post_certificate' => 0]);
        return response()->json(['message' => 'Rollback selesai. is_post_certificate dibalik ke 0'], 200);
    }

    // original code...
}