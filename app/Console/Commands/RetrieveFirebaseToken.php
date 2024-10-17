<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class RetrieveFirebaseToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:retrieve-firebase-token';
    protected $signature = 'firebase:token';
    protected $description = 'Retrieve and log Firebase access token';

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * The console command description.
     *
     * @var string
     */
    // protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $firebaseService = new FirebaseService();
        $accessToken = $firebaseService->getAccessToken();
        Log::info('Firebase Access Token:', ['token' => $accessToken]);
        $this->info('Firebase access token retrieved and logged successfully.');
    }
}
