<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE learn_items MODIFY COLUMN type ENUM('pre-test','post-test','task','meeting','video','literature','h5p') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE learn_items MODIFY COLUMN type ENUM('pre-test','post-test','task','meeting','video','literature') NOT NULL");
    }
};
