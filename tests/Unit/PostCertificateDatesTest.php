<?php

namespace Tests\Unit;

use App\Console\Commands\PostCertificateCommand;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PostCertificateDatesTest extends TestCase
{
    public function test_payload_matches_the_current_batch_certificate_api_contract(): void
    {
        $courseItem = (object) [
            'course' => (object) [
                'title' => 'Pelatihan Dinotech',
                'jam_pelajaran' => 4,
                'category' => (object) ['code' => 5],
            ],
        ];

        $method = new ReflectionMethod(PostCertificateCommand::class, 'buildPayload');
        $payload = $method->invoke(
            new PostCertificateCommand(),
            $courseItem,
            ['199210212019031005', '199210212019031006'],
            ['tgl_mulai' => '2026-11-03', 'tgl_selesai' => '2026-11-03'],
        );

        $this->assertSame([
            'fc_kduker' => 0,
            'fc_kdsatker' => 0,
            'fc_kdsubsatker' => 0,
            'lama_diklat' => 4,
            'jenis_waktu' => 'J',
            'kode_kegiatan' => 5,
            'nama_kegiatan' => 'Pelatihan Dinotech',
            'tempat_kegiatan' => 'Dinas Komunikasi & Informatika',
            'penyelenggara' => 'BKPSDM Gresik',
            'tahun' => (int) date('Y'),
            'tgl_mulai' => '2026-11-03',
            'tgl_selesai' => '2026-11-03',
            'peserta' => '199210212019031005,199210212019031006',
        ], $payload);
    }

    public function test_non_scheduled_course_uses_participant_completion_dates(): void
    {
        $course = new Course(['id' => 10, 'type' => 'unschedule']);
        $schedule = new Schedule();
        $schedule->setRelation('course', $course);
        $schedule->setRawAttributes(['id' => 20, 'date' => null]);

        $courseItem = (object) [
            'course' => (object) [
                'id' => 10,
                'schedules' => new Collection([$schedule]),
            ],
        ];
        $learnRecords = new Collection([
            (object) ['finished_at' => '2026-09-02 09:30:00'],
            (object) ['finished_at' => '2026-09-05 16:00:00'],
        ]);

        $method = new ReflectionMethod(PostCertificateCommand::class, 'resolveDates');
        $dates = $method->invoke(new PostCertificateCommand(), $courseItem, $learnRecords);

        $this->assertSame([
            'tgl_mulai' => '2026-09-02',
            'tgl_selesai' => '2026-09-05',
        ], $dates);
    }
}
