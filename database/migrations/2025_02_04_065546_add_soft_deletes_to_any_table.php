<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('interactives', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('literatures', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Schema::table('schedule_user', function (Blueprint $table) {
        //     $table->softDeletes();
        // });

        Schema::table('question_variants', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('question_items', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('learns', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('learn_items', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Schema::table('user_meta', function (Blueprint $table) {
        //     $table->softDeletes();
        // });
    }
};
