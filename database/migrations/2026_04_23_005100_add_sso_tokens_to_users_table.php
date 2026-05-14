<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('sso_access_token')->nullable()->after('password');
            $table->text('sso_refresh_token')->nullable()->after('sso_access_token');
            $table->timestamp('sso_expires_at')->nullable()->after('sso_refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sso_access_token', 'sso_refresh_token', 'sso_expires_at']);
        });
    }
};
