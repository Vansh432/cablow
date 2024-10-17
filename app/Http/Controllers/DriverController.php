<?php

namespace App\Http\Controllers;

use App\Models\DriverRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Events\DriverLocationUpdated;


class DriverController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|numeric',
            'device_id' => 'required|string',
        ]);

        $mobileNumber = $request->input('mobile_number');
        $deviceId = $request->input('device_id');
        $otp = mt_rand(1000, 9999);
        $otpExpiresAt = Carbon::now()->addMinutes(2);
        $bearerToken = Str::random(60);

        $driver = DB::table('driver_register')->where('mobile_number', $mobileNumber)->first();

        if ($driver) {
            // Update existing driver
            DB::table('driver_register')
                ->where('mobile_number', $mobileNumber)
                ->update([
                    'otp' => $otp,
                    'otp_expires_at' => $otpExpiresAt,
                    'bearer_token' => $bearerToken,
                    'device_id' => $deviceId,
                    'updated_at' => now(),
                ]);
            $driverId = $driver->id; // Get the existing driver's ID
        } else {
            // Insert new driver and get the inserted ID
            $driverId = DB::table('driver_register')->insertGetId([
                'mobile_number' => $mobileNumber,
                'otp' => $otp,
                'otp_expires_at' => $otpExpiresAt,
                'bearer_token' => $bearerToken,
                'device_id' => $deviceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Send OTP via SMS
        $response = Http::withHeaders([
            'Authorization' => 'Bearer APIwOKDvVaA132231',
            'Content-Type' => 'application/json',
        ])->post('https://www.bulksmsplans.com/api/send_sms', [
            'message' => "Dear user, your AGNRS OTP for account Registration is $otp CABLOW.",
            'api_id' => 'APIwOKDvVaA132231',
            'api_password' => 'oFw6WGYu',
            'sms_type' => 'Transactional',
            'number' => $mobileNumber,
            'sender' => 'AGNRSI',
            'template_id' => '164191',
            'sms_encoding' => '1',
            'DLT_template_id' => '1707172075821643669',
        ]);

        return response()->json([
            'message' => 'OTP sent successfully!',
            'bearer_token' => $bearerToken,
            'mobile_number' => $mobileNumber,
            'otp' => $otp,
            'device_id' => $deviceId,
            'driver_id' => $driverId, // Include the driver's ID in the response
        ]);
    }



    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|numeric',
            'otp' => 'required|string'
        ]);

        $driver = $request->attributes->get('driver');

        if (!$driver) {
            return response()->json(['message' => 'Driver not authenticated'], 401);
        }

        $mobileNumber = $request->input('mobile_number');
        $otp = $request->input('otp');

        $bearerToken = $request->bearerToken();

        // Fetch the driver record
        $driver = DB::table('driver_register')->where('mobile_number', $mobileNumber)->first();

        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }

        // Validate the Bearer token
        if ($driver->bearer_token != $bearerToken) {
            return response()->json(['message' => 'Invalid Bearer token'], 400);
        }

        // Check if OTP has expired
        if (Carbon::now()->greaterThan($driver->otp_expires_at)) {
            return response()->json(['message' => 'OTP expired'], 400);
        }

        // Validate the OTP
        if ($driver->otp != $otp) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        // Check if the driver's vehicle is registered
        $vehicleRegister = DB::table('driver_vehicle_register')
            ->where('driver_id', $driver->id)
            ->first();

        if (!$vehicleRegister) {
            return response()->json(['message' => 'Your vehicle is not registered'], 250);
        }

        // Check if the vehicle's status is approved
        if ($vehicleRegister->status != 'approved') {
            return response()->json(['message' => 'Your vehicle status is not approved'], 300);
        }

        // Update driver status to online
        DB::table('driver_register')
            ->where('mobile_number', $mobileNumber)
            ->update(['status' => 'online']);

        return response()->json(['message' => 'OTP verified successfully', 'Driver Details' => $driver]);
    }


    public function resendOtp(Request $request)
    {

        $request->validate([
            'mobile_number' => 'required|numeric'
        ]);

        $mobileNumber = $request->input('mobile_number');
        $otp = mt_rand(1000, 9999);
        $otpExpiresAt = Carbon::now()->addMinutes(2);

        $driver = DB::table('driver_register')->where('mobile_number', $mobileNumber)->first();

        if ($driver) {
            DB::table('driver_register')
                ->where('mobile_number', $mobileNumber)
                ->update([
                    'otp' => $otp,
                    'otp_expires_at' => $otpExpiresAt,
                ]);
        } else {
            return response()->json(['message' => 'Driver not found'], 404);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer APIwOKDvVaA132231',
            'Content-Type' => 'application/json',
        ])->post('https://www.bulksmsplans.com/api/send_sms', [
            'message' => "Dear user, your AGNRS OTP for account Registration is $otp CABLOW.",
            'api_id' => 'APIwOKDvVaA132231',
            'api_password' => 'oFw6WGYu',
            'sms_type' => 'Transactional',
            'number' => $mobileNumber,
            'sender' => 'AGNRSI',
            'template_id' => '164191',
            'sms_encoding' => '1',
            'DLT_template_id' => '1707172075821643669',
        ]);

        return response()->json([
            'message' => 'OTP resent successfully!',
            'otp' => $otp,
        ]);
    }

    public function updateProfile(Request $request, $id)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255', // Add validation for full name
            'image' => 'nullable|string', // Expect base64 string
            'email' => 'nullable|email',
            'state' => 'nullable|string',
            'city' => 'nullable|string',
            'street_address' => 'nullable|string',
            'pin_code' => 'nullable|numeric',
            'dob' => 'nullable|date',
            'vehicle_type' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Fetch driver by ID
        $driver = DriverRegister::find($id);
    
        if (!$driver) {
            return response()->json(['message' => 'Driver not found.'], 404);
        }
    
        // Check if the token belongs to the driver being updated
        $token = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $token);
    
        if ($driver->bearer_token !== $token) {
            return response()->json(['message' => 'Unauthorized: token does not match driver.'], 401);
        }
    
        // Handle base64 image upload
        if ($request->has('image')) {
            // (Existing image handling code here...)
        }
    
        // Update driver fields
        $driver->full_name = $request->input('full_name', $driver->full_name); // Update full name
        $driver->email = $request->input('email', $driver->email);
        $driver->state = $request->input('state', $driver->state);
        $driver->city = $request->input('city', $driver->city);
        $driver->street_address = $request->input('street_address', $driver->street_address);
        $driver->pin_code = $request->input('pin_code', $driver->pin_code);
        $driver->dob = $request->input('dob', $driver->dob);
        $driver->vehicle_type = $request->input('vehicle_type', $driver->vehicle_type);
    
        // Update latitude and longitude and dispatch event if they are provided
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
    
        if ($latitude !== null && $longitude !== null) {
            $driver->latitude = $latitude;
            $driver->longitude = $longitude;
            $driver->save();
    
            // Dispatch the event
            event(new DriverLocationUpdated($driver));
        }
    
        return response()->json([
            'message' => 'Profile updated successfully.',
            'Driver Details' => $driver,
        ]);
    }
    
}
