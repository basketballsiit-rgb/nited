<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->beginTransaction();

    // Check if column exists before adding to avoid errors
    $stmt = $pdo->query("SHOW COLUMNS FROM `supervisions` LIKE 'teacher_signature_path'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `supervisions` ADD `teacher_signature_path` TEXT NULL AFTER `signature_path`, ADD `teacher_signed_at` DATETIME NULL AFTER `teacher_signature_path`");
        echo "Added teacher_signature_path to supervisions.<br>";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM `lesson_plans` LIKE 'teacher_signature_path'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `lesson_plans` ADD `teacher_signature_path` TEXT NULL AFTER `signature_path`, ADD `teacher_signed_at` DATETIME NULL AFTER `teacher_signature_path`");
        echo "Added teacher_signature_path to lesson_plans.<br>";
    }

    $pdo->commit();
    echo "<h1>Database updated successfully.</h1>";
    echo "<p>คุณสามารถปิดหน้านี้และกลับไปใช้งานระบบได้เลยครับ</p>";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<h1>Error: " . $e->getMessage() . "</h1>";
}
?>
