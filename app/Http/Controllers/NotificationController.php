<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class NotificationController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function sendNotification(Request $request)
    {
        $accessToken = $this->firebaseService->getAccessToken();
        Log::info('Access Token:', ['token' => $accessToken]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/v1/projects/cablow-driver/messages:send', [
            'message' => [
                'token' => $request->input('token'),
                'notification' => [
                    'title' => 'FCM Message',
                    'body' => 'This is an FCM notification message!',
                ],
            ],
        ]);

        Log::info('FCM Response:', $response->json());
        return $response->json();
    }
}
