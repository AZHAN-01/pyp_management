<?php
session_start();
header('Content-Type: application/json');

// Security check: Must be logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

require_once __DIR__ . '/db.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($id <= 0 || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(["status" => "error", "message" => "Invalid parameters."]);
    exit;
}

try {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE papers SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["status" => "success"]);
    } 
    else if ($action === 'reject') {
        // First get the filename so we can delete the physical file
        $stmt = $pdo->prepare("SELECT fileName FROM papers WHERE id = ?");
        $stmt->execute([$id]);
        $paper = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($paper) {
            $filePath = __DIR__ . '/../uploads/' . $paper['fileName'];
            // Delete file if it exists
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete from DB
            $stmtDel = $pdo->prepare("DELETE FROM papers WHERE id = ?");
            $stmtDel->execute([$id]);
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Paper not found."]);
        }
    }
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
