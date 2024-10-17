<?php

use App\Events\DriverLocationUpdated;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OTPController;
use App\Http\Controllers\registerVehicle;
use App\Http\Controllers\RideController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// user 
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::middleware('auth.bearer')->group(function () {
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/ride/request/user_id_{user_id}', [RideController::class, 'requestRide']);
    Route::get('/user/ride/details/user_id_{user_id}/ride_id_{ride_id}', [RideController::class, 'getRideDetails']);
    Route::get('/rides/user_id_{user_id}/datetime_{datetime}', [RideController::class, 'getRides']);
    Route::post('/ride/cancel/user_id_{user_id}/ride_id_{ride_id}', [RideController::class, 'cancelRide']);


Route::get('/user/user_id_{user_id}/ride-history', [RideController::class, 'getUserRideHistory']);

Route::get('/user/user_id_{id}', [AuthController::class, 'getUser']);
Route::put('/user/user_id_{id}', [AuthController::class, 'updateUser']);

});
// Route::post('/ride/request/user_id_{user_id}', [RideController::class, 'requestRide'])->middleware('auth:bearer');

// driver 
Route::post('/driver/login', [DriverController::class, 'login']);
Route::post('/driver/verify-otp', [DriverController::class, 'verifyOtp'])->middleware('auth.token'); 
Route::post('/driver/resend-otp', [DriverController::class, 'resendOtp'])->middleware('auth.token');
Route::post('/driver/update-profile/driver_id_{id}', [DriverController::class, 'updateProfile'])->middleware('auth.token');
Route::post('/driver/register-vehicle/driver_id_{driverId}', [registerVehicle::class, 'registerVehicle'])->middleware('auth.token');
Route::get('/driver/vehicle-details/driver_id_{driverId}', [registerVehicle::class, 'getVehicleDetails'])->middleware('auth.token');

Route::middleware('auth.token')->group(function () {
    Route::post('/ride/accept/driver_id_{driver_id}', [RideController::class, 'acceptRide']);
    Route::get('/driver/ride-get/driver_id_{driver_id}/datetime_{datetime}', [RideController::class, 'getNearbyRideRequests']);
    Route::post('driver/driver_id_{driver_id}/location', [DriverLocationController::class, 'updateLocation']);
    Route::get('driver/driver_id_{driver_id}/location', [DriverLocationController::class, 'getLocation']);
    Route::post('/ride/verify-otp/ride_id_{ride_id}/driver_id_{driver_id}', [RideController::class, 'verifyRideOtp']);
    Route::post('/ride/complete/ride_id_{ride_id}/driver_id_{driver_id}', [RideController::class, 'completeRide']);
    Route::get('/driver/driver_id_{driver_id}/ride-history', [RideController::class, 'getDriverRideHistory']);


});


