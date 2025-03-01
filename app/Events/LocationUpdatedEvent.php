<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class LocationUpdatedEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $userId;
    public $latitude;
    public $longitude;

    public function __construct($userId, $latitude, $longitude)
    {
        $this->userId = $userId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function broadcastOn()
    {
        return new Channel('mobilpersonel-development');
    }

    public function broadcastAs()
    {
        return 'location.updated';
    }
    
    public function broadcastWith()
    {
        return [
            'userId'    => $this->userId,
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
