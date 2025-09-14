<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttOneShotPublisher
{
    public static function publish(string $topic, string $payload, int $qos = 0, bool $retain = false): bool
    {
        $host = config('services.mqtt.host', env('MQTT_HOST', 'broker.hivemq.com'));
        $port = (int) config('services.mqtt.port', env('MQTT_PORT', 1883));
        $clientId = 'laravel-once-' . uniqid('', true);

        $client = new MqttClient($host, $port, $clientId);
        $settings = new ConnectionSettings();
        if (method_exists($settings, 'setKeepAliveInterval')) {
            $settings->setKeepAliveInterval(15);
        }

        try {
            $client->connect($settings, true);
            $client->publish($topic, $payload, $qos, $retain);
            $client->disconnect();

            Log::info('OneShot MQTT published', compact('topic', 'payload', 'qos', 'retain'));
            return true;
        } catch (\Throwable $e) {
            Log::error('OneShot MQTT publish failed: ' . $e->getMessage(), compact('topic'));
            try { $client->disconnect(); } catch (\Throwable $e2) {}
            return false;
        }
    }
}
