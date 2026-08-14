<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();

            // Set when a registered user leaves the review; null for reviews left
            // by anonymous visitors (still moderated before publishing).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('author_name');
            $table->unsignedTinyInteger('rating'); // 1..5, enforced in the model/request
            $table->text('body')->nullable();

            // Reviews are moderated before they count. Only `published` rows feed
            // clinics.rating_avg / rating_count and the JSON-LD aggregateRating,
            // so fake or abusive reviews never reach Google.
            $table->enum('status', ['pending', 'published', 'rejected'])->default('pending');
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['clinic_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
