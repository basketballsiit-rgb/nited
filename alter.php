<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("ALTER TABLE lp_criteria_items ADD COLUMN is_header TINYINT(1) DEFAULT 0 AFTER category_id");
    echo "Column is_header added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
