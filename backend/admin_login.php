<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/env.php';

define('ADMIN_PASSWORD', $_ENV['ADMIN_PASSWORD'] ?? 'azhanisadmin@123');

$data = json_decode(file_get_contents("php://input"), true);
$password = $data['password'] ?? '';

if ($password === ADMIN_PASSWORD) {
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Incorrect password"]);
}
?>
