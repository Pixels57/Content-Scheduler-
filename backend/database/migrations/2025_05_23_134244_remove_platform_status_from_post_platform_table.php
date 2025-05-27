<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('post_platform', function (Blueprint $table) {
            $table->dropColumn('platform_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_platform', function (Blueprint $table) {
            $table->enum('platform_status', ['active', 'inactive'])->default('active');
        });
    }
};
