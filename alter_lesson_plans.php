<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("ALTER TABLE lesson_plans MODIFY COLUMN status ENUM('pending', 'draft', 'approved', 'revision', 'rejected') DEFAULT 'pending'");
    echo "Status ENUM updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating ENUM: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE lesson_plans ADD COLUMN optional_sections TEXT NULL AFTER review_comment");
    echo "Column optional_sections added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
}
?>
