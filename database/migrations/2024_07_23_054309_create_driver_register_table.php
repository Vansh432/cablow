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
        Schema::create('driver_register', function (Blueprint $table) {
            $table->id();
            $table->string('mobile_number', 15)->unique();
            $table->string('otp', 4)->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('image')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('state', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('street_address', 255)->nullable();
            $table->string('pin_code', 10)->nullable();
            $table->date('dob')->nullable();
            $table->string('vehicle_type', 50)->nullable();
            $table->string('device_id', 255)->nullable();
            $table->string('bearer_token', 255)->nullable();
            $table->enum('status', ['online', 'offline'])->default('offline');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_register');
    }
};
