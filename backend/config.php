<?php
// config.php - Stores sensitive API keys and configuration

// Simple .env parser
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

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
