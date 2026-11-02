<?php

namespace App\Services\Learning;

use App\Models\Learning\Learn;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use Illuminate\Support\Facades\DB;

class LearnLifecycleService
{
    public function __construct(private readonly ParticipantNotificationService $notifications, private readonly LearnRepositoryInterface $learns) {}

    public function finish(Learn $learn, mixed $finishedAt = null): Learn
    {
        DB::transaction(function () use ($learn, $finishedAt) {
            $locked = $this->learns->lockById($learn->id);
            if (! $locked || $locked->finished_at) {
                return;
            }
            $locked->finished_at = $finishedAt ?? now();
            $this->learns->update($locked, $locked->getDirty());
            $this->learns->completeActiveEnrollment($locked);
            $this->notifications->completed($locked);
            $learn->setRawAttributes($locked->getAttributes());
            $learn->syncOriginal();
        });

        return $learn;
    }

    public function setCertificate(Learn $learn, ?string $url): bool
    {
        if (! $this->notifications->validCertificateUrl($url)) {
            return false;
        }

        return DB::transaction(function () use ($learn, $url) {
            $locked = $this->learns->lockById($learn->id);
            if (! $locked || $locked->url_certificate === $url) {
                return false;
            }
            $firstCertificate = ! $this->notifications->validCertificateUrl($locked->url_certificate);
            $locked->url_certificate = $url;
            $locked->is_post_certificate = true;
            $this->learns->update($locked, $locked->getDirty());
            if ($firstCertificate) {
                $this->notifications->certificate($locked);
            }

            return true;
        });
    }
}
