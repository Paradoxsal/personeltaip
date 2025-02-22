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

    /**
     * Yayınlanacak kanal bilgisini döndür.
     */
    public function broadcastOn()
    {
        return new Channel('location-updates');
    }

    // Eğer broadcastAs() metodunu kullanıyorsanız:
    public function broadcastAs()
    {
        return 'location.updates';
    }
    public function broadcastWith()
    {
        \Log::info('Konum güncellendi:', [
            'user_id' => $this->userId,
            'lat' => $this->latitude,
            'lng' => $this->longitude
        ]);
       
    }
}
