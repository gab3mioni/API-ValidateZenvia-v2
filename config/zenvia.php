<?php

return [
    'token' => env('ZENVIA_TOKEN'),
    'url' => env('ZENVIA_URL', 'https://api.zenvia.com/v2/templates'),
    'sender_phone' => env('ZENVIA_SENDER_PHONE'),
    'sender_email' => env('ZENVIA_SENDER_EMAIL'),
    'channel' => env('ZENVIA_CHANNEL'),
];
