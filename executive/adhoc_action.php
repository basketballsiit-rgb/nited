<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'executive') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $executive_id = $_SESSION['user_id'];
    $year_id = intval($_POST['academic_year_id'] ?? 0);
    $teacher_id = intval($_POST['teacher_id'] ?? 0);
    $subject_code = trim($_POST['subject_code'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');
    $level = trim($_POST['level'] ?? '');

    if ($year_id == 0 || $teacher_id == 0 || empty($subject_code) || empty($subject_name) || empty($level)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
        exit;
    }

    try {
        // Set scheduled_date to current time, and end_time to +1 hour
        $current_datetime = date('Y-m-d H:i:s');
        $end_datetime = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("
            INSERT INTO supervisions 
            (teacher_id, supervisor_id, academic_year_id, subject_code, subject_name, level, scheduled_date, end_time, status, is_urgent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', 1)
        ");
        $stmt->execute([
            $teacher_id, 
            $executive_id, 
            $year_id, 
            $subject_code, 
            $subject_name, 
            $level, 
            $current_datetime, 
            $end_datetime
        ]);

        $new_id = $pdo->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'supervision_id' => $new_id
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
