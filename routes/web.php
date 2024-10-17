<?php

use App\Http\Controllers\WebSocketController;
use Illuminate\Support\Facades\Route;
use App\Events\DriverLocationUpdated;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::middleware('auth:web')->get('/websockets/dashboard', function () {
    return view('websockets.dashboard');
});
Route::get('/test-websocket', [WebSocketController::class, 'test']);


Route::get('/broadcast-test', function () {
    broadcast(new DriverLocationUpdated([
        'latitude' => 34.0522,
        'longitude' => -118.2437
    ]));

    return 'Broadcasted!';
});
