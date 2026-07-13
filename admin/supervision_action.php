<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete') {
            $id = $_POST['id'];
            
            // Optionally, we could check if we need to delete associated lesson plans
            $stmt = $pdo->prepare("SELECT lesson_plan_file FROM supervisions WHERE id = ?");
            $stmt->execute([$id]);
            $sup = $stmt->fetch();
            
            if ($sup && !empty($sup['lesson_plan_file'])) {
                $file_path = __DIR__ . '/../uploads/lesson_plans/' . $sup['lesson_plan_file'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Delete the supervision record
            $stmt = $pdo->prepare("DELETE FROM supervisions WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลการจองนิเทศสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
