<?php
require_once __DIR__ . '/config/db.php';
try {
    // Check if column exists first
    $stmt = $pdo->query("SHOW COLUMNS FROM supervisions LIKE 'is_urgent'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE supervisions ADD COLUMN is_urgent TINYINT(1) DEFAULT 0");
        echo "Added column is_urgent. ";
    }
    
    // Update existing
    $count = $pdo->exec("UPDATE supervisions SET is_urgent = 1 WHERE ABS(TIMESTAMPDIFF(SECOND, created_at, scheduled_date)) < 60");
    echo "Updated $count rows.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
