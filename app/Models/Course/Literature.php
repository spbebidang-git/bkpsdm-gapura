<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Literature extends Model
{
    use HasFactory;

    public static $directoryDocument = '/assets/media/documents/literatures/';

    public $guarded = [];
}
