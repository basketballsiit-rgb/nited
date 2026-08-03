<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'executive' && $_SESSION['role'] !== 'teacher') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/notification_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supervision_id = $_POST['supervision_id'];
    $supervisor_id = $_SESSION['user_id'];
    $scores = $_POST['scores'] ?? []; // Array of criteria_item_id => score
    $comments = $_POST['comments'] ?? []; // Array of criteria_item_id => comment

    // Validate that the request belongs to this supervisor and is approved/completed
    $stmt = $pdo->prepare("SELECT id, status, photo_path, photo_path_2, signature_path FROM supervisions WHERE id = ? AND supervisor_id = ? AND status IN ('approved', 'completed')");
    $stmt->execute([$supervision_id, $supervisor_id]);
    $existing_sup = $stmt->fetch();
    if (!$existing_sup) {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลการนิเทศไม่ถูกต้อง หรือไม่พร้อมประเมิน']);
        exit;
    }

    // Check if scores are provided
    if (empty($scores)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาให้คะแนนให้ครบถ้วน']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Delete old results if this is an update
        $stmt = $pdo->prepare("DELETE FROM supervision_results WHERE supervision_id = ?");
        $stmt->execute([$supervision_id]);

        // Loop through scores and insert results
        foreach ($scores as $item_id => $score) {
            $comment = isset($comments[$item_id]) ? trim($comments[$item_id]) : '';

            $stmt = $pdo->prepare("INSERT INTO supervision_results (supervision_id, criteria_item_id, score, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$supervision_id, $item_id, $score, $comment]);
        }

        // Handle File Uploads
        $photo_path_1 = $existing_sup['photo_path'];
        $photo_path_2 = $existing_sup['photo_path_2'];
        $upload_dir = __DIR__ . '/../assets/uploads/evaluations/';

        // Ensure directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_exts = ['jpg', 'jpeg', 'png'];

        // Process Photo 1
        if (isset($_FILES['evaluation_photo_1'])) {
            $err1 = $_FILES['evaluation_photo_1']['error'];
            if ($err1 === UPLOAD_ERR_OK) {
                $file_ext = strtolower(pathinfo($_FILES['evaluation_photo_1']['name'], PATHINFO_EXTENSION));
                if (in_array($file_ext, $allowed_exts)) {
                    $new_file_name_1 = 'eval_1_' . $supervision_id . '_' . time() . '.' . $file_ext;
                    $dest_path_1 = $upload_dir . $new_file_name_1;
                    if (move_uploaded_file($_FILES['evaluation_photo_1']['tmp_name'], $dest_path_1)) {
                        $photo_path_1 = 'assets/uploads/evaluations/' . $new_file_name_1;
                    } else {
                        throw new Exception("ไม่สามารถอัปโหลดภาพที่ 1 ได้ กรุณาตรวจสอบสิทธิ์โฟลเดอร์");
                    }
                } else {
                    throw new Exception("ไฟล์ภาพที่ 1 ไม่รองรับ (รับเฉพาะ jpg, jpeg, png)");
                }
            } elseif ($err1 !== UPLOAD_ERR_NO_FILE) {
                throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดภาพที่ 1 (รหัส: {$err1}) ไฟล์อาจจะใหญ่เกินไป");
            }
        }

        // Process Photo 2
        if (isset($_FILES['evaluation_photo_2'])) {
            $err2 = $_FILES['evaluation_photo_2']['error'];
            if ($err2 === UPLOAD_ERR_OK) {
                $file_ext = strtolower(pathinfo($_FILES['evaluation_photo_2']['name'], PATHINFO_EXTENSION));
                if (in_array($file_ext, $allowed_exts)) {
                    $new_file_name_2 = 'eval_2_' . $supervision_id . '_' . time() . '.' . $file_ext;
                    $dest_path_2 = $upload_dir . $new_file_name_2;
                    if (move_uploaded_file($_FILES['evaluation_photo_2']['tmp_name'], $dest_path_2)) {
                        $photo_path_2 = 'assets/uploads/evaluations/' . $new_file_name_2;
                    } else {
                        throw new Exception("ไม่สามารถอัปโหลดภาพที่ 2 ได้ กรุณาตรวจสอบสิทธิ์โฟลเดอร์");
                    }
                } else {
                    throw new Exception("ไฟล์ภาพที่ 2 ไม่รองรับ (รับเฉพาะ jpg, jpeg, png)");
                }
            } elseif ($err2 !== UPLOAD_ERR_NO_FILE) {
                throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดภาพที่ 2 (รหัส: {$err2}) ไฟล์อาจจะใหญ่เกินไป");
            }
        }

        // Handle Signature
        $signature_path = $existing_sup['signature_path'];
        $sig_upload_dir = __DIR__ . '/../uploads/signatures/';

        if (!empty($_POST['signature_base64'])) {
            $base64_string = $_POST['signature_base64'];
            $data = explode(',', $base64_string);
            if (count($data) > 1) {
                if (!is_dir($sig_upload_dir)) mkdir($sig_upload_dir, 0777, true);
                $filename = 'sig_obs_' . $supervision_id . '_' . $supervisor_id . '_' . time() . '.png';
                if (file_put_contents($sig_upload_dir . $filename, base64_decode($data[1]))) {
                    $signature_path = '/nited/uploads/signatures/' . $filename;
                }
            }
        } elseif (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir($sig_upload_dir)) mkdir($sig_upload_dir, 0777, true);
            $ext = pathinfo($_FILES['signature_file']['name'], PATHINFO_EXTENSION);
            $filename = 'sig_obs_' . $supervision_id . '_' . $supervisor_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['signature_file']['tmp_name'], $sig_upload_dir . $filename)) {
                $signature_path = '/nited/uploads/signatures/' . $filename;
            }
        }

        $update_time_sql = "";
        $update_params = [$photo_path_1, $photo_path_2, $signature_path];

        if (!empty($_POST['scheduled_date_only']) && !empty($_POST['scheduled_time_only']) && !empty($_POST['end_time_only'])) {
            $new_start = $_POST['scheduled_date_only'] . ' ' . $_POST['scheduled_time_only'] . ':00';
            $new_end = $_POST['scheduled_date_only'] . ' ' . $_POST['end_time_only'] . ':00';
            $update_time_sql = ", scheduled_date = ?, end_time = ?";
            $update_params[] = $new_start;
            $update_params[] = $new_end;
        }

        $update_params[] = $supervision_id;

        $stmt = $pdo->prepare("UPDATE supervisions SET status = 'completed', photo_path = ?, photo_path_2 = ?, signature_path = ? {$update_time_sql} WHERE id = ?");
        $stmt->execute($update_params);

        $pdo->commit();

        // Fetch teacher to notify
        if ($existing_sup['status'] !== 'completed') {
            $stmt = $pdo->prepare("SELECT teacher_id, subject_name FROM supervisions WHERE id = ?");
            $stmt->execute([$supervision_id]);
            $sup = $stmt->fetch();
            if ($sup) {
                $supervisor_name = $_SESSION['name'];
                $title = "ผลการนิเทศการสอน";
                $message = "กรรมการ {$supervisor_name} ได้ประเมินการนิเทศวิชา {$sup['subject_name']} ของคุณเรียบร้อยแล้ว";
                $link = "/nited/teacher/history.php";
                addNotification($pdo, $sup['teacher_id'], $title, $message, $link);
            }
        }

        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>