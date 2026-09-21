<?php

namespace App\Models\Identity;

use App\Models\Course\Schedule;
use App\Models\Learning\Enrollment;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, HasUlids, Notifiable, SoftDeletes;

    public static $directoryImage = 'assets/media/images/users/';

    protected $fillable = [
        'name',
        'nip',
        'email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'image',
    ];

    protected $hidden = [
        'remember_token',
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    /** No local password — SSO only. */
    public function getAuthPassword(): string
    {
        return '';
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'participant_id');
    }

    public function metas()
    {
        return $this->hasMany(UserMeta::class);
    }

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_user', 'participant_id')
            ->withPivot('pre_test_variant_id', 'post_test_variant_id');
    }

    /**
     * Prefer users.nip column; fallback encrypted meta.
     */
    protected function nip(): Attribute
    {
        return Attribute::get(function (?string $value) {
            if (filled($value)) {
                return $value;
            }

            $meta = $this->relationLoaded('metas')
                ? $this->metas->firstWhere('key', 'nip')
                : $this->metas()->where('key', 'nip')->first();

            if (! $meta) {
                return null;
            }

            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($meta->value);
            } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                return $meta->value;
            }
        });
    }

    public function isAdmin(): Attribute
    {
        return Attribute::get(fn () => $this->hasRole('Admin'));
    }

    public function isInstructor(): Attribute
    {
        return Attribute::get(fn () => $this->hasRole('Instructor'));
    }

    public function isParticipant(): Attribute
    {
        return Attribute::get(fn () => $this->hasRole('Participant'));
    }

    public function profilePicture(): Attribute
    {
        if (! $this->image) {
            return Attribute::get(fn () => asset('assets/media/images/static/default-profile.jpg'));
        }

        return Attribute::get(fn () => asset(self::$directoryImage.$this->image));
    }
}
