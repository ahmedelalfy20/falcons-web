<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rounds keep their internal sequence (`number`, used for ordering), plus what
 * visitors see: an optional display number (so numbering can restart at 1)
 * and an optional Arabic name next to the existing English one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->unsignedInteger('display_number')->nullable()->after('number');
            $table->string('name_ar')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn(['display_number', 'name_ar']);
        });
    }
};
