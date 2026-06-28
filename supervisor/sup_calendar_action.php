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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$supervisor_id = $_SESSION['user_id'];

try {
    if ($action === 'get_events') {
        $stmt = $pdo->prepare("
            SELECT s.*, u.name as teacher_name 
            FROM supervisions s 
            LEFT JOIN users u ON s.teacher_id = u.id 
            WHERE s.supervisor_id = ?
        ");
        $stmt->execute([$supervisor_id]);
        $supervisions = $stmt->fetchAll();

        $events = [];
        foreach ($supervisions as $s) {
            $status_colors = [
                'pending' => '#ffc107',
                'approved' => '#0d6efd',
                'rejected' => '#dc3545',
                'completed' => '#198754'
            ];

            $status_labels = [
                'pending' => 'รออนุมัติ',
                'approved' => 'ยืนยันแล้ว',
                'rejected' => 'ถูกปฏิเสธ',
                'completed' => 'ประเมินแล้ว'
            ];

            $events[] = [
                'id' => $s['id'],
                'title' => $s['subject_name'],
                'start' => date('Y-m-d\TH:i:s', strtotime($s['scheduled_date'])),
                'end' => date('Y-m-d\TH:i:s', strtotime($s['end_time'])),
                'backgroundColor' => $status_colors[$s['status']],
                'borderColor' => $status_colors[$s['status']],
                'extendedProps' => [
                    'status' => $s['status'],
                    'status_label' => $status_labels[$s['status']],
                    'teacher_name' => $s['teacher_name'],
                    'lesson_plan_file' => $s['lesson_plan_file']
                ]
            ];
        }
        echo json_encode($events);

    } elseif ($action === 'update_status') {
        $id = $_POST['id'];
        $status = $_POST['status']; // 'approved' or 'rejected'

        if (!in_array($status, ['approved', 'rejected'])) {
            echo json_encode(['status' => 'error', 'message' => 'สถานะไม่ถูกต้อง']);
            exit;
        }

        if ($status === 'approved') {
            // Check if the teacher already reached the max limit for this academic year
            $stmt = $pdo->prepare("SELECT teacher_id, academic_year_id FROM supervisions WHERE id = ?");
            $stmt->execute([$id]);
            $sup_info = $stmt->fetch();
            
            if ($sup_info) {
                $stmt2 = $pdo->prepare("
                    SELECT COUNT(*) FROM supervisions 
                    WHERE teacher_id = ? 
                    AND academic_year_id = ? 
                    AND status IN ('approved', 'completed')
                ");
                $stmt2->execute([$sup_info['teacher_id'], $sup_info['academic_year_id']]);
                $current_count = $stmt2->fetchColumn();
                
                if ($current_count >= 1) {
                    echo json_encode(['status' => 'error', 'message' => 'ผู้รับการนิเทศท่านนี้ได้รับการอนุมัติครบ 1 ครั้งในภาคเรียนนี้แล้ว ไม่สามารถอนุมัติเพิ่มได้ (โควตาปกติ 1 ครั้ง/ภาคเรียน)']);
                    exit;
                }
            }
        }

        // Ensure this supervisor owns this supervision record
        $stmt = $pdo->prepare("UPDATE supervisions SET status = ? WHERE id = ? AND supervisor_id = ? AND status = 'pending'");
        $stmt->execute([$status, $id, $supervisor_id]);
        
        if ($stmt->rowCount() > 0) {
            // Fetch teacher info to send notification
            $stmt = $pdo->prepare("SELECT teacher_id, subject_name FROM supervisions WHERE id = ?");
            $stmt->execute([$id]);
            $sup = $stmt->fetch();
            if ($sup) {
                $status_th = $status === 'approved' ? 'อนุมัติ' : 'ปฏิเสธ';
                $title = "ตอบรับคำขอรับการนิเทศ";
                $message = "กรรมการได้ {$status_th} คำขอรับการนิเทศวิชา {$sup['subject_name']} ของคุณแล้ว";
                $link = "/nited/teacher/history.php";
                addNotification($pdo, $sup['teacher_id'], $title, $message, $link);
            }
        }

        echo json_encode(['status' => 'success']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>