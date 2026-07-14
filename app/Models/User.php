<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Course\Schedule;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    public static $directoryImage = 'assets/media/images/users/';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'sso_access_token',
        'sso_refresh_token',
        'sso_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'sso_access_token',
        'sso_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // 'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function metas()
    {
        return $this->hasMany(\App\Models\Management\UserMeta::class);
    }

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_user', 'participant_id')
            ->withPivot('pre_test_variant_id', 'post_test_variant_id');
    }

    public function voteRecords()
    {
        return $this->hasMany(VoteRecord::class);
    }

    public function nip(): Attribute
    {
        return Attribute::get(fn() => get_user_meta($this->id, 'nip') ?: '-');
    }

    public function isAdmin(): Attribute
    {
        return Attribute::get(fn() => $this->getRoleNames()->first() == 'Admin'  ? true : false);
    }

    public function isInstructor(): Attribute
    {
        return Attribute::get(fn() => $this->getRoleNames()->first() == 'Instructor' ? true : false);
    }

    public function isParticipant(): Attribute
    {
        return Attribute::get(fn() => $this->getRoleNames()->first() == 'Participant' ? true : false);
    }

    public function profilePicture(): Attribute
    {
        if (!$this->image) {
            return Attribute::get(fn() => asset('assets/media/images/static/default-profile.jpg'));
        }

        return Attribute::get(fn() => asset(self::$directoryImage . $this->image));
    }
}
