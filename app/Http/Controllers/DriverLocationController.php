<?php

namespace App\Http\Controllers;

use App\Events\DriverLocationUpdated;
use App\Models\DriverLocation;
use Illuminate\Http\Request;
use App\Models\DriverRegister;
use Illuminate\Support\Facades\Log;

class DriverLocationController extends Controller
{
    // public function updateLocation(Request $request, $driver_id)
    // {
    //     // Validate the request
    //     $request->validate([
    //         'driver_lat' => 'required|numeric',
    //         'driver_long' => 'required|numeric',
    //     ]);

    //     // Create or update the driver's location
    //     $location = DriverLocation::updateOrCreate(
    //         ['driver_id' => $driver_id],
    //         ['latitude' => $request->input('driver_lat'), 'longitude' => $request->input('driver_long')]
    //     );

    //     return response()->json(['message' => 'Location updated successfully', 'Location' => $location]);
    // }

    public function updateLocation(Request $request, $driver_id)
{
    // Log each time the API is hit
    Log::info('API hit to update location for driver_id: ' . $driver_id, [
        'latitude' => $request->input('driver_lat'),
        'longitude' => $request->input('driver_long')
    ]);

    $request->validate([
        'driver_lat' => 'required|numeric',
        'driver_long' => 'required|numeric',
    ]);

    $location = DriverLocation::updateOrCreate(
        ['driver_id' => $driver_id],
        ['latitude' => $request->input('driver_lat'), 'longitude' => $request->input('driver_long')]
    );

    // Broadcast the updated location
    broadcast(new DriverLocationUpdated($driver_id, $location->latitude, $location->longitude));

    return response()->json(['message' => 'Location updated successfully', 'Location' => $location]);
}



    public function getLocation($driver_id)
    {
        // Find the driver's location by driver_id
        $location = DriverLocation::where('driver_id', $driver_id)->first();

        if (!$location) {
            return response()->json(['error' => 'Location not found for this driver'], 404);
        }

        return response()->json([
            'driver_id' => $location->driver_id,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'updated_at' => $location->updated_at,
        ]);
    }
}
