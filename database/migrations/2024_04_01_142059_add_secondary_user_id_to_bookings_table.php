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
        Schema::table('bookings', function (Blueprint $table) {
            $table->bigInteger('secondary_employer_id')->unsigned()->nullable()->after('employer_id');
            $table->foreign('secondary_employer_id')->references('id')->on('employers')->onDelete('set null');
            $table->boolean('requested_secondary')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['secondary_employer_id']);
            $table->dropColumn('secondary_employer_id');
            $table->dropColumn('requested_secondary');
        });
    }
};
