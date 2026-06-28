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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$teacher_id = $_SESSION['user_id'];

try {
    if ($action === 'get_events') {
        // Only fetch events for this teacher
        $stmt = $pdo->prepare("
            SELECT s.*, u.name as supervisor_name 
            FROM supervisions s 
            LEFT JOIN users u ON s.supervisor_id = u.id 
            WHERE s.teacher_id = ?
        ");
        $stmt->execute([$teacher_id]);
        $supervisions = $stmt->fetchAll();

        $events = [];
        foreach ($supervisions as $s) {
            $status_colors = [
                'pending' => '#ffc107',   // Yellow
                'approved' => '#198754',  // Green
                'rejected' => '#dc3545',
                'completed' => '#198754'  // Green
            ];

            $text_colors = [
                'pending' => '#000000',   // Black text for yellow bg
                'approved' => '#ffffff',  // White text for green bg
                'rejected' => '#ffffff',
                'completed' => '#ffffff'
            ];

            $status_labels = [
                'pending' => 'รออนุมัติ',
                'approved' => 'ยืนยันแล้ว',
                'rejected' => 'ถูกปฏิเสธ',
                'completed' => 'ประเมินแล้ว'
            ];

            // Format time
            $s_time = date('H:i', strtotime($s['scheduled_date']));
            $e_time = date('H:i', strtotime($s['end_time']));
            
            // Build title string: time - subject (supervisor)
            $event_title = "{$s_time}-{$e_time} น.\nวิชา: {$s['subject_name']}\nกรรมการ: {$s['supervisor_name']}";

            $events[] = [
                'id' => $s['id'],
                'title' => $event_title,
                'start' => date('Y-m-d\TH:i:s', strtotime($s['scheduled_date'])),
                'end' => date('Y-m-d\TH:i:s', strtotime($s['end_time'])),
                'backgroundColor' => $status_colors[$s['status']],
                'borderColor' => $status_colors[$s['status']],
                'textColor' => $text_colors[$s['status']],
                'display' => 'block', // Force full block style
                'extendedProps' => [
                    'status' => $s['status'],
                    'status_label' => $status_labels[$s['status']],
                    'supervisor_name' => $s['supervisor_name'],
                    'lesson_plan_file' => $s['lesson_plan_file']
                ]
            ];
        }
        echo json_encode($events);

    } elseif ($action === 'book_slot') {
        $year_id = $_POST['academic_year_id'];
        $start_date = $_POST['start_datetime'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $subject_code = trim($_POST['subject_code'] ?? '');
        $subject = trim($_POST['subject_name']);
        $level = trim($_POST['level'] ?? '');
        $teaching_department = trim($_POST['teaching_department'] ?? '');

        if (empty($subject_code) || empty($subject) || empty($level) || empty($teaching_department)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
            exit;
        }

        $start_dt = $start_date . ' ' . $start_time . ':00';
        $end_dt = $start_date . ' ' . $end_time . ':00';

        // --- VALIDATION: MAX SUPERVISIONS PER SEMESTER ---
        // Teachers can have a maximum of 1 'approved' or 'completed' supervisions per semester.
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM supervisions 
            WHERE teacher_id = ? 
            AND academic_year_id = ? 
            AND status IN ('approved', 'completed')
        ");
        $stmt->execute([$teacher_id, $year_id]);
        $valid_supervision_count = $stmt->fetchColumn();

        if ($valid_supervision_count >= 1) {
            echo json_encode(['status' => 'error', 'message' => 'คุณได้รับการอนุมัติวันนิเทศครบ 1 ครั้งในภาคเรียนนี้ตามเกณฑ์แล้ว ไม่สามารถจองเพิ่มได้อีก (ยกเว้นกรณีรองผู้อำนวยการฯ เดินเข้านิเทศเร่งด่วน)']);
            exit;
        }

        // --- RANDOM ASSIGNMENT LOGIC ---
        // --- RANDOM ASSIGNMENT LOGIC ---
        $eligible_evaluators = [];
        $evaluator_role_target = 'supervisor';

        if ($_SESSION['role'] === 'supervisor') {
            // If the person booking is a supervisor, they must be evaluated by an executive
            $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'executive'");
            $eligible_evaluators = $stmt->fetchAll();
            $evaluator_role_target = 'ผู้บริหาร';

            if (empty($eligible_evaluators)) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่มีผู้ใช้งานระดับ "ผู้บริหาร" ในระบบเลย กรุณาติดต่อผู้ดูแลระบบ']);
                exit;
            }
        } else {
            // Find eligible supervisors: role in (supervisor, executive) AND matches high rank
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

            // Fallback: If no one matches the strict criteria, just grab ANY supervisor or executive
            if (empty($eligible_evaluators)) {
                $stmt = $pdo->query("SELECT id, name FROM users WHERE role IN ('supervisor', 'executive')");
                $eligible_evaluators = $stmt->fetchAll();
            }

            // Deep Fallback: If there are literally no supervisors at all
            if (empty($eligible_evaluators)) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่มีผู้ใช้งานระดับ "กรรมการนิเทศ" หรือ "ผู้บริหาร" ในระบบเลย กรุณาติดต่อผู้ดูแลระบบ']);
                exit;
            }
            $evaluator_role_target = 'กรรมการนิเทศ / ผู้บริหาร';
        }

        // --- OVERLAP FILTERING LOGIC ---
        // Fetch supervisor IDs who already have a booking overlapping with the requested time
        // An overlap occurs if existing.start < new.end AND existing.end > new.start
        $stmt = $pdo->prepare("
            SELECT DISTINCT supervisor_id 
            FROM supervisions 
            WHERE status != 'rejected'
            AND scheduled_date < ? 
            AND end_time > ?
        ");
        $stmt->execute([$end_dt, $start_dt]);
        $busy_supervisor_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Filter out busy supervisors from $eligible_evaluators
        if (!empty($busy_supervisor_ids)) {
            $eligible_evaluators = array_filter($eligible_evaluators, function($sup) use ($busy_supervisor_ids) {
                return !in_array($sup['id'], $busy_supervisor_ids);
            });
            // Re-index the array after filtering
            $eligible_evaluators = array_values($eligible_evaluators);
        }
        
        // Check if anyone is left after filtering
        if (empty($eligible_evaluators)) {
            echo json_encode(['status' => 'error', 'message' => 'คิวเต็มแล้วประจำวันและเวลานี้ ไม่มีกรรมการท่านใดว่าง กรุณาเลือกช่วงเวลาอื่น']);
            exit;
        }

        // Get current load for each eligible evaluator in the active academic year
        $load_counts = [];
        foreach ($eligible_evaluators as $sup) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM supervisions WHERE supervisor_id = ? AND academic_year_id = ?");
            $stmt->execute([$sup['id'], $year_id]);
            $load_counts[$sup['id']] = $stmt->fetchColumn();
        }

        // Find the minimum load
        $min_load = min($load_counts);

        // Filter evaluators who have this minimum load
        $candidates = array_filter($load_counts, function ($load) use ($min_load) {
            return $load == $min_load;
        });

        // Get array of candidate IDs
        $candidate_ids = array_keys($candidates);

        // Randomly pick one ID from the candidates
        $selected_supervisor_id = $candidate_ids[array_rand($candidate_ids)];

        // Get name for response
        $selected_name = '';
        foreach ($eligible_evaluators as $sup) {
            if ($sup['id'] == $selected_supervisor_id) {
                $selected_name = $sup['name'];
                break;
            }
        }

        // Handle File Upload
        $lesson_plan_path = null;
        if (isset($_FILES['lesson_plan_file']) && $_FILES['lesson_plan_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/lesson_plans/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = strtolower(pathinfo($_FILES['lesson_plan_file']['name'], PATHINFO_EXTENSION));
            $new_filename = 'plan_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['lesson_plan_file']['tmp_name'], $destination)) {
                $lesson_plan_path = 'uploads/lesson_plans/' . $new_filename;
            }
        }

        // Insert Supervision Request
        $stmt = $pdo->prepare("
            INSERT INTO supervisions (teacher_id, supervisor_id, academic_year_id, subject_code, subject_name, level, teaching_department, scheduled_date, end_time, status, lesson_plan_file)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");
        $stmt->execute([$teacher_id, $selected_supervisor_id, $year_id, $subject_code, $subject, $level, $teaching_department, $start_dt, $end_dt, $lesson_plan_path]);

        // Send notification to the assigned supervisor
        $teacher_name = $_SESSION['name'];
        $title = "คำขอรับการนิเทศใหม่";
        $message = "คุณ {$teacher_name} ได้ส่งคำขอรับการนิเทศวิชา {$subject} กรุณาตรวจสอบและยืนยันเวลา";
        $link = "/nited/supervisor/calendar.php";
        addNotification($pdo, $selected_supervisor_id, $title, $message, $link);

        echo json_encode([
            'status' => 'success',
            'assigned_supervisor' => $selected_name
        ]);

    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>