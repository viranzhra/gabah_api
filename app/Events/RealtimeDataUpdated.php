<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class RealtimeDataUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $dryerId;
    public $payload;

    public function __construct($dryerId, $payload)
    {
        $this->dryerId = $dryerId;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('dryer.'.$this->dryerId);
    }

    public function broadcastAs()
    {
        return 'realtime.updated';
    }
}
