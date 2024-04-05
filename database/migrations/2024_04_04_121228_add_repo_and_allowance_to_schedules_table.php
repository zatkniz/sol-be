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
        Schema::table('schedules', function (Blueprint $table) {
            $table->boolean('repo')->default(false); // Add a 'repo' boolean column with a default of false
            $table->boolean('allowance')->default(false); // Add an 'allowance' boolean column with a default of false
            $table->time('time_start')->nullable()->change();
            $table->time('time_end')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['repo', 'allowance']); // Remove the columns if the migration is rolled back
            $table->time('time_start')->nullable(false)->change();
            $table->time('time_end')->nullable(false)->change();
        });
    }
};
