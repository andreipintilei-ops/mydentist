<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Slugs are diacritic-free ASCII: "Bistrița-Năsăud" -> "bistrita-nasaud".
            // They appear in URLs like /stomatologi/judetul-{slug}, so they are
            // permanent once published.
            $table->string('slug')->unique();
            // Vehicle registration code (IS, CJ, B...). Short, universally known
            // in Romania, and handy for admin filters and disambiguation.
            $table->string('auto_code', 2)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counties');
    }
};
