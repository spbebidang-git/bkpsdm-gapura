<?php

namespace Tests\Unit;

use App\Services\Reporting\LearningReportService;
use Tests\TestCase;

class LearningReportServiceTest extends TestCase
{
    public function test_question_response_is_aggregated_without_exposing_answers(): void
    {
        $service = new LearningReportService();
        $response = serialize([
            ['question' => 'rahasia', 'score' => 10, 'is_correct' => true],
            ['question' => 'rahasia lain', 'score' => 0, 'is_correct' => false],
        ]);

        $metrics = $service->responseMetrics($response, 'post-test');

        $this->assertSame(10.0, $metrics['score']);
        $this->assertSame(1, $metrics['correct']);
        $this->assertSame(1, $metrics['wrong']);
        $this->assertArrayNotHasKey('question', $metrics);
    }

    public function test_invalid_response_is_safe_and_reports_no_score(): void
    {
        $metrics = (new LearningReportService())->responseMetrics('not-a-serialized-value', 'pre-test');

        $this->assertNull($metrics['score']);
        $this->assertNull($metrics['correct']);
        $this->assertFalse($metrics['available']);
    }

    public function test_activity_without_a_learn_item_is_reported_as_not_started(): void
    {
        $row = (object) [
            'enrollment_id' => 'enrollment-1',
            'participant_id' => 'participant-1',
            'participant_name' => 'Peserta Uji',
            'participant_nip' => null,
            'nip_meta_value' => null,
            'jabatan' => null,
            'course_id' => 'course-1',
            'course_title' => 'Kelas Uji',
            'schedule_title' => null,
            'enrollment_status' => 'active',
            'enrolled_at' => '2026-10-01 08:00:00',
            'finished_at' => null,
            'activity_id' => 'activity-1',
            'activity_title' => 'Literatur',
            'activity_type' => 'literature',
            'activity_value' => 0,
            'activity_timer' => null,
            'updated_at' => null,
            'learn_item_id' => null,
            'learn_item_status' => null,
            'learn_item_response' => null,
            'interactive_duration' => null,
            'literature_duration' => 20,
            'task_attachment_path' => null,
        ];

        $report = (new LearningReportService())->decorate($row);

        $this->assertSame('not_started', $report['status']);
        $this->assertSame('Belum dimulai', $report['status_label']);
        $this->assertNull($report['score']);
        $this->assertNull($report['attachment_url']);
    }
}
