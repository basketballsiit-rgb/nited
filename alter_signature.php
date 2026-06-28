<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("ALTER TABLE lesson_plans ADD COLUMN signature_path TEXT NULL AFTER optional_sections");
    echo "Column signature_path added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
}
?>
