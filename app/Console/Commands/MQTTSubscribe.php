<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MQTTService;

class MQTTSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe to MQTT topics for sensor data';

    public function handle()
    {
        $this->info('Starting MQTT subscription...');
        $mqttService = new MQTTService();
        $mqttService->subscribe();
    }
}