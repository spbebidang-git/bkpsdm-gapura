<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scorm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'scorms';

    /** Folder publik tempat paket SCORM diekstrak. */
    public static $directory = 'assets/media/scorm/';

    public $guarded = [];

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }
}
