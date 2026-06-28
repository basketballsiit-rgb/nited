<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $name = trim($_POST['name']);
            if (empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อสาขาวิชา']);
                exit;
            }

            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'มีสาขาวิชานี้ในระบบแล้ว']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
            $stmt->execute([$name]);

            echo json_encode(['status' => 'success', 'message' => 'เพิ่มสาขาวิชาเรียบร้อยแล้ว']);

        } elseif ($action === 'update') {
            $id = $_POST['id'];
            $name = trim($_POST['name']);
            if (empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อสาขาวิชา']);
                exit;
            }

            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'มีสาขาวิชานี้ในระบบแล้ว']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE departments SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);

            echo json_encode(['status' => 'success', 'message' => 'อัปเดตสาขาวิชาเรียบร้อยแล้ว']);

        } elseif ($action === 'delete') {
            $id = $_POST['id'];

            $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['status' => 'success', 'message' => 'ลบสาขาวิชาเรียบร้อยแล้ว']);

        } else {
            echo json_encode(['status' => 'error', 'message' => 'คำสั่งไม่ถูกต้อง']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'ฐานข้อมูลผิดพลาด: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>