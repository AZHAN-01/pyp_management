<?php
// Default XAMPP MySQL credentials
// Rename this file to db.php and replace these with your actual database credentials
$host = '127.0.0.1';
$db   = 'ku_pyp_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If the database doesn't exist yet, we will catch it here and return a friendly JSON error
    // Alternatively, if this is included from setup.php, we handle it there differently.
    
    // Check if the script is setup.php calling this to connect without db
    if (basename($_SERVER['PHP_SELF']) !== 'setup.php') {
        echo json_encode(["status" => "error", "message" => "Database connection failed. Did you run setup.php first? Error: " . $e->getMessage()]);
        exit;
    }
}
?>
