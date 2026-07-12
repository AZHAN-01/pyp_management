<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

require_once __DIR__ . '/db.php';

// Ensure uploads directory exists
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

// CAPTCHA Verification
$config = require __DIR__ . '/config.php';
$captchaToken = isset($data['captcha_token']) ? $data['captcha_token'] : '';

if (empty($captchaToken)) {
    echo json_encode(["status" => "error", "message" => "CAPTCHA token is missing."]);
    exit;
}

$verifyData = [
    'secret' => $config['captcha']['secret_key'],
    'response' => $captchaToken,
    'remoteip' => $_SERVER['REMOTE_ADDR']
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($verifyData)
    ]
];
$context  = stream_context_create($options);
$verifyResult = file_get_contents($config['captcha']['verify_url'], false, $context);
$captchaResponse = json_decode($verifyResult);

if (!$captchaResponse || !$captchaResponse->success) {
    // Log failed attempt
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/captcha.log';
    $logMsg = date('Y-m-d H:i:s') . " - CAPTCHA Failed - IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    file_put_contents($logFile, $logMsg, FILE_APPEND);

    echo json_encode(["status" => "error", "message" => "CAPTCHA verification failed. Please try again."]);
    exit;
}

$imageDataUrl = $data['image'];

// Extract base64 string from data URL
if (preg_match('/^data:image\/(\w+);base64,/', $imageDataUrl, $type)) {
    $imageDataUrl = substr($imageDataUrl, strpos($imageDataUrl, ',') + 1);
    $type = strtolower($type[1]); // jpg, png, jpeg
    
    if (!in_array($type, [ 'jpg', 'jpeg', 'png' ])) {
        echo json_encode(["status" => "error", "message" => "Invalid image type"]);
        exit;
    }
    
    $imageData = base64_decode($imageDataUrl);
    
    if ($imageData === false) {
        echo json_encode(["status" => "error", "message" => "Base64 decode failed"]);
        exit;
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid image data format"]);
    exit;
}

// Generate unique filename
$fileName = uniqid('paper_') . '.' . $type;
$filePath = $uploadDir . $fileName;

// Save image
if (file_put_contents($filePath, $imageData)) {
    
    // Insert into MySQL Database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO papers 
            (studentName, department, batch, paperName, paperCode, semester, year, month, fileName) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['studentName'],
            $data['department'],
            $data['batch'],
            $data['paperName'],
            $data['paperCode'],
            $data['semester'],
            (int)$data['year'],
            $data['month'],
            $fileName
        ]);
        
        echo json_encode(["status" => "success", "message" => "Paper uploaded successfully!"]);
    } catch (\PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database insert failed: " . $e->getMessage()]);
    }
    
} else {
    echo json_encode(["status" => "error", "message" => "Failed to save image"]);
}
?>
