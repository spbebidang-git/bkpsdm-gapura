<?php

namespace App\Http\Controllers;

use App\Models\Course\H5p;
use App\Models\Course\Scorm;
use App\Models\Learn\ActivityRuntimeState;
use App\Models\Learn\Learn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityRuntimeStateController
{
    public function storeScorm(Request $request, string $folder): JsonResponse
    {
        abort_unless((bool) preg_match('/^scorm_[0-9a-f]+$/', $folder), 404);

        $scorm = Scorm::with('activity')->where('folder', $folder)->firstOrFail();
        abort_unless($scorm->activity, 404);
        $learn = $this->learnFor($request, (int) $scorm->activity->course_id);
        $submitted = $request->input('cmi', []);
        abort_unless(is_array($submitted), 422, 'Data runtime SCORM tidak valid.');

        $cmi = [];
        $totalBytes = 0;
        foreach ($submitted as $key => $value) {
            if (!is_string($key) || !str_starts_with($key, 'cmi.') || is_array($value) || is_object($value)) {
                continue;
            }

            $cmi[$key] = mb_substr((string) ($value ?? ''), 0, 1048576);
            $totalBytes += strlen($key) + strlen($cmi[$key]);
        }

        abort_if(count($cmi) > 1000, 422, 'Data runtime SCORM terlalu besar.');
        abort_if($totalBytes > 5 * 1024 * 1024, 422, 'Data runtime SCORM terlalu besar.');

        $status = $this->scormProgressStatus($cmi);
        $score = $cmi['cmi.core.score.raw'] ?? $cmi['cmi.score.raw'] ?? null;
        $score = is_numeric($score) ? (float) $score : null;

        ActivityRuntimeState::updateOrCreate(
            [
                'learn_id' => $learn->id,
                'activity_id' => $scorm->activity_id,
            ],
            [
                'type' => 'scorm',
                'state' => ['cmi' => $cmi],
                'progress_status' => $status,
                'score_raw' => $score,
                'last_accessed_at' => now(),
            ],
        );

        return response()->json(['success' => true]);
    }

    public function h5p(Request $request, string $folder): JsonResponse
    {
        abort_unless((bool) preg_match('/^h5p_[0-9a-f]+$/', $folder), 404);

        $h5p = H5p::with('activity')->where('folder', $folder)->firstOrFail();
        abort_unless($h5p->activity, 404);
        $learn = $this->learnFor($request, (int) $h5p->activity->course_id);
        $dataType = (string) $request->input('data_type', 'state');
        $subContentId = (string) $request->input('sub_content_id', '0');

        abort_unless((bool) preg_match('/^[A-Za-z0-9_.-]{1,100}$/', $dataType), 422);
        abort_unless((bool) preg_match('/^[A-Za-z0-9_.-]{1,100}$/', $subContentId), 422);

        $runtime = ActivityRuntimeState::firstOrNew([
            'learn_id' => $learn->id,
            'activity_id' => $h5p->activity_id,
        ]);
        $state = is_array($runtime->state) ? $runtime->state : [];
        $contentUserData = $state['content_user_data'] ?? [];

        if ($request->isMethod('get')) {
            $data = $contentUserData[$subContentId][$dataType] ?? false;

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        $data = $request->input('data');
        if ($data === null || $data === 0 || $data === '0') {
            unset($contentUserData[$subContentId][$dataType]);
            if (empty($contentUserData[$subContentId])) {
                unset($contentUserData[$subContentId]);
            }
        } else {
            abort_unless(is_string($data), 422, 'Data runtime H5P tidak valid.');
            abort_if(strlen($data) > 5 * 1024 * 1024, 422, 'Data runtime H5P terlalu besar.');
            $contentUserData[$subContentId][$dataType] = $data;
        }

        $runtime->fill([
            'type' => 'h5p',
            'state' => ['content_user_data' => $contentUserData],
            'progress_status' => $runtime->progress_status === 'completed'
                ? 'completed'
                : 'in_progress',
            'last_accessed_at' => now(),
        ]);
        $runtime->save();

        return response()->json(['success' => true]);
    }

    private function learnFor(Request $request, int $courseId): Learn
    {
        $learn = Learn::where('participant_id', $request->user()->id)
            ->where('course_id', $courseId)
            ->first();

        abort_unless($learn, 403, 'Anda tidak terdaftar pada pelatihan ini.');

        return $learn;
    }

    private function scormProgressStatus(array $cmi): string
    {
        $lesson = strtolower((string) ($cmi['cmi.core.lesson_status'] ?? ''));
        $completion = strtolower((string) ($cmi['cmi.completion_status'] ?? ''));
        $success = strtolower((string) ($cmi['cmi.success_status'] ?? ''));

        if (in_array($lesson, ['completed', 'passed'], true)
            || $completion === 'completed'
            || $success === 'passed') {
            return 'completed';
        }

        if ($lesson === 'failed' || $success === 'failed') {
            return 'failed';
        }

        return 'in_progress';
    }
}
