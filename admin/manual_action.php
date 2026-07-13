<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $title = trim($_POST['title'] ?? '');
    $role_target = $_POST['role_target'] ?? 'all';
    
    if (empty($title)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อคู่มือ']);
        exit;
    }
    
    if (!isset($_FILES['manual_file']) || $_FILES['manual_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาอัปโหลดไฟล์คู่มือ']);
        exit;
    }
    
    $file = $_FILES['manual_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
    
    if (!in_array($ext, $allowed_exts)) {
        echo json_encode(['status' => 'error', 'message' => 'อนุญาตเฉพาะไฟล์ PDF, Word หรือ PowerPoint เท่านั้น']);
        exit;
    }
    
    $upload_dir = __DIR__ . '/../uploads/manuals/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = 'manual_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $target_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $db_path = 'uploads/manuals/' . $filename;
        
        $stmt = $pdo->prepare("INSERT INTO manuals (role_target, title, file_path) VALUES (?, ?, ?)");
        if ($stmt->execute([$role_target, $title, $db_path])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถบันทึกข้อมูลลงฐานข้อมูลได้']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถอัปโหลดไฟล์ได้']);
    }
    
} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT file_path FROM manuals WHERE id = ?");
    $stmt->execute([$id]);
    $manual = $stmt->fetch();
    
    if ($manual) {
        // Delete file
        $full_path = __DIR__ . '/../' . $manual['file_path'];
        if (file_exists($full_path)) {
            unlink($full_path);
        }
        
        // Delete record
        $stmt = $pdo->prepare("DELETE FROM manuals WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบข้อมูลได้']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลคู่มือ']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
