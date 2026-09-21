<?php

namespace App\Services\Reporting;

use App\Repositories\Reporting\Contracts\ResultRepositoryInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class ResultService
{
    public function __construct(private readonly ResultRepositoryInterface $results) {}

    public function getRawResults($courseId, $filters = [])
    {
        return $this->results->getRawResults($courseId, $filters)
            ->map(function ($row) {
                if ($row->nip !== '-') {
                    try {
                        $row->nip = Crypt::decryptString($row->nip);
                    } catch (DecryptException $e) {
                        // Fallback to original if not encrypted
                    }
                }

                return $row;
            });
    }

    public function groupAverageScores($data)
    {
        return collect($data)->groupBy('name')->map(function ($rows, $name) {
            $scores = [];

            foreach ($rows as $row) {
                if (in_array($row->activity_type, ['post-test', 'pre-test'])) {
                    try {
                        $response = @unserialize($row->response);
                        if (is_array($response)) {
                            foreach ($response as $item) {
                                if (isset($item['score'])) {
                                    $scores[] = $item['score'];
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // optional: log error
                    }
                }
            }

            return [
                'nip' => $rows->first()->nip,
                'name' => $name,
                'jabatan' => $rows->first()->jabatan,
                'avg_score' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0,
                'updated_at' => $rows->max('updated_at'),
            ];
        })->values();
    }
}
