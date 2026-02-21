<?php

namespace App\Jobs;

use App\Models\Learn\Learn;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SetUrlCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        # update url certificate & mark as posted
        Learn::where('course_id', $this->data['course'])
            ->where('participant_id', $this->data['participant'])
            ->update([
                'url_certificate'    => $this->data['url_certificate'],
                'is_post_certificate' => 1,
            ]);
    }
}
