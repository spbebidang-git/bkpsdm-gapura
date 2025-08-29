<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_employee_status', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('employee_status_id')->constrained('employee_status')->cascadeOnDelete();
            $table->primary(['course_id', 'employee_status_id']);
        });
    }
};
