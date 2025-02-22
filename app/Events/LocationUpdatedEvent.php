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

    /**
     * Event oluşturulurken gönderilecek verileri al.
     */
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

    public function broadcastAs() // ✅ Event öneki tanımlandı
    {
        return 'location.updated'; // ✅ Doğru event adı
    }

}
