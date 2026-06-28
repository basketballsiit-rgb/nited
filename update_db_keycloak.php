<?php
// update_db_keycloak.php
// สคริปต์สำหรับอัปเดตฐานข้อมูลบนเซิร์ฟเวอร์จริงให้รองรับ Keycloak

require_once 'config/db.php';

try {
    // 1. เพิ่มคอลัมน์ email
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL AFTER name");
        echo "เพิ่มคอลัมน์ 'email' สำเร็จ<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "คอลัมน์ 'email' มีอยู่แล้ว<br>";
        } else {
            throw $e;
        }
    }

    // 2. เพิ่มคอลัมน์ profile_picture
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER department");
        echo "เพิ่มคอลัมน์ 'profile_picture' สำเร็จ<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "คอลัมน์ 'profile_picture' มีอยู่แล้ว<br>";
        } else {
            throw $e;
        }
    }

    echo "<br><b style='color:green;'>อัปเดตฐานข้อมูลเสร็จสมบูรณ์! สามารถลบไฟล์นี้ทิ้งได้เลยครับ</b>";
} catch (PDOException $e) {
    echo "<b style='color:red;'>เกิดข้อผิดพลาด:</b> " . $e->getMessage();
}
?>
