<?php
// config/viettelpost.php

return [
    // API Configuration
    'api_url' => env('VIETTELPOST_API_URL', 'https://partner.viettelpost.vn/v2'),
    'username' => env('VIETTELPOST_USERNAME'),
    'password' => env('VIETTELPOST_PASSWORD'),
    'sender_name' => env('VIETTELPOST_SENDER_NAME', 'Ngô Nguyễn'),
    'sender_phone' => env('VIETTELPOST_SENDER_PHONE', '0966074330'),
    'sender_address' => env('VIETTELPOST_SENDER_ADDRESS', '22 Cầu Diễn, Bắc Từ Liêm, Hà Nội, Việt Nam'),
    'token' => env('VIETTELPOST_TOKEN'),

    // NEW: Địa chỉ để tính phí
    'sender_province_id' => env('VIETTELPOST_SENDER_PROVINCE_ID', ''), // VD: '1' (Hà Nội)
    'sender_district_id' => env('VIETTELPOST_SENDER_DISTRICT_ID', ''), // VD: '1' (Ba Đình)
    
    // Cache settings
    'cache_ttl' => 86400, // 24 hours
];
