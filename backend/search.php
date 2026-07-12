<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

// Get query parameters
$searchPaperName = isset($_GET['paperName']) ? trim($_GET['paperName']) : '';
$searchPaperCode = isset($_GET['paperCode']) ? trim($_GET['paperCode']) : '';
$searchSemester = isset($_GET['semester']) ? $_GET['semester'] : '';

// Build dynamic SQL query
$query = "SELECT * FROM papers WHERE 1=1";
$params = [];

// Handle Name OR Code
if ($searchPaperName !== '' || $searchPaperCode !== '') {
    $query .= " AND (";
    $conditions = [];
    
    if ($searchPaperName !== '') {
        $conditions[] = "paperName LIKE ?";
        $params[] = '%' . $searchPaperName . '%';
    }
    
    if ($searchPaperCode !== '') {
        $conditions[] = "paperCode LIKE ?";
        $params[] = '%' . $searchPaperCode . '%';
    }
    
    $query .= implode(" OR ", $conditions) . ")";
}

if ($searchSemester !== '') {
    $query .= " AND semester = ?";
    $params[] = $searchSemester;
}

$query .= " ORDER BY uploadDate DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    echo json_encode(["status" => "success", "data" => $results]);
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database search failed: " . $e->getMessage()]);
}
?>
