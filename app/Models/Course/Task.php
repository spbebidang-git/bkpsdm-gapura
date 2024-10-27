<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    public static $directoryTask = 'assets/media/tasks/';

    public $guarded = [];
}
