<?php

namespace App\Jobs;

use App\Models\Learning\Learn;
use App\Services\Learning\LearnLifecycleService;
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
            ->get()->each(fn (Learn $learn) => app(LearnLifecycleService::class)->setCertificate($learn, $this->data['url_certificate']));
    }
}
