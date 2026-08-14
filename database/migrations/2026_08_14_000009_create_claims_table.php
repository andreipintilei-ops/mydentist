<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();

            // The signed-in user requesting ownership — they become claimed_by on
            // approval, so claiming requires an account.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // The address we email a verification link to. Ideally on the clinic's
            // own domain — that's the strongest evidence the claimant runs it.
            $table->string('claimant_email');
            $table->text('message')->nullable();

            // pending  — submitted, email not yet verified
            // verified — email confirmed, waiting on a moderator
            // approved — moderator granted ownership
            // rejected — moderator declined
            $table->enum('status', ['pending', 'verified', 'approved', 'rejected'])->default('pending');

            // Single-use token for the email verification link.
            $table->string('verification_token', 64)->nullable()->unique();
            $table->timestamp('verified_at')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['clinic_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
