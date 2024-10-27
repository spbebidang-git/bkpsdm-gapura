<?php

namespace App\Models\Learn;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Learn extends Model
{
    use HasFactory;

    public $guarded = [];

    public function learnItems()
    {
        return $this->hasMany(LearnItem::class);
    }

    public function progress(): Attribute
    {
        $doneActivity = $this->learnItems()->where('status', 'done')->count();
        $totalActivity = $this->learnItems()->count();

        if ($totalActivity == 0) {
            return Attribute::get(fn() => 0 . '%');
        }

        return Attribute::get(fn() => ($doneActivity / $totalActivity) * 100 . '%');
    }
}
