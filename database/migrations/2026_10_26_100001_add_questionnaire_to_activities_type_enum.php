<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE activities MODIFY COLUMN type ENUM('pre-test','post-test','task','video','meeting','literature','h5p','scorm','questionnaire') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE activities MODIFY COLUMN type ENUM('pre-test','post-test','task','video','meeting','literature','h5p','scorm') NOT NULL");
    }
};
