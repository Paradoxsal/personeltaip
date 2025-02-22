<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    // app/Providers/AppServiceProvider.php
    public function boot()
    {
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Broadcasting\BroadcastEvent::class, function ($event) {
            \Log::info('Event Yayınlandı:', [
                'channel' => $event->broadcastOn()->name,
                'event' => $event->broadcastAs(),
                'data' => $event->broadcastWith()
            ]);
        });
    }
}
