<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

try {
    // Get total uploads
    $stmtUploads = $pdo->query("SELECT COUNT(id) as totalUploads FROM papers");
    $totalUploads = $stmtUploads->fetchColumn() ?: 0;
    
    // Get total downloads
    $stmtDownloads = $pdo->query("SELECT SUM(downloads) as totalDownloads FROM papers");
    $totalDownloads = $stmtDownloads->fetchColumn() ?: 0;
    
    echo json_encode([
        "status" => "success", 
        "data" => [
            "totalUploads" => (int)$totalUploads,
            "totalDownloads" => (int)$totalDownloads
        ]
    ]);
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
