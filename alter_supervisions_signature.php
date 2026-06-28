<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("ALTER TABLE supervisions ADD COLUMN signature_path TEXT NULL AFTER photo_path_2");
    echo "Column signature_path added successfully to supervisions.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists in supervisions.\n";
    } else {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
}
?>
