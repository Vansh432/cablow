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
        Schema::create('driver_vehicle_register', function (Blueprint $table) {
            $table->id();
            $table->string('DL_number');
            $table->string('DL_image');
            $table->string('aadhaar_number');
            $table->string('aadhaar_front_image');
            $table->string('aadhaar_back_image');
            $table->string('PAN_number');
            $table->string('PAN_image');
            $table->string('RC_image');
            $table->string('insurance_image');
            $table->string('vehicle_permit_image');
            $table->foreignId('driver_id')->constrained('driver_register')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_vehicle_register');
    }
};
