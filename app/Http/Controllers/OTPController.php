<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OTPController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required|numeric',
        ]);

        $mobileNumber = $request->input('mobile_number');
        $otp = mt_rand(100000, 999999); // Generate a 6-digit OTP

        // Check if the mobile number exists
        $user = DB::table('user_register')->where('mobile_number', $mobileNumber)->first();

        if ($user) {
            // Update existing OTP
            DB::table('user_register')->where('mobile_number', $mobileNumber)->update(['otp' => $otp]);
        } else {
            // Insert new record with OTP
            DB::table('user_register')->insert(['mobile_number' => $mobileNumber, 'otp' => $otp]);
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

        return response()->json(['message' => 'OTP sent successfully!', 'status' => $response->status()]);
    }
}
