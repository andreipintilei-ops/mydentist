<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();

            $table->string('path');
            $table->string('disk')->default('public');

            // Stored explicitly so the <img> can ship width/height attributes and
            // reserve space — no layout shift (CLS) when photos lazy-load.
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();

            $table->string('alt')->nullable();

            // Drag-to-reorder in the owner/admin panel; the first photo is the
            // listing thumbnail and the OG image.
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['clinic_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_photos');
    }
};
