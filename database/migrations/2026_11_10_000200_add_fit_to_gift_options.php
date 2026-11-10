<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** How a gift photo sits in its card: a cut-out product ("contain") or a full-bleed photo ("cover"). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_options', function (Blueprint $table) {
            $table->string('fit', 10)->default('contain')->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('gift_options', function (Blueprint $table) {
            $table->dropColumn('fit');
        });
    }
};
