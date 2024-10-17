<?php

namespace App\Http\Controllers;

use App\Models\UserRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;


class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'full_name' => 'nullable|string',
            'mobile_number' => 'required|numeric',
            'device_id' => 'required|string',
        ]);
    
        $mobileNumber = $request->input('mobile_number');
        $fullName = $request->input('full_name');
        $deviceId = $request->input('device_id');
        $otp = mt_rand(1000, 9999); // Generate a 4-digit OTP
        $otpExpiresAt = Carbon::now()->addMinutes(2); // OTP expires in 2 minutes
    
        // Check if the mobile number exists
        $user = DB::table('user_register')->where('mobile_number', $mobileNumber)->first();
    
        $bearerToken = Str::random(60); // Generate a new bearer token
    
        if ($user) {
            // Update existing record with new OTP and bearer token
            DB::table('user_register')
                ->where('mobile_number', $mobileNumber)
                ->update([
                    'full_name' => $fullName,
                    'otp' => $otp,
                    'otp_expires_at' => $otpExpiresAt,
                    'bearer_token' => $bearerToken, // Store plaintext token
                    'device_id' => $deviceId
                ]);
        } else {
            // Insert new record with OTP and bearer token
            DB::table('user_register')->insert([
                'full_name' => $fullName,
                'mobile_number' => $mobileNumber,
                'otp' => $otp,
                'otp_expires_at' => $otpExpiresAt,
                'bearer_token' => $bearerToken,
                'device_id' => $deviceId,
                'created_at' => now(),
                'updated_at' => now()
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
            'status' => $response->status(),
            'bearer_token' => $bearerToken,
            'full_name' => $fullName,
            'mobile_number' => $mobileNumber,
            'otp' => $otp
        ]);
    }
    
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|numeric',
            'otp' => 'required|string',
        ]);
    
        $mobileNumber = $request->input('mobile_number');
        $otp = $request->input('otp');
    
        // Extract bearer token from the header (if needed)
        $bearerToken = $request->bearerToken();
    
        // Check if the user exists
        $user = DB::table('user_register')->where('mobile_number', $mobileNumber)->first();
    
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        // Check token validity (if needed)
        if ($user->bearer_token !== $bearerToken) {
            return response()->json(['message' => 'Invalid Bearer token'], 400);
        }
    
        // Check OTP expiration
        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP expired'], 400);
        }
    
        // Check OTP validity
        if ($user->otp != $otp) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }
    
        return response()->json(['message' => 'OTP verified successfully', 'User Detail' => $user]);
    }
    
    public function resendOtp(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|numeric',
        ]);

        $mobileNumber = $request->input('mobile_number');
        $otp = mt_rand(1000, 9999); // Generate a 6-digit OTP
        $otpExpiresAt = Carbon::now()->addMinutes(2); // OTP expires in 2 minutes

        $user = DB::table('user_register')->where('mobile_number', $mobileNumber)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update the OTP and expiry time
        DB::table('user_register')
            ->where('mobile_number', $mobileNumber)
            ->update([
                'otp' => $otp,
                'otp_expires_at' => $otpExpiresAt,
            ]);

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

        return response()->json(['message' => 'OTP resent successfully!', 'status' => $response->status(), 'otp' => $otp,]);
    }
    
    public function getUser(Request $request, $id)
    {
        $user = UserRegister::find($id);

// Retrieve bearer token from request
    $bearerToken = $request->bearerToken();

    // Check if user exists and if the token matches
    $user = UserRegister::where('id', $id)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    if ($user->bearer_token !== $bearerToken) {
        return response()->json(['message' => 'Invalid Bearer token'], 401);
    }
    
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user, 200);
    }

    // Update user profile by ID
    public function updateUser(Request $request, $id)
    {
        $user = UserRegister::find($id);

// Retrieve bearer token from request
    $bearerToken = $request->bearerToken();

    // Check if user exists and if the token matches
    $user = UserRegister::where('id', $id)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    if ($user->bearer_token !== $bearerToken) {
        return response()->json(['message' => 'Invalid Bearer token'], 401);
    }
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update only the fields provided in the request
        $user->full_name = $request->input('full_name', $user->full_name);
        $user->email = $request->input('email', $user->email);
        $user->state = $request->input('state', $user->state);
        $user->city = $request->input('city', $user->city);
        $user->pin_code = $request->input('pin_code', $user->pin_code);
        $user->street_address = $request->input('street_address', $user->street_address);

        // Handle Base64 image upload
        if ($request->has('image')) {
            // Decode the Base64 string
            $image = base64_decode($request->input('image'));

            // Generate a unique filename
            $filename = 'user_' . $id . '.png';

            // Store the image in the specified path
            Storage::disk('local')->put("storage/app/{$filename}", $image);

            // Update the image path in the database
            $user->image = $filename;
        }

        // Save the updated user data
        $user->save();

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user], 200);
    }

}
