<?php

return [
    'paths' => ['api/*', 'broadcasting/auth'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://gabahapi.test', 'http://gabah.test', 'http://localhost:3000', 'http://127.0.0.1:5000', 'http://localhost:5000', 'http://192.168.43.142:8000', 'http://192.168.1.23:3333', 'http://127.0.0.1:3333'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];