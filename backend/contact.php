<?php
header('Content-Type: application/json');

// Include database connection
require_once 'db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Get the raw POST data
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

// Check if data was decoded
if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input.']);
    exit;
}

// Validate inputs
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}

try {
    // Insert into database using prepared statements (prevents SQL injection)
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
    
    $stmt->execute([
        ':name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        ':email' => $email, // already validated
        ':subject' => htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
        ':message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
    ]);
    
    // Send Email to the owner
    $to = "mohdazhannajar@gmail.com";
    $email_subject = "New Contact Form Message: " . $subject;
    $email_body = "You have received a new message from the KU PYP Contact Form.\n\n".
                  "Name: $name\n".
                  "Email: $email\n".
                  "Subject: $subject\n\n".
                  "Message:\n$message\n";
    $headers = "From: noreply@localhost\r\n";
    $headers .= "Reply-To: $email\r\n";
    
    // Use @mail to suppress warnings if SMTP is not configured locally (e.g. default XAMPP)
    @mail($to, $email_subject, $email_body, $headers);
    
    echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully.']);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while saving your message. Please try again later.']);
}
?>
