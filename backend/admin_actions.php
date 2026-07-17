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
        $config = require __DIR__ . '/config.php';
        
        if ($paper) {
            $fileName = $paper['fileName'];
            $isCloudinary = filter_var($fileName, FILTER_VALIDATE_URL) !== false;
            
            if ($isCloudinary) {
                // Delete from Cloudinary
                if (!empty($config['cloudinary']['api_key']) && !empty($config['cloudinary']['api_secret'])) {
                    if (preg_match('/\/upload\/(?:v\d+\/)?([^\.]+)/', $fileName, $matches)) {
                        $publicId = $matches[1];
                        $cloudName = $config['cloudinary']['cloud_name'];
                        $timestamp = time();
                        $params = [
                            'public_id' => $publicId,
                            'timestamp' => $timestamp
                        ];
                        ksort($params);
                        $signString = '';
                        foreach ($params as $k => $v) {
                            $signString .= $k . '=' . $v . '&';
                        }
                        $signString = rtrim($signString, '&');
                        $signature = sha1($signString . $config['cloudinary']['api_secret']);
                        
                        $postFields = [
                            'public_id' => $publicId,
                            'api_key' => $config['cloudinary']['api_key'],
                            'timestamp' => $timestamp,
                            'signature' => $signature
                        ];
                        
                        // Using 'image' resource type as PDF usually gets uploaded as image on Cloudinary
                        $ch = curl_init("https://api.cloudinary.com/v1_1/$cloudName/image/destroy");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                        curl_exec($ch);
                        curl_close($ch);
                    }
                }
            } else {
                $filePath = __DIR__ . '/uploads/' . $fileName;
                // Delete file if it exists
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
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
