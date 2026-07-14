<?php
require_once __DIR__ . '/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Invalid Paper ID");
}

$id = (int)$_GET['id'];

try {
    // 1. Fetch paper details to get the filename
    $stmt = $pdo->prepare("SELECT fileName, paperCode, year FROM papers WHERE id = ?");
    $stmt->execute([$id]);
    $paper = $stmt->fetch();
    
    if (!$paper) {
        http_response_code(404);
        die("Paper not found");
    }
    
    $fileName = $paper['fileName'];
    $filePath = __DIR__ . '/uploads/' . $fileName;
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        die("File is missing on the server");
    }
    
    // 2. Increment download count
    $updateStmt = $pdo->prepare("UPDATE papers SET downloads = downloads + 1 WHERE id = ?");
    $updateStmt->execute([$id]);
    
    // 3. Serve the file
    $extension = pathinfo($paper['fileName'], PATHINFO_EXTENSION);
    if (!$extension) $extension = 'pdf';
    $downloadName = $paper['paperCode'] . "_" . $paper['year'] . "." . $extension;
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    
    readfile($filePath);
    exit;

} catch (\PDOException $e) {
    http_response_code(500);
    die("Database error: " . $e->getMessage());
}
?>
