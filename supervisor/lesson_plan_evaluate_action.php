<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'executive') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/notification_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'evaluate_plan') {
        $reviewer_id = $_SESSION['user_id'];
        $lesson_plan_id = intval($_POST['lesson_plan_id']);
        $scores = $_POST['scores'] ?? [];
        $comments = $_POST['comments'] ?? [];
        $final_status = $_POST['final_status'] ?? 'approved';
        $overall_comment = trim($_POST['overall_comment'] ?? '');

        $is_draft = isset($_POST['is_draft']) ? 1 : 0;
        $optional_header = $_POST['optional_header'] ?? [];

        // Convert the skipped optional headers into a string for tracking (e.g., "[12][15]")
        $skipped_sections = '';
        foreach ($optional_header as $header_id => $val) {
            if ($val == '0') {
                $skipped_sections .= '[' . intval($header_id) . ']';
            }
        }

        // Validate
        $stmt = $pdo->prepare("SELECT id, status FROM lesson_plans WHERE id = ? AND reviewer_id = ? FOR UPDATE");
        $stmt->execute([$lesson_plan_id, $reviewer_id]);
        $plan = $stmt->fetch();

        if (!$plan) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูล หรือท่านไม่มีสิทธิ์ประเมิน']);
            exit;
        }

        if ($plan['status'] !== 'pending' && $plan['status'] !== 'draft') {
            echo json_encode(['status' => 'error', 'message' => 'แผนการสอนนี้ไม่ได้อยู่ในสถานะที่สามารถแก้ไขได้']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Clear previous results to prevent duplicates on draft resave
            $clear_stmt = $pdo->prepare("DELETE FROM lesson_plan_results WHERE lesson_plan_id = ?");
            $clear_stmt->execute([$lesson_plan_id]);

            // Insert score items
            $insert_score_stmt = $pdo->prepare("INSERT INTO lesson_plan_results (lesson_plan_id, criteria_item_id, score, comment, is_draft) VALUES (?, ?, ?, ?, ?)");
            foreach ($scores as $item_id => $score) {
                // If the item belongs to a skipped section, we don't save its score
                $comment = trim($comments[$item_id] ?? '');
                $insert_score_stmt->execute([$lesson_plan_id, intval($item_id), floatval($score), $comment, $is_draft]);
            }
            
            // Note: If an item is in a skipped section, it shouldn't be in the $scores POST array 
            // because the javascript handles disabling those inputs.
            
            // Save comments for items that might not have a score (like headers or skipped items)
            foreach ($comments as $item_id => $comment) {
                if (!isset($scores[$item_id]) && trim($comment) !== '') {
                    // This item wasn't scored, but has a comment. Save it with NULL score.
                    $insert_score_stmt->execute([$lesson_plan_id, intval($item_id), null, trim($comment), $is_draft]);
                }
            }

            // Handle Signature
            $signature_path = null;
            $upload_dir = __DIR__ . '/../uploads/signatures/';

            if (!empty($_POST['signature_base64'])) {
                $base64_string = $_POST['signature_base64'];
                $data = explode(',', $base64_string);
                if (count($data) > 1) {
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    $filename = 'sig_lp_' . $lesson_plan_id . '_' . $reviewer_id . '_' . time() . '.png';
                    if (file_put_contents($upload_dir . $filename, base64_decode($data[1]))) {
                        $signature_path = '/nited/uploads/signatures/' . $filename;
                    }
                }
            } elseif (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = pathinfo($_FILES['signature_file']['name'], PATHINFO_EXTENSION);
                $filename = 'sig_lp_' . $lesson_plan_id . '_' . $reviewer_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['signature_file']['tmp_name'], $upload_dir . $filename)) {
                    $signature_path = '/nited/uploads/signatures/' . $filename;
                }
            }

            // Update main record
            if ($is_draft) {
                $sql = "UPDATE lesson_plans SET status = 'draft', review_comment = ?, optional_sections = ?";
                $params = [$overall_comment, $skipped_sections];
                if ($signature_path) { 
                    $sql .= ", signature_path = ?"; 
                    $params[] = $signature_path; 
                }
                $sql .= " WHERE id = ?";
                $params[] = $lesson_plan_id;
                $update_plan_stmt = $pdo->prepare($sql);
                $update_plan_stmt->execute($params);
            } else {
                $sql = "UPDATE lesson_plans SET status = ?, review_comment = ?, optional_sections = ?, reviewed_at = CURRENT_TIMESTAMP";
                $params = [$final_status, $overall_comment, $skipped_sections];
                if ($signature_path) { 
                    $sql .= ", signature_path = ?"; 
                    $params[] = $signature_path; 
                }
                $sql .= " WHERE id = ?";
                $params[] = $lesson_plan_id;
                $update_plan_stmt = $pdo->prepare($sql);
                $update_plan_stmt->execute($params);

                // Notify Teacher
                $stmt = $pdo->prepare("SELECT teacher_id, subject_name FROM lesson_plans WHERE id = ?");
                $stmt->execute([$lesson_plan_id]);
                $lp = $stmt->fetch();
                if ($lp) {
                    $reviewer_name = $_SESSION['name'];
                    $status_th = $final_status === 'approved' ? 'อนุมัติ' : ($final_status === 'revision' ? 'ให้แก้ไข' : 'ไม่อนุมัติ');
                    $title = "ผลการตรวจแผนการสอน";
                    $message = "กรรมการ {$reviewer_name} ได้ตรวจและ {$status_th} แผนการสอนวิชา {$lp['subject_name']} ของคุณแล้ว";
                    $link = "/nited/teacher/lesson_plans.php";
                    addNotification($pdo, $lp['teacher_id'], $title, $message, $link);
                }
            }

            $pdo->commit();
            echo json_encode(['status' => 'success']);

        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
}
?>
