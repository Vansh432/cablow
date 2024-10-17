<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $driver_id;
    public $latitude;
    public $longitude;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($driver_id, $latitude, $longitude)
    {
        $this->driver_id = $driver_id;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('driver-location.' . $this->driver_id);
    }

    public function broadcastWith()
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    
}
