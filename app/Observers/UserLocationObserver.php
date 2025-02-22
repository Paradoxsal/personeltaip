<?php
namespace App\Observers;

use App\Models\UserLocation;
use App\Events\LocationUpdatedEvent;

class UserLocationObserver
{
    public function created(UserLocation $userLocation)
    {
        // Test amaçlı toOthers() kaldırıldı
        broadcast(new LocationUpdatedEvent(
            $userLocation->user_id,
            $userLocation->latitude,
            $userLocation->longitude
        ));
    }
}
