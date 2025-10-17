<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MQTTService;

class MQTTSubscribe extends Command
{
    protected $signature = 'mqtt';
    protected $description = 'Subscribe to MQTT topics for sensor data';

    public function handle()
    {
        $this->info('MQTT subscription is starting...');
        $mqttService = new MQTTService();
        $mqttService->subscribe();
    }
}