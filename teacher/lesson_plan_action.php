<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'supervisor') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/notification_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'submit_plan') {
        $teacher_id = $_SESSION['user_id'];
        $year_id = intval($_POST['academic_year_id']);
        $subject_code = trim($_POST['subject_code'] ?? '');
        $subject_name = trim($_POST['subject_name']);
        $level = trim($_POST['level'] ?? '');

        if (empty($subject_name) || empty($subject_code) || empty($level) || empty($_FILES['lesson_plan_file']['name'])) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วนและแนบไฟล์']);
            exit;
        }

        // --- RANDOM ASSIGNMENT LOGIC (Load Balancing Document Reviews) ---
        $eligible_evaluators = [];

        if ($_SESSION['role'] === 'supervisor') {
            // Supervisors must be evaluated by executives
            $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'executive'");
            $eligible_evaluators = $stmt->fetchAll();
            
            if (empty($eligible_evaluators)) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่มีผู้ใช้งานระดับ "ผู้บริหาร" ในระบบเลย กรุณาติดต่อผู้ดูแลระบบ']);
                exit;
            }
        } else {
            // Regular Teachers evaluated by Supervisors (prioritize specialized/heads) or Executives
            $stmt = $pdo->query("
                SELECT id, name FROM users 
                WHERE role IN ('supervisor', 'executive')
                AND (
                    academic_standing LIKE '%ชำนาญการพิเศษ%' 
                    OR position LIKE '%หัวหน้าสาขาวิชา%'
                    OR academic_standing = 'ชำนาญการพิเศษ'
                    OR position = 'หัวหน้าสาขาวิชา'
                    OR role = 'executive'
                )
            ");
            $eligible_evaluators = $stmt->fetchAll();

            // Fallback
            if (empty($eligible_evaluators)) {
                $stmt = $pdo->query("SELECT id, name FROM users WHERE role IN ('supervisor', 'executive')");
                $eligible_evaluators = $stmt->fetchAll();
            }

            if (empty($eligible_evaluators)) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่มีผู้ใช้งานระดับ "กรรมการนิเทศ" หรือ "ผู้บริหาร" ในระบบเลย กรุณาติดต่อผู้ดูแลระบบ']);
                exit;
            }
        }

        // Load Balancing logic based on `lesson_plans` table
        $load_counts = [];
        foreach ($eligible_evaluators as $sup) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_plans WHERE reviewer_id = ? AND academic_year_id = ?");
            $stmt->execute([$sup['id'], $year_id]);
            $load_counts[$sup['id']] = $stmt->fetchColumn();
        }

        $min_load = min($load_counts);
        $candidates = array_filter($load_counts, function ($load) use ($min_load) {
            return $load == $min_load;
        });

        $candidate_ids = array_keys($candidates);
        $selected_reviewer_id = $candidate_ids[array_rand($candidate_ids)];

        // Get Name
        $selected_name = '';
        foreach ($eligible_evaluators as $sup) {
            if ($sup['id'] == $selected_reviewer_id) {
                $selected_name = $sup['name'];
                break;
            }
        }

        // Handle File Upload
        $lesson_plan_path = '';
        if (isset($_FILES['lesson_plan_file']) && $_FILES['lesson_plan_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/full_lesson_plans/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = strtolower(pathinfo($_FILES['lesson_plan_file']['name'], PATHINFO_EXTENSION));
            
            // Basic security check on extension
            $allowed = ['pdf', 'doc', 'docx'];
            if (!in_array($file_extension, $allowed)) {
                echo json_encode(['status' => 'error', 'message' => 'ประเภทไฟล์ไม่รองรับ อนุญาตเฉพาะ PDF หรือ Word เท่านั้น']);
                exit;
            }

            $new_filename = 'fullplan_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['lesson_plan_file']['tmp_name'], $destination)) {
                $lesson_plan_path = 'uploads/full_lesson_plans/' . $new_filename;
            } else {
                $err = error_get_last();
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถบันทึกไฟล์ได้: ' . ($err['message'] ?? 'Unknown Error') . ' (Path: ' . $destination . ')']);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์']);
            exit;
        }

        // Insert into DB
        try {
            $stmt = $pdo->prepare("
                INSERT INTO lesson_plans (teacher_id, reviewer_id, academic_year_id, subject_code, subject_name, level, file_path, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$teacher_id, $selected_reviewer_id, $year_id, $subject_code, $subject_name, $level, $lesson_plan_path]);

            // Notify reviewer
            $teacher_name = $_SESSION['name'];
            $title = "มีแผนการสอนใหม่รอการตรวจ";
            $message = "คุณ {$teacher_name} ได้ส่งแผนการสอนเต็มเล่มวิชา {$subject_name} กรุณาตรวจสอบ";
            $link = "/nited/supervisor/lesson_plans_review.php";
            addNotification($pdo, $selected_reviewer_id, $title, $message, $link);

            echo json_encode([
                'status' => 'success',
                'assigned_reviewer' => $selected_name
            ]);

        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
