<?php

namespace App\Models\Course;

use App\Models\Identity\User;
use App\Models\Identity\Opd;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trainer extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public static $directoryImage = 'assets/media/images/trainers/';

    protected $guarded = [];

    protected $appends = ['organization_id', 'organization_name'];

    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    protected function organizationId(): Attribute
    {
        return Attribute::get(fn () => $this->user_id ? $this->user?->opd_id : $this->opd_id);
    }

    protected function organizationName(): Attribute
    {
        return Attribute::get(fn () => $this->user_id ? $this->user?->opd?->name : $this->opd?->name);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_trainer');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photoSrc(): Attribute
    {
        if ($this->user_id) {
            return Attribute::get(fn () => $this->user?->profile_picture ?? asset('assets/media/images/static/default-profile.jpg'));
        }
        if (! $this->photo) {
            return Attribute::get(fn () => asset('assets/media/images/static/default-class.jpg'));
        }

        return Attribute::get(fn () => asset(self::$directoryImage . $this->photo));
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn ($value) => $this->user_id ? ($this->user?->name ?? $value) : $value);
    }

    protected function jabatan(): Attribute
    {
        return Attribute::get(fn ($value) => $this->user_id ? $this->user?->metas->firstWhere('key', 'nama_jabatan')?->value : $value);
    }
}
