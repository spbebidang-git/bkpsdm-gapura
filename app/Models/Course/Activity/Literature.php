<?php

namespace App\Models\Course\Activity;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Literature extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public static $directoryDocument = 'assets/media/documents/literatures/';

    public $guarded = [];
}
