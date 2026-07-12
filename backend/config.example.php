<?php
// config.example.php - Example configuration file

return [
    'captcha' => [
        'provider' => 'turnstile',
        'secret_key' => 'YOUR_TURNSTILE_SECRET_KEY_HERE', 
        'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ]
];
?>
