<?php

namespace App\Models\Identity;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserMeta extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'user_meta';

    public $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
