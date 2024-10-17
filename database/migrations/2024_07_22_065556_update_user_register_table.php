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
        Schema::table('user_register', function (Blueprint $table) {
            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('pin_code')->nullable();
            $table->string('street_address')->nullable();
            $table->string('image')->nullable();
            $table->string('bearer_token')->nullable();
            $table->string('device_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_register', function (Blueprint $table) {
            $table->dropColumn([
                'full_name', 'email', 'state', 'city', 'pin_code', 
                'street_address', 'image', 'bearer_token', 'device_id'
            ]);
        });
    }
};
