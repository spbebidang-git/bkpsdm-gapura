<?php

namespace App\Models\Identity;

use App\Models\Course\Course;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeStatus extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public $guarded = [];

    protected $table = 'employee_status';

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_employee_status');
    }
}
