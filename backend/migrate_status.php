<?php
// backend/migrate_status.php
require_once __DIR__ . '/db.php';

try {
    // Check if column exists first
    $checkSql = "SHOW COLUMNS FROM papers LIKE 'status'";
    $stmt = $pdo->query($checkSql);
    
    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, so add it
        $sql = "ALTER TABLE papers ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'";
        $pdo->exec($sql);
        
        // Update all existing papers to 'approved' so they don't disappear
        $updateSql = "UPDATE papers SET status = 'approved'";
        $pdo->exec($updateSql);
        
        echo "Successfully added 'status' column and approved all existing papers!";
    } else {
        echo "The 'status' column already exists in the papers table.";
    }
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
