<?php

namespace App\Models\Concerns;

use App\Models\Identity\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait TracksModifier
{
    protected static function bootTracksModifier(): void
    {
        static::saving(function ($model): void {
            if (($userId = Auth::id()) && (! $model->exists || $model->isDirty())) {
                $model->modified_by = $userId;
            }
        });
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by')->withTrashed();
    }
}
