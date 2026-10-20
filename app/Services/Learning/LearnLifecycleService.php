<?php

namespace App\Services\Learning;

use App\Models\Learning\Learn;
use Illuminate\Support\Facades\DB;

class LearnLifecycleService
{
    public function __construct(private readonly ParticipantNotificationService $notifications) {}

    public function finish(Learn $learn, mixed $finishedAt = null): Learn
    {
        DB::transaction(function () use ($learn, $finishedAt) {
            $locked = Learn::query()->lockForUpdate()->find($learn->id);
            if (! $locked || $locked->finished_at) return;
            $locked->finished_at = $finishedAt ?? now();
            $locked->save();
            $locked->enrollment()->where('status', 'active')->update(['status' => 'completed']);
            $this->notifications->completed($locked);
            $learn->setRawAttributes($locked->getAttributes());
            $learn->syncOriginal();
        });

        return $learn;
    }

    public function setCertificate(Learn $learn, ?string $url): bool
    {
        if (! $this->notifications->validCertificateUrl($url)) return false;
        return DB::transaction(function () use ($learn, $url) {
            $locked = Learn::query()->lockForUpdate()->find($learn->id);
            if (! $locked || $locked->url_certificate === $url) return false;
            $firstCertificate = ! $this->notifications->validCertificateUrl($locked->url_certificate);
            $locked->url_certificate = $url;
            $locked->is_post_certificate = true;
            $locked->save();
            if ($firstCertificate) $this->notifications->certificate($locked);
            return true;
        });
    }
}
