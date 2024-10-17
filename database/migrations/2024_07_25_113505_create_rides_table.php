<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user_register')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('driver_register')->onDelete('cascade');
            $table->string('source_address');
            $table->decimal('source_latitude', 10, 7);
            $table->decimal('source_longitude', 10, 7);
            $table->string('destination_address');
            $table->decimal('destination_latitude', 10, 7);
            $table->decimal('destination_longitude', 10, 7);
            $table->enum('status', ['in_progress', 'completed', 'cancelled'])->default('in_progress');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};
