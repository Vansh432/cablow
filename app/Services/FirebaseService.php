<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google_Client;
use Illuminate\Support\Facades\Storage;

class FirebaseService
{
    protected $credentials;
    protected $credentialsPath;

    public function __construct()
    {
        $this->credentialsPath = storage_path('app/firebase/credentials.json');

        if (!file_exists($this->credentialsPath)) {
            throw new \Exception('Firebase credentials file not found.');
        }

        $this->credentials = json_decode(file_get_contents($this->credentialsPath), true);
    }

    public function getAccessToken()
    {
        // Initialize the Google Client
        $client = new Google_Client();
        $client->setAuthConfig($this->credentials);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->setSubject($this->credentials['client_email']);

        // Fetch the access token
        $accessToken = $client->fetchAccessTokenWithAssertion();
        return $accessToken['access_token'];
    }
}
