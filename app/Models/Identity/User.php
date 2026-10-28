<?php

namespace App\Models\Identity;

use App\Models\Course\Schedule;
use App\Models\Identity\Opd;
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
use LogicException;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, HasUlids, Notifiable, SoftDeletes;

    public static $directoryImage = 'assets/media/images/users/';

    public const AUTH_SOURCE_LOCAL = 'local';

    public const AUTH_SOURCE_SSO = 'sso';

    public const AUTH_SOURCE_PRESTIGE = 'prestige';

    protected $fillable = [
        'name',
        'nip',
        'email',
        'password',
        'auth_source',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'image',
        'opd_id',
        'employee_status_id',
    ];

    protected $attributes = [
        'auth_source' => self::AUTH_SOURCE_SSO,
    ];

    protected $hidden = [
        'remember_token',
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $user): void {
            if ($user->isDirty('auth_source')) {
                throw new LogicException('Sumber autentikasi akun tidak dapat diubah.');
            }
        });
    }

    public function usesLocalAuthentication(): bool
    {
        return $this->auth_source === self::AUTH_SOURCE_LOCAL;
    }

    public function usesSsoAuthentication(): bool
    {
        return $this->auth_source === self::AUTH_SOURCE_SSO;
    }

    public function usesPrestigeAuthentication(): bool
    {
        return $this->auth_source === self::AUTH_SOURCE_PRESTIGE;
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'participant_id');
    }

    public function metas()
    {
        return $this->hasMany(UserMeta::class);
    }

    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function employeeStatus()
    {
        return $this->belongsTo(EmployeeStatus::class);
    }

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_user', 'participant_id');
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
        return Attribute::get(fn () => $this->hasAnyRole(['super_admin', 'administrator']));
    }

    public function isInstructor(): Attribute
    {
        return Attribute::get(fn () => $this->hasRole('trainer'));
    }

    public function isParticipant(): Attribute
    {
        return Attribute::get(fn () => $this->hasRole('participant'));
    }

    public function profilePicture(): Attribute
    {
        if (! $this->image) {
            return Attribute::get(fn () => asset('assets/media/images/static/default-profile.jpg'));
        }

        return Attribute::get(fn () => asset(self::$directoryImage.$this->image));
    }
}
