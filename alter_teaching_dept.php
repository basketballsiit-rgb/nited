<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("ALTER TABLE supervisions ADD COLUMN teaching_department VARCHAR(100) NULL AFTER level");
    echo "Column teaching_department added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
