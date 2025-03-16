<?php
return [
    'secret_key' => env('jwt.secret_key', 'TelegramGiftBotApi$%^'),
    'iss' => 'telegram-gift-api',
    'aud' => 'telegram-users',
    'ttl' => env('jwt.ttl', 3600), // 默认过期时间（秒）
    'refresh_ttl' => 604800, // 刷新Token过期时间（7天）
];