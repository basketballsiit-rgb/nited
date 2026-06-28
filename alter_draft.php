<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("ALTER TABLE lp_criteria_items ADD COLUMN is_optional TINYINT(1) DEFAULT 0 AFTER is_header");
    echo "Column is_optional added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("ALTER TABLE lesson_plan_results ADD COLUMN is_draft TINYINT(1) DEFAULT 0 AFTER comment");
    echo "Column is_draft added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
