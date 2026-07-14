<?php
// env.php - Simple .env parser to load environment variables

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignore comments
        if (strpos($line, '#') === 0) continue;
        
        // Parse key-value pairs
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            // Remove quotes if present
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[trim($name)] = $value;
        }
    }
}
?>
