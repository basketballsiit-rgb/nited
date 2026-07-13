<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'teacher') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $type = $_POST['type'] ?? ''; // 'supervision' or 'lesson_plan'
    $teacher_id = $_SESSION['user_id'];
    
    if (!$id || !in_array($type, ['supervision', 'lesson_plan'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        exit;
    }

    try {
        // Validate ownership
        if ($type === 'supervision') {
            $stmt = $pdo->prepare("SELECT id FROM supervisions WHERE id = ? AND teacher_id = ? AND status = 'completed'");
        } else {
            $stmt = $pdo->prepare("SELECT id FROM lesson_plans WHERE id = ? AND teacher_id = ? AND status IN ('approved', 'rejected', 'revision')");
        }
        $stmt->execute([$id, $teacher_id]);
        if ($stmt->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูล หรือคุณไม่มีสิทธิ์เซ็นรับทราบ']);
            exit;
        }

        $signature_path = null;
        $sig_upload_dir = __DIR__ . '/../uploads/signatures/';

        if (!empty($_POST['signature_base64'])) {
            $base64_string = $_POST['signature_base64'];
            $data = explode(',', $base64_string);
            if (count($data) > 1) {
                if (!is_dir($sig_upload_dir)) mkdir($sig_upload_dir, 0777, true);
                $filename = 'teacher_sig_' . $type . '_' . $id . '_' . $teacher_id . '_' . time() . '.png';
                if (file_put_contents($sig_upload_dir . $filename, base64_decode($data[1]))) {
                    $signature_path = '/nited/uploads/signatures/' . $filename;
                }
            }
        } elseif (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir($sig_upload_dir)) mkdir($sig_upload_dir, 0777, true);
            $ext = pathinfo($_FILES['signature_file']['name'], PATHINFO_EXTENSION);
            $filename = 'teacher_sig_' . $type . '_' . $id . '_' . $teacher_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['signature_file']['tmp_name'], $sig_upload_dir . $filename)) {
                $signature_path = '/nited/uploads/signatures/' . $filename;
            }
        }

        if (!$signature_path) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาเซ็นชื่อ หรืออัปโหลดรูปลายเซ็น']);
            exit;
        }

        $now = date('Y-m-d H:i:s');
        if ($type === 'supervision') {
            $stmt = $pdo->prepare("UPDATE supervisions SET teacher_signature_path = ?, teacher_signed_at = ? WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE lesson_plans SET teacher_signature_path = ?, teacher_signed_at = ? WHERE id = ?");
        }
        $stmt->execute([$signature_path, $now, $id]);

        echo json_encode(['status' => 'success']);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
