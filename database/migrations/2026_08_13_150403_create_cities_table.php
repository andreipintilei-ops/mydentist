<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('county_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            // Unique across the whole country, NOT per county: this slug is the
            // /stomatologi/{oras} URL segment. Romania reuses town names across
            // counties, so colliding slugs get a -{judet} suffix at seed time
            // (see CitySeeder). Keeping the constraint global is what forces
            // that collision to be resolved rather than silently producing two
            // pages competing for the same URL.
            $table->string('slug')->unique();

            // municipiu | oras | comuna — drives listing prominence and lets us
            // avoid generating landing pages for tiny communes.
            $table->string('type', 16)->default('oras');

            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedInteger('population')->nullable();

            $table->timestamps();

            $table->index(['county_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
