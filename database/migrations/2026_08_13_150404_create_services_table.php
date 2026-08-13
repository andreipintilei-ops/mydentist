<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // URL segment in /stomatologi/{oras}/{serviciu} and /servicii/{serviciu}.
            $table->string('slug')->unique();
            // Short intro reused as the landing-page lede and meta description
            // seed, so city × service pages are not near-duplicates of each other.
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
