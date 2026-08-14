<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_service', function (Blueprint $table) {
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();

            // A clinic offers a service at most once. The pair is the key.
            $table->primary(['clinic_id', 'service_id']);

            // /stomatologi/{oras}/{serviciu} pages start from a service and pull
            // clinics, so the reverse lookup needs its own index.
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_service');
    }
};
