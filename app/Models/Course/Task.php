<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    public static $directoryTask = 'assets/media/tasks/';

    public $guarded = [];
}
