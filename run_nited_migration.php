<?php
// run_nited_migration.php
require_once __DIR__ . '/config/db.php';

try {
    if (!$pdo) {
        throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
    }

    // 1. ตรวจสอบว่ามีแถว 'รายวิชาระยะสั้น' หรือยัง
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
    $stmt->execute(['รายวิชาระยะสั้น']);
    $dept = $stmt->fetch();
    
    if (!$dept) {
        $stmtInsert = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
        $stmtInsert->execute(['รายวิชาระยะสั้น']);
        $new_id = $pdo->lastInsertId();
        echo json_encode([
            "status" => "success", 
            "message" => "เพิ่มแผนกวิชา 'รายวิชาระยะสั้น' สำเร็จ (ID: $new_id)"
        ]);
    } else {
        echo json_encode([
            "status" => "success", 
            "message" => "แผนกวิชา 'รายวิชาระยะสั้น' มีอยู่ในระบบอยู่แล้ว (ID: {$dept['id']})"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "status" => "error", 
        "message" => $e->getMessage()
    ]);
}
?>
