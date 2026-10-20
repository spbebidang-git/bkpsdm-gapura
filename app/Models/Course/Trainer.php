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

    protected $appends = ['organization_id', 'organization_name', 'organization_ids', 'organization_names', 'organization_options'];

    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function opds()
    {
        return $this->belongsToMany(Opd::class, 'trainer_opd', 'trainer_id', 'opd_id');
    }

    protected function organizationIds(): Attribute
    {
        return Attribute::get(function () {
            $ids = $this->relationLoaded('opds') ? $this->opds->pluck('id') : $this->opds()->pluck('opds.id');
            if ($this->user_id && $this->user?->opd_id) $ids->push($this->user->opd_id);
            if (! $this->user_id && $this->opd_id) $ids->push($this->opd_id);
            return $ids->unique()->values()->all();
        });
    }

    protected function organizationNames(): Attribute
    {
        return Attribute::get(function () {
            $ids = $this->organization_ids;
            return $ids ? Opd::whereIn('id', $ids)->orderBy('name')->pluck('name')->all() : [];
        });
    }

    protected function organizationOptions(): Attribute
    {
        return Attribute::get(fn () => Opd::whereIn('id', $this->organization_ids)->where('is_active', true)
            ->orderBy('name')->get(['id', 'name'])->values()->all());
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

    public static function activeOpdIdsForUser(int|string $userId): array
    {
        $trainer = static::with(['opds', 'user.opd'])->where('user_id', $userId)->first();
        return $trainer
            ? collect($trainer->organization_ids)->filter(fn ($id) => \App\Models\Identity\Opd::whereKey($id)->where('is_active', true)->exists())->values()->all()
            : [];
    }

    public function photoSrc(): Attribute
    {
        if ($this->user_id) {
            return Attribute::get(fn () => $this->user?->profile_picture ?? asset('assets/media/images/static/default-profile.jpg'));
        }
        if (! $this->photo) {
            return Attribute::get(fn () => asset('assets/media/images/static/default-profile.jpg'));
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
