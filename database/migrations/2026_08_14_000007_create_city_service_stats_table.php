<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_service_stats', function (Blueprint $table) {
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();

            // Count of PUBLISHED, non-demo clinics in this city offering this
            // service. Rebuilt nightly (RebuildCityServiceStats). It powers two
            // things without an N+1 COUNT() per page:
            //   - the thin-content guard (a city×service page renders only at
            //     >= 3 clinics, else 301s to the city page)
            //   - the sitemap (which city×service URLs are worth emitting)
            $table->unsignedInteger('clinic_count')->default(0);

            $table->timestamps();

            $table->primary(['city_id', 'service_id']);
            // Sitemap/threshold scans filter by count.
            $table->index('clinic_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_service_stats');
    }
};
