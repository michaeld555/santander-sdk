<?php

declare(strict_types=1);

return [
    'client_id' => env('SANTANDER_CLIENT_ID'),
    'client_secret' => env('SANTANDER_CLIENT_SECRET'),
    'cert' => env('SANTANDER_CERT'),
    'base_url' => env('SANTANDER_BASE_URL'),
    'workspace_id' => env('SANTANDER_WORKSPACE_ID', ''),
    'log_request_response_level' => env('SANTANDER_LOG_LEVEL', 'ERROR'),
    'timeout' => (int) env('SANTANDER_TIMEOUT', 60),
];