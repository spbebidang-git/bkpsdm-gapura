<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class H5p extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'h5ps';

    /** Folder publik tempat paket .h5p diekstrak. */
    public static $directory = 'assets/media/h5p/';

    public $guarded = [];

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }
}
