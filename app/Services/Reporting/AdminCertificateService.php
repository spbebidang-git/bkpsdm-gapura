<?php

namespace App\Services\Reporting;

use App\Jobs\BulkCertificateJob;
use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use Illuminate\Support\Facades\Artisan;

class AdminCertificateService
{
    public function __construct(private readonly LearnRepositoryInterface $learns, private readonly CourseRepositoryInterface $courses) {}

    public function query(array $filters)
    {
        return $this->learns->certificateQuery($filters);
    }

    public function courses()
    {
        return $this->courses->certificateCourses();
    }

    public function filterColumn($query, string $relation, string $field, string $keyword): void
    {
        $this->learns->filterCertificateColumn($query, $relation, $field, $keyword);
    }

    public function push($courseId, $learnId): array
    {

        $params = [];
        if ($learnId) {
            $params['--learn'] = $learnId;
        } elseif ($courseId) {
            $params['--course'] = $courseId;
        }

        set_time_limit(0);
        $exitCode = Artisan::call('app:post-certificate-command', $params);
        $output = trim(Artisan::output());

        $finalMessage = 'Periksa log server untuk detail.';
        if (preg_match('/Push selesai\.\s*(Berhasil:\s*\d+,\s*Skip:\s*\d+,\s*Gagal:\s*\d+.*)$/m', $output, $matches)) {
            $finalMessage = trim($matches[1]);
        } elseif (preg_match('/(Berhasil:\s*\d+,\s*Skip:\s*\d+,\s*Gagal:\s*\d+.*)/', $output, $matches)) {
            $finalMessage = trim($matches[1]);
        } elseif (str_contains($output, 'Tidak ada data yang perlu di-push')) {
            $finalMessage = 'Tidak ada data yang perlu di-push.';
        }

        $msg = ($exitCode !== 0 ? 'Sync Push error. ' : 'Sync Push OK. ').$finalMessage;

        return ['exitCode' => $exitCode, 'message' => $msg];
    }

    public function queueBulk($courseId): void
    {
        BulkCertificateJob::dispatch($courseId);
    }
}
