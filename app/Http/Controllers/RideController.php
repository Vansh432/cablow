<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use App\Models\DriverRegister;
use App\Models\DriverVehicleRegister;
use App\Models\RideRequest;
use App\Models\Ride;
use App\Models\UserRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\FirebaseService;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class RideController extends Controller
{

    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }


  public function requestRide(Request $request, $user_id)
{
    // Validate incoming request data, including price
    $validator = Validator::make($request->all(), [
        'source_address' => 'required|string',
        'source_latitude' => 'required|numeric',
        'source_longitude' => 'required|numeric',
        'destination_address' => 'required|string',
        'destination_latitude' => 'required|numeric',
        'destination_longitude' => 'required|numeric',
        'vehicle_type' => 'required|string',
        'price' => 'required|numeric' // Validate the price
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Retrieve bearer token from request
    $bearerToken = $request->bearerToken();

    // Check if user exists and if the token matches
    $user = UserRegister::where('id', $user_id)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    if ($user->bearer_token !== $bearerToken) {
        return response()->json(['message' => 'Invalid Bearer token'], 401);
    }

    // Find nearby drivers based on user's source location and vehicle type
    $drivers = $this->findNearbyDrivers($request->source_latitude, $request->source_longitude, $request->vehicle_type);

    if ($drivers->isEmpty()) {
        return response()->json(['message' => 'No drivers found nearby.'], 404);
    }

    // Convert distances, include device_id, vehicle_type, and price from the request body
    $convertedDrivers = $drivers->map(function ($driver) use ($request) {
        $distanceInMeters = $driver->distance * 1000; // Convert km to meters

        return [
            'driver_id' => $driver->driver_id,
            'distance_meters' => $distanceInMeters,
            'distance_kilometers' => $driver->distance, // Already in kilometers
            'device_id' => $driver->device_id,
            'vehicle_type' => $driver->vehicle_type, // Include vehicle type
            'price' => $request->price // Use the price from the request
        ];
    });

    // Pass the collection directly to sendNotification with user details
    $this->sendNotification($convertedDrivers, [
        'user_id' => $user_id,
        'mobile_number' => $user->mobile_number , // Pass the user mobile number
        'source_address' => $request->source_address,
        'destination_address' => $request->destination_address,
        'price' => $request->price // Include price from the request
    ]);

    // Save ride request with the provided price
    $rideRequest = RideRequest::create([
        'user_id' => $user_id,
        'source_address' => $request->source_address,
        'source_latitude' => $request->source_latitude,
        'source_longitude' => $request->source_longitude,
        'destination_address' => $request->destination_address,
        'destination_latitude' => $request->destination_latitude,
        'destination_longitude' => $request->destination_longitude,
        'vehicle_type' => $request->vehicle_type, // Save vehicle type
        'price' => $request->price, // Save the provided price
    ]);

    // Return success response with ride request details and driver distances
    return response()->json([
        'message' => 'Ride request sent.',
        'ride_request_id' => $rideRequest->id,
        'ride_request_details' => $rideRequest,
        'user_details' => $user,
        'drivers' => $convertedDrivers
    ]);
}




  public function getNearbyRideRequests(Request $request, $driver_id, $datetime)
{
    // Retrieve driver details including their status
    $driver = DB::table('driver_register')->where('id', $driver_id)->first();

    if (!$driver) {
        return response()->json(['message' => 'Driver not found.'], 404);
    }

    // Ensure the driver is online
    if ($driver->status !== 'online') {
        return response()->json(['message' => 'Driver is offline.'], 403);
    }

    // Retrieve driver's live location from the driver_locations table
    $driverLocation = DB::table('driver_locations')
        ->where('driver_id', $driver_id)
        ->first();

    if (!$driverLocation) {
        return response()->json(['message' => 'Driver location not found.'], 404);
    }

    // Parse the datetime parameter
    try {
        $requestedTime = Carbon::parse($datetime);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Invalid date-time format.'], 400);
    }

    // Calculate the time range (10 minutes before and 10 minutes after the provided time)
    $startTime = $requestedTime->copy()->subMinutes(10);
    $endTime = $requestedTime->copy()->addMinutes(10);

    // Calculate the 10 km radius and find pending ride requests within this radius
    $pendingRideRequests = DB::table('ride_requests')
        ->join('user_register', 'ride_requests.user_id', '=', 'user_register.id')
        ->select(
            'ride_requests.*',
            'user_register.full_name as user_name',
            'user_register.email as user_email',
            'user_register.mobile_number as user_phone',
            DB::raw(
                '(6371 * acos(cos(radians(' . $driverLocation->latitude . ')) 
                * cos(radians(source_latitude)) 
                * cos(radians(source_longitude) - radians(' . $driverLocation->longitude . ')) 
                + sin(radians(' . $driverLocation->latitude . ')) 
                * sin(radians(source_latitude)))) AS distance'
            )
        )
        ->having('distance', '<=', 10)
        ->where('ride_requests.status', 'pending')
        ->whereBetween('ride_requests.created_at', [$startTime, $endTime]) // Filter by the 10-minute window
        ->get();

    // Check if any ride requests found
    if ($pendingRideRequests->isEmpty()) {
        return response()->json(['message' => 'No ride requests found within 10 km radius.'], 404);
    }

    // Return the list of nearby ride requests along with user details
    return response()->json([
        'message' => 'Ride requests found.',
        'ride_requests' => $pendingRideRequests
    ]);
}





    public function sendNotification(Collection $drivers, array $data)
    {
        $accessToken = $this->firebaseService->getAccessToken();
    
        foreach ($drivers as $driver) {
    $notificationData = array_merge($data, [
        'driver_id' => (string)$driver['driver_id'],
        'distance_meters' => (string)$driver['distance_meters'],
        'distance_kilometers' => (string)$driver['distance_kilometers'],
        'price' => (string)$driver['price'], // Include price in the data
    ]);

    $notificationBody = sprintf(
        'New Ride Request from %s. Distance: %.2f meters. Price: %s',
        $data['mobile_number'],
        $driver['distance_meters'],
        $driver['price']
    );

    Log::info('Sending Notification to: ' . $driver['device_id']);
    Log::info('Notification Body: ' . $notificationBody);
    Log::info('Notification Data: ', $notificationData);

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json',
    ])->post('https://fcm.googleapis.com/v1/projects/cablow-driver/messages:send', [
        'message' => [
            'token' => $driver['device_id'],
            'notification' => [
                'title' => 'CabLow',
                'body' => $notificationBody,
            ],
            'data' => $notificationData,
        ],
    ]);

    Log::info('FCM Response for token ' . $driver['device_id'] . ':', $response->json());
}

    }
    


