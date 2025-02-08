<?php
/*
    FILE INI SUDAH TIDAK DIPAKAI.
    TIDAK PERLU DIPEDULIKAN TAPI AKAN DIBIARKAN UNTUK JAGA-JAGA.
    AKHIRNYA TIDAK DIPAKAI PUN YA SUDAH
*/
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
    
    public function broadcastOn()
    {
        return new Channel('data-update');
    }

    public function broadcastAs()
    {
        return 'data_updated';
    }
}
