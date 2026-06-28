<?php
require_once __DIR__ . '/config/db.php';

try {
    // Users: Add department
    $pdo->exec("ALTER TABLE users ADD COLUMN department VARCHAR(255) DEFAULT NULL AFTER position");
    echo "Column 'department' added to 'users' table.\n";
} catch (PDOException $e) {
    echo "Info (users): " . $e->getMessage() . "\n";
}

try {
    // Supervisions: Add subject_code and level
    $pdo->exec("ALTER TABLE supervisions ADD COLUMN subject_code VARCHAR(50) NOT NULL AFTER academic_year_id");
    $pdo->exec("ALTER TABLE supervisions ADD COLUMN level VARCHAR(50) NOT NULL AFTER subject_name");
    echo "Columns 'subject_code' and 'level' added to 'supervisions' table.\n";
} catch (PDOException $e) {
    echo "Info (supervisions): " . $e->getMessage() . "\n";
}

try {
    // Lesson Plans: Add subject_code and level
    $pdo->exec("ALTER TABLE lesson_plans ADD COLUMN subject_code VARCHAR(50) NOT NULL AFTER academic_year_id");
    $pdo->exec("ALTER TABLE lesson_plans ADD COLUMN level VARCHAR(50) NOT NULL AFTER subject_name");
    echo "Columns 'subject_code' and 'level' added to 'lesson_plans' table.\n";
} catch (PDOException $e) {
    echo "Info (lesson_plans): " . $e->getMessage() . "\n";
}

echo "Database schema update completed.\n";
?>
