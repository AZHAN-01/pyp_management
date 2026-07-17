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
if (preg_match('/^data:(application\/pdf|image\/\w+);.*base64,/', $imageDataUrl)) {
    $base64Str = substr($imageDataUrl, strpos($imageDataUrl, ',') + 1);
    $fileData = base64_decode($base64Str);
    
    if ($fileData === false) {
        echo json_encode(["status" => "error", "message" => "Base64 decode failed"]);
        exit;
    }

    // Magic Bytes Verification (Security check)
    $magicBytes = substr($fileData, 0, 5);
    if ($magicBytes !== '%PDF-') {
        echo json_encode(["status" => "error", "message" => "Security Check Failed: Uploaded file is not a valid PDF."]);
        exit;
    }
    
    $extension = 'pdf';
} else {
    echo json_encode(["status" => "error", "message" => "Invalid file data format"]);
    exit;
}

// Generate unique filename
$fileName = uniqid('paper_') . '.' . $extension;
$filePath = $uploadDir . $fileName;

$fileSavedLocal = file_put_contents($filePath, $fileData);
if (!$fileSavedLocal) {
    echo json_encode(["status" => "error", "message" => "Failed to save file locally"]);
    exit;
}

$dbFileName = $fileName;

// Upload to Cloudinary if configured
if (!empty($config['cloudinary']['cloud_name']) && (!empty($config['cloudinary']['upload_preset']) || (!empty($config['cloudinary']['api_key']) && !empty($config['cloudinary']['api_secret'])))) {
    $cloudName = $config['cloudinary']['cloud_name'];
    $url = "https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload";
    
    $postFields = [
        'file' => new CURLFile($filePath)
    ];

    if (!empty($config['cloudinary']['upload_preset'])) {
        $postFields['upload_preset'] = $config['cloudinary']['upload_preset'];
    } else {
        $timestamp = time();
        $apiSecret = $config['cloudinary']['api_secret'];
        $params = ['timestamp' => $timestamp];
        ksort($params);
        $signString = '';
        foreach ($params as $k => $v) {
            $signString .= $k . '=' . $v . '&';
        }
        $signString = rtrim($signString, '&');
        $signature = sha1($signString . $apiSecret);
        
        $postFields['api_key'] = $config['cloudinary']['api_key'];
        $postFields['timestamp'] = $timestamp;
        $postFields['signature'] = $signature;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        if (isset($result['secure_url'])) {
            $dbFileName = $result['secure_url'];
            // Optionally delete the local file since it's now on Cloudinary
            unlink($filePath);
        }
    } else {
        // Fallback to local file if Cloudinary upload fails
        error_log("Cloudinary upload failed: " . $response);
    }
}

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
        $dbFileName
    ]);
    
    echo json_encode(["status" => "success", "message" => "Upload Successful! Your paper is under review by an admin."]);
} catch (\PDOException $e) {
    // If it was a local file, delete it on db failure
    if (!filter_var($dbFileName, FILTER_VALIDATE_URL) && file_exists($uploadDir . $dbFileName)) {
        unlink($uploadDir . $dbFileName);
    }
    echo json_encode(["status" => "error", "message" => "Database insert failed: " . $e->getMessage()]);
}
?>
