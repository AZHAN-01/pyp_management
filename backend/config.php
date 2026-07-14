<?php
// config.php - Stores sensitive API keys and configuration

require_once __DIR__ . '/env.php';

return [
    'captcha' => [
        // Using Cloudflare Turnstile by default
        'provider' => 'turnstile',
        
        // Read from .env
        'secret_key' => $_ENV['TURNSTILE_SECRET_KEY'] ?? '', 
        
        // Cloudflare Turnstile verification URL
        'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ]
];
?>
