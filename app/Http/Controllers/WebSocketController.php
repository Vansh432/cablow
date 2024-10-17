<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebSocketController extends Controller
{
    public function test()
    {
        // WebSocket testing logic goes here.
        return response()->json(['message' => 'WebSocket test route.']);
    }
}
