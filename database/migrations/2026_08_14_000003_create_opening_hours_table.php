<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();

            // 0 = Monday ... 6 = Sunday (ISO-8601 weekday order minus one). A day
            // can have MORE THAN ONE row to model a lunch break (e.g. 09:00–13:00
            // and 14:00–18:00), so there is deliberately no unique constraint on
            // (clinic_id, day_of_week).
            $table->unsignedTinyInteger('day_of_week');

            // Null open/close with is_closed = true means "closed all day". This
            // is stored explicitly rather than as a missing row so the owner panel
            // can distinguish "closed on Sunday" from "hours not filled in yet".
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);

            $table->timestamps();

            $table->index(['clinic_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_hours');
    }
};