public function findNearbyDrivers($sourceLatitude, $sourceLongitude, $vehicleType, $radius = 10)
{
    Log::info('Finding nearby drivers...');
    Log::info('Source Latitude: ' . $sourceLatitude);
    Log::info('Source Longitude: ' . $sourceLongitude);
    Log::info('Vehicle Type: ' . $vehicleType);

    // Query to find drivers within the radius and matching vehicle type
    $drivers = DB::table('driver_locations')
        ->join('driver_register', 'driver_locations.driver_id', '=', 'driver_register.id') // Join with driver_register
        ->select(
            'driver_register.device_id as device_id', 
            'driver_register.vehicle_type as vehicle_type', // Select vehicle type
            'driver_locations.driver_id', 
            'driver_locations.latitude', 
            'driver_locations.longitude',
            DB::raw(
                "(6371 * acos(cos(radians($sourceLatitude)) 
                * cos(radians(driver_locations.latitude)) 
                * cos(radians(driver_locations.longitude) - radians($sourceLongitude)) 
                + sin(radians($sourceLatitude)) 
                * sin(radians(driver_locations.latitude)))) AS distance"
            )
        )
        ->where('driver_register.vehicle_type', $vehicleType) // Filter by vehicle type
        ->having('distance', '<', $radius)
        ->get();

    Log::info('Found ' . $drivers->count() . ' drivers.');

    foreach ($drivers as $driver) {
        Log::info('Driver ID: ' . $driver->driver_id . ' Distance: ' . $driver->distance);
    }

    return $drivers;
}


    public function acceptRide(Request $request, $driver_id)
    {
        $validator = Validator::make($request->all(), [
            'ride_request_id' => 'required|exists:ride_requests,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Retrieve the authenticated driver
        $driver = DriverRegister::find($driver_id);

        if (!$driver) {
            return response()->json(['message' => 'Driver not found.'], 404);
        }

        // Retrieve bearer token from request
        $bearerToken = $request->bearerToken();

        // Check if user exists and if the token matches
        $driver = DriverRegister::where('id', $driver_id)->first();

        if (!$driver) {
            return response()->json(['message' => 'Driver not found.'], 404);
        }

        if ($driver->bearer_token !== $bearerToken) {
            return response()->json(['message' => 'Invalid Bearer token'], 401);
        }

        $rideRequest = RideRequest::find($request->ride_request_id);

        if ($rideRequest->status == 'accepted') {
            return response()->json(['message' => 'Ride request already accepted.'], 400);
        }

        // Generate a 4-digit OTP
        $otp = random_int(1000, 9999);

        DB::transaction(function () use ($rideRequest, $driver, $otp, &$ride) {
            // Update ride request status
            $rideRequest->update(['status' => 'accepted', 'driver_id' => $driver->id]);

            // Save ride details to Rides table
            $ride = Ride::create([
                'user_id' => $rideRequest->user_id,
                'driver_id' => $driver->id,
                'source_address' => $rideRequest->source_address,
                'source_latitude' => $rideRequest->source_latitude,
                'source_longitude' => $rideRequest->source_longitude,
                'destination_address' => $rideRequest->destination_address,
                'destination_latitude' => $rideRequest->destination_latitude,
                'destination_longitude' => $rideRequest->destination_longitude,
                'status' => "in progress",
                'otp' => $otp,
                'ride_request_id' => $rideRequest->id,
            ]);
        });


        // Retrieve the driver's vehicle details
        $vehicle = DriverVehicleRegister::where('driver_id', $driver->id)->first();

        // Send notification via FCM
        $this->sendFCMNotification($rideRequest->user_id, [
            'full_name' => $driver->full_name,  // Ensure this key is properly set
            'vehicle_type' => $driver->vehicle_type,  // Ensure this key is properly set
            'otp' => $otp,
            'ride_id' => $ride->id,
        ]);

        return response()->json([
            'message' => 'Ride accepted successfully.',
            'Driver Details' => $driver,
            'Vehicle Details' => $vehicle,
            'ride_details' => $ride,
        ]);
    }

    public function verifyRideOtp(Request $request, $ride_id, $driver_id)
    {
        // Validate the input OTP
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Retrieve the ride details
        $ride = Ride::find($ride_id);

        if (!$ride) {
            return response()->json(['message' => 'Ride not found.'], 404);
        }

        // Retrieve bearer token from request
        $bearerToken = $request->bearerToken();

        // Check if user exists and if the token matches
        $driver = DriverRegister::where('id', $driver_id)->first();

        if (!$driver) {
            return response()->json(['message' => 'Driver not found.'], 404);
        }

        if ($driver->bearer_token !== $bearerToken) {
            return response()->json(['message' => 'Invalid Bearer token'], 401);
        }

        if ((int)$ride->driver_id !== (int)$driver_id) {
            return response()->json(['message' => 'This driver did not accept the ride.'], 403);
        }
        // Check if the OTP matches
        // if ($ride->otp !== $request->otp) {
        //     return response()->json(['message' => 'Invalid OTP.'], 400);
        // } 

        // Update the ride status to "on the way"
        $ride->update(['status' => 'on the way']);

        // Retrieve the user and driver details
        $user = UserRegister::find($ride->user_id);
        $driver = DriverRegister::find($ride->driver_id);

        // Return the details in the response
        return response()->json([
            'message' => 'OTP verified successfully. Ride status updated to "on the way".',
            'user_details' => $user,
            'driver_details' => $driver,
            'ride_details' => $ride,
        ]);
    }

    public function completeRide(Request $request, $ride_id, $driver_id)
    {
        // Retrieve the ride details
        $ride = Ride::find($ride_id);

        if (!$ride) {
            return response()->json(['message' => 'Ride not found.'], 404);
        }

        // Retrieve bearer token from request
        $bearerToken = $request->bearerToken();

        // Check if the driver exists and if the token matches
        $driver = DriverRegister::where('id', $driver_id)->first();

        if (!$driver) {
            return response()->json(['message' => 'Driver not found.'], 404);
        }

        if ($driver->bearer_token !== $bearerToken) {
            return response()->json(['message' => 'Invalid Bearer token'], 401);
        }

        // Check if the driver ID matches the driver who accepted the ride
        if ((int)$ride->driver_id !== (int)$driver_id) {
            return response()->json(['message' => 'This driver did not accept the ride.'], 403);
        }

        // Update the ride status to "completed"
        $ride->update(['status' => 'completed']);

        // Retrieve the user and driver details
        $user = UserRegister::find($ride->user_id);
        $driver = DriverRegister::find($ride->driver_id);

        // Return the details in the response
        return response()->json([
            'message' => 'Ride completed successfully.',
            'user_details' => $user,
            'driver_details' => $driver,
            'ride_details' => $ride,
        ]);
    }


    protected function sendFCMNotification($userId, $data)
    {
        // Retrieve the user's device ID (FCM token)
        $user = UserRegister::find($userId);

        if (!$user || !$user->device_id) {
            Log::warning('FCM token not found for user ID: ' . $userId);
            return;
        }

        $fcmToken = $user->device_id;

        // Prepare the notification data
        $notificationData = [
            'title' => 'Ride Accepted',
            'body' => 'Your ride has been accepted by ' . $data['full_name'] .
                '. OTP: ' . $data['otp'] . '. Vehicle: ' . $data['vehicle_type'] . '. Ride id: ' . $data['ride_id'],
            'data' => [
                'full_name' => (string)$data['full_name'],
                'vehicle_type' => (string)$data['vehicle_type'],
                'otp' => (string)$data['otp'],  // Cast OTP to string
                'ride_id' => (string)$data['ride_id'],
            ],
        ];

        // Retrieve the Firebase access token
        $accessToken = $this->firebaseService->getAccessToken();

        if (!$accessToken) {
            Log::error('Failed to retrieve Firebase access token.');
            return;
        }

        // Prepare the FCM request payload
        $fcmRequestData = [
            'message' => [
                'token' => $fcmToken,  // Use the retrieved FCM token
                'notification' => [
                    'title' => $notificationData['title'],
                    'body' => $notificationData['body'],
                ],
                'data' => $notificationData['data'],  // Use the string-cast data
            ],
        ];

        // Send the notification using FCM
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/v1/projects/cablow-driver/messages:send', $fcmRequestData);

        // Log the response from FCM
        if ($response->successful()) {
            Log::info('FCM notification sent successfully to user ID ' . $userId);
        } else {
            Log::error('Failed to send FCM notification to user ID ' . $userId . ': ' . $response->body());
        }
    }


    public function getUserRideHistory($user_id)
    {
        $rides = Ride::where('user_id', $user_id)->get();
        return response()->json($rides);
    }

  public function getDriverRideHistory($driver_id)
{
    // Fetch the ride history for the driver with the associated price and user details
    $rides = Ride::join('ride_requests', 'rides.ride_request_id', '=', 'ride_requests.id')
        ->join('user_register', 'rides.user_id', '=', 'user_register.id')
        ->select(
            'rides.*',
            'ride_requests.price',
            'user_register.full_name as user_name',
            'user_register.mobile_number as user_mobile',
            'user_register.email as user_email'
        )
        ->where('rides.driver_id', $driver_id) // Ensure the driver_id matches in the rides table
        ->get();

    // Return the data as a JSON response
    return response()->json($rides);
}




    public function getRideDetails(Request $request, $user_id, $ride_id)
    {
        // Retrieve the authenticated user by checking the bearer token
        $user = UserRegister::find($user_id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Retrieve bearer token from request
        $bearerToken = $request->bearerToken();

        // Check if user exists and if the token matches
        $user = UserRegister::where('id', $user_id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->bearer_token !== $bearerToken) {
            return response()->json(['message' => 'Invalid Bearer token'], 401);
        }

        // Retrieve the ride details using the ride ID and check if it belongs to the user
        $ride = Ride::where('id', $ride_id)->where('user_id', $user_id)->first();

        if (!$ride) {
            return response()->json(['message' => 'Ride not found or does not belong to the user.'], 404);
        }

        // Retrieve the driver details
        $driver = DriverRegister::find($ride->driver_id);

        if (!$driver) {
            return response()->json(['message' => 'Driver not found.'], 404);
        }

        // Retrieve the vehicle details
        $vehicle = DriverVehicleRegister::where('driver_id', $ride->driver_id)->first();

        // Return the ride details along with driver and vehicle information
        return response()->json([
            'message' => 'Ride details retrieved successfully.',
            'Ride Details' => $ride,
            'Driver Details' => $driver,
            'Vehicle Details' => $vehicle,
        ]);
    }
    
public function getRides($user_id, $datetime, Request $request)
{
    // Validate datetime parameter
    try {
        $endTime = Carbon::createFromFormat('Y-m-d H:i:s', $datetime);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Invalid datetime format.'], 400);
    }

    // Retrieve bearer token from request
    $bearerToken = $request->bearerToken();

    // Check if user exists and if the token matches
    $user = UserRegister::where('id', $user_id)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    if ($user->bearer_token !== $bearerToken) {
        return response()->json(['message' => 'Invalid Bearer token'], 401);
    }

    // Calculate the start time (10 minutes before the end time)
    $startTime = $endTime->copy()->subMinutes(10);

    // Query the database for rides created within the 10 minutes before the provided datetime
    $rides = Ride::where('user_id', $user_id)
        ->whereBetween('created_at', [$startTime, $endTime])  // Get rides between 10 minutes before and the given datetime
        ->get();

    // Check if rides are found
    if ($rides->isEmpty()) {
        return response()->json(['message' => 'No rides found for the specified criteria.'], 404);
    }

    // Return the rides data
    return response()->json($rides, 200);
}

    
    public function cancelRide(Request $request, $user_id, $ride_id)
{
    // Validate the request
    $validator = Validator::make($request->all(), [
        'reason' => 'nullable|string|max:255', // Optionally, you can log the reason for cancellation
    ]);

// Retrieve bearer token from request
        $bearerToken = $request->bearerToken();
    
        // Check if user exists and if the token matches
        $user = UserRegister::where('id', $user_id)->first();
    
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
    
        if ($user->bearer_token !== $bearerToken) {
            return response()->json(['message' => 'Invalid Bearer token'], 401);
        }
        
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Retrieve the ride
    $ride = Ride::where('id', $ride_id)->where('user_id', $user_id)->first();

    if (!$ride) {
        return response()->json(['message' => 'Ride not found or you do not have permission to cancel this ride.'], 404);
    }

    // Check if the ride is already completed or cancelled
    if ($ride->status == 'completed' || $ride->cancelled_at) {
        return response()->json(['message' => 'This ride has already been completed or cancelled.'], 400);
    }

    // Update the ride's status to 'cancelled'
    $ride->update([
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    // Optionally, log the cancellation reason
    // RideCancellation::create([
    //     'ride_id' => $ride_id,
    //     'user_id' => $user_id,
    //     'reason' => $request->reason,
    // ]);

    // Notify the driver that the ride was cancelled
    $this->sendCancellationNotificationToDriver($ride->driver_id, $ride->id);

    return response()->json([
        'message' => 'Ride has been cancelled successfully.',
        'ride_details' => $ride,
    ]);
}

protected function sendCancellationNotificationToDriver($driver_id, $ride_id)
{
    // Retrieve the driver's device ID (FCM token)
    $driver = DriverRegister::find($driver_id);

    if (!$driver || !$driver->device_id) {
        Log::warning('FCM token not found for driver ID: ' . $driver_id);
        return;
    }

    $fcmToken = $driver->device_id;

    // Prepare the notification data
    $notificationData = [
        'title' => 'Ride Cancelled',
        'body' => 'The ride with ID ' . $ride_id . ' has been cancelled by the user.',
        'data' => [
            'ride_id' => (string)$ride_id,
        ],
    ];

    // Retrieve the Firebase access token
    $accessToken = $this->firebaseService->getAccessToken();

    if (!$accessToken) {
        Log::error('Failed to retrieve Firebase access token.');
        return;
    }

    // Prepare the FCM request payload
    $fcmRequestData = [
        'message' => [
            'token' => $fcmToken,  // Use the retrieved FCM token
            'notification' => [
                'title' => $notificationData['title'],
                'body' => $notificationData['body'],
            ],
            'data' => $notificationData['data'],  // Use the string-cast data
        ],
    ];

    // Send the notification using FCM
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json',
    ])->post('https://fcm.googleapis.com/v1/projects/cablow-driver/messages:send', $fcmRequestData);

    // Log the response from FCM
    if ($response->successful()) {
        Log::info('FCM cancellation notification sent successfully to driver ID ' . $driver_id);
    } else {
        Log::error('Failed to send FCM cancellation notification to driver ID ' . $driver_id . ': ' . $response->body());
    }
}

}
