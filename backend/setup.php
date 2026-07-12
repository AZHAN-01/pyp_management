<?php
// setup.php - Run this once in the browser to initialize the database and table

$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default XAMPP password is empty

try {
    // 1. Connect without database to create it
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>Connected to MySQL server successfully.</h3>";

    // 2. Create the Database
    $sqlCreateDB = "CREATE DATABASE IF NOT EXISTS ku_pyp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sqlCreateDB);
    echo "<h3>Database 'ku_pyp_db' created or already exists.</h3>";

    // 3. Connect to the new database
    $pdo->exec("USE ku_pyp_db");

    // 4. Create the Table
    $sqlCreateTable = "
        CREATE TABLE IF NOT EXISTS papers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            studentName VARCHAR(255) NOT NULL,
            department VARCHAR(255) NOT NULL,
            batch VARCHAR(50) NOT NULL,
            paperName VARCHAR(255) NOT NULL,
            paperCode VARCHAR(50) NOT NULL,
            semester VARCHAR(50) NOT NULL,
            year INT NOT NULL,
            month VARCHAR(50) NOT NULL,
            fileName VARCHAR(255) NOT NULL,
            downloads INT DEFAULT 0,
            uploadDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_paper_name (paperName),
            INDEX idx_paper_code (paperCode),
            INDEX idx_semester (semester)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($sqlCreateTable);
    echo "<h3>Table 'papers' created or already exists.</h3>";

    // 5. Upgrade existing table if needed (adding columns or indexes)
    try {
        $pdo->exec("ALTER TABLE papers ADD COLUMN downloads INT DEFAULT 0 AFTER fileName");
        echo "<h3>Added 'downloads' column to existing table.</h3>";
    } catch (PDOException $e) {
        // Column already exists, ignore
    }
    
    try {
        $pdo->exec("ALTER TABLE papers ADD INDEX idx_paper_name (paperName)");
        echo "<h3>Added index 'idx_paper_name' to existing table.</h3>";
    } catch (PDOException $e) { /* Ignore if exists */ }

    try {
        $pdo->exec("ALTER TABLE papers ADD INDEX idx_paper_code (paperCode)");
        echo "<h3>Added index 'idx_paper_code' to existing table.</h3>";
    } catch (PDOException $e) { /* Ignore if exists */ }

    try {
        $pdo->exec("ALTER TABLE papers ADD INDEX idx_semester (semester)");
        echo "<h3>Added index 'idx_semester' to existing table.</h3>";
    } catch (PDOException $e) { /* Ignore if exists */ }

    // 6. Create Contact Messages Table
    $sqlCreateContactTable = "
        CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sqlCreateContactTable);
    echo "<h3>Table 'contact_messages' created or already exists.</h3>";

    echo "<h2 style='color:green;'>Setup Complete! You can close this tab and start using the app.</h2>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>Setup Failed: " . $e->getMessage() . "</h3>";
    echo "<p>Please ensure XAMPP MySQL is running.</p>";
}
?>
