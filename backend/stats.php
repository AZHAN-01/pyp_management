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

    // Get uploads per semester
    $stmtSemesters = $pdo->query("SELECT semester, COUNT(id) as count FROM papers GROUP BY semester");
    $semesterData = $stmtSemesters->fetchAll(PDO::FETCH_ASSOC);

    // Get top contributors
    $stmtContributors = $pdo->query("SELECT studentName, department, COUNT(id) as uploads FROM papers GROUP BY studentName, department ORDER BY uploads DESC LIMIT 3");
    $topContributors = $stmtContributors->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success", 
        "data" => [
            "totalUploads" => (int)$totalUploads,
            "totalDownloads" => (int)$totalDownloads,
            "semesterData" => $semesterData,
            "topContributors" => $topContributors
        ]
    ]);
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
