<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/notification_helper.php';

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
        } elseif ($action === 'edit') {
            $id = $_POST['id'] ?? null;
            $new_supervisor_id = $_POST['supervisor_id'] ?? null;
            $new_date = $_POST['date'] ?? null;
            $new_start_time = $_POST['start_time'] ?? null;
            $new_end_time = $_POST['end_time'] ?? null;

            if (!$id || !$new_supervisor_id || !$new_date || !$new_start_time || !$new_end_time) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            $new_scheduled_date = $new_date . ' ' . $new_start_time . ':00';
            $new_end_time_full = $new_date . ' ' . $new_end_time . ':00';

            // Check if the new supervisor is busy at that time (overlap logic)
            $stmt = $pdo->prepare("
                SELECT id 
                FROM supervisions 
                WHERE supervisor_id = ? 
                AND id != ?
                AND status != 'rejected'
                AND scheduled_date < ? 
                AND end_time > ?
            ");
            $stmt->execute([$new_supervisor_id, $id, $new_end_time_full, $new_scheduled_date]);
            if ($stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'กรรมการท่านนี้มีตารางนิเทศในช่วงเวลาดังกล่าวแล้ว กรุณาเลือกเวลาอื่นหรือเปลี่ยนกรรมการท่านอื่น']);
                exit;
            }

            // Fetch old supervision data to compare
            $stmt = $pdo->prepare("SELECT teacher_id, supervisor_id, subject_name FROM supervisions WHERE id = ?");
            $stmt->execute([$id]);
            $old_sup = $stmt->fetch();

            if (!$old_sup) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลการจองนี้']);
                exit;
            }

            $old_supervisor_id = $old_sup['supervisor_id'];
            $teacher_id = $old_sup['teacher_id'];
            $subject_name = $old_sup['subject_name'];

            // Update supervision
            $stmt = $pdo->prepare("UPDATE supervisions SET supervisor_id = ?, scheduled_date = ?, end_time = ? WHERE id = ?");
            $stmt->execute([$new_supervisor_id, $new_scheduled_date, $new_end_time_full, $id]);

            // Determine notifications
            if ($old_supervisor_id != $new_supervisor_id) {
                // Supervisor changed
                // 1. Notify old supervisor (cancellation)
                addNotification($pdo, $old_supervisor_id, "การจองนิเทศถูกยกเลิก", "รายการนิเทศวิชา {$subject_name} ของคุณถูกยกเลิก เนื่องจากผู้ดูแลระบบได้เปลี่ยนตัวกรรมการ", "/nited/supervisor/calendar.php");
                // 2. Notify new supervisor
                addNotification($pdo, $new_supervisor_id, "ได้รับการมอบหมายใหม่ (โดยแอดมิน)", "ผู้ดูแลระบบได้มอบหมายการนิเทศวิชา {$subject_name} ให้กับคุณ", "/nited/supervisor/calendar.php");
                // 3. Notify teacher
                addNotification($pdo, $teacher_id, "การจองนิเทศถูกเปลี่ยนแปลง", "ผู้ดูแลระบบได้แก้ไขข้อมูลการจองนิเทศวิชา {$subject_name} ของคุณ (รวมถึงเปลี่ยนตัวกรรมการ)", "/nited/teacher/history.php");
            } else {
                // Same supervisor, just time/date changed
                addNotification($pdo, $old_supervisor_id, "เปลี่ยนแปลงเวลานิเทศ", "ผู้ดูแลระบบได้แก้ไขวัน/เวลาการนิเทศวิชา {$subject_name}", "/nited/supervisor/calendar.php");
                addNotification($pdo, $teacher_id, "การจองนิเทศถูกเปลี่ยนแปลง", "ผู้ดูแลระบบได้แก้ไขวัน/เวลาการนิเทศวิชา {$subject_name} ของคุณ", "/nited/teacher/history.php");
            }

            echo json_encode(['status' => 'success', 'message' => 'บันทึกการแก้ไขสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
