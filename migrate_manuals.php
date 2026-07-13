<?php
require_once __DIR__ . '/config/db.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS `manuals` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `role_target` enum('teacher','supervisor','executive','all') NOT NULL DEFAULT 'all',
      `title` varchar(255) NOT NULL,
      `file_path` varchar(255) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "<h1>Table 'manuals' created successfully.</h1>";
    echo "<p>คุณสามารถปิดหน้านี้และกลับไปใช้งานระบบได้เลยครับ</p>";
} catch (PDOException $e) {
    echo "<h1>Error: " . $e->getMessage() . "</h1>";
}
?>
