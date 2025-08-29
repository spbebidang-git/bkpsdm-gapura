<?php

namespace App\Models;

use App\Models\Course\Course;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class EmployeeStatus extends Model
{
    use HasFactory;

    public $guarded = [];

    protected $table = 'employee_status';

    public function courses()
    {
        return $this->belongsToMany(Course::class);
    }
}
