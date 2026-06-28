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
            $year = trim($_POST['year']);
            $term = trim($_POST['term']);

            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM academic_years WHERE year = ? AND term = ?");
            $stmt->execute([$year, $term]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'มีปีการศึกษาและภาคเรียนนี้ในระบบแล้ว']);
                exit;
            }

            // If it's the first one, make it active
            $stmt = $pdo->query("SELECT COUNT(*) FROM academic_years");
            $is_active = ($stmt->fetchColumn() == 0) ? 1 : 0;

            $stmt = $pdo->prepare("INSERT INTO academic_years (year, term, is_active) VALUES (?, ?, ?)");
            $stmt->execute([$year, $term, $is_active]);

            echo json_encode(['status' => 'success']);

        } elseif ($action === 'set_active') {
            $id = $_POST['id'];

            $pdo->beginTransaction();
            // Reset all
            $pdo->exec("UPDATE academic_years SET is_active = 0");
            // Set new active
            $stmt = $pdo->prepare("UPDATE academic_years SET is_active = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $pdo->commit();

            echo json_encode(['status' => 'success']);

        } elseif ($action === 'delete') {
            $id = $_POST['id'];

            // Cannot delete active year
            $stmt = $pdo->prepare("SELECT is_active FROM academic_years WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() == 1) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบปีการศึกษาที่กำลังใช้งานได้']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM academic_years WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['status' => 'success']);
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>