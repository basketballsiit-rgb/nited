<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'supervisor') {
    header("Location: /nited/index.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$teacher_id = $_SESSION['user_id'];

// Fetch all history
$stmt = $pdo->prepare("
    SELECT s.*, u.name as supervisor_name, ay.year, ay.term 
    FROM supervisions s 
    LEFT JOIN users u ON s.supervisor_id = u.id 
    LEFT JOIN academic_years ay ON s.academic_year_id = ay.id
    WHERE s.teacher_id = ? 
    ORDER BY s.scheduled_date DESC
");
$stmt->execute([$teacher_id]);
$history = $stmt->fetchAll();
?>

<div class="content-header" style="margin-bottom: 20px;">
    <h2><i class="fas fa-history"></i> ประวัติการนิเทศทั้งหมด</h2>
    <p class="text-muted">รายการจองเวลาและผลการนิเทศที่ผ่านมา</p>
</div>

<div class="content-card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">ปีการศึกษา</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">วิชาที่สอน</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">วันเวลาที่นิเทศ</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">กรรมการผู้นิเทศ</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd; text-align: center;">สถานะ</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd; text-align: center;">ดูผลประเมิน</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $h): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;"><?php echo htmlspecialchars($h['term'] . '/' . $h['year']); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($h['subject_name']); ?></td>
                        <td style="padding: 12px;">
                            <?php echo date('d/m/Y', strtotime($h['scheduled_date'])); ?>
                            (<?php echo date('H:i', strtotime($h['scheduled_date'])) . '-' . date('H:i', strtotime($h['end_time'])); ?>)
                        </td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($h['supervisor_name']); ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <?php
                            $status_colors = [
                                'pending' => ['bg' => '#ffc107', 'text' => '#000', 'label' => 'รออนุมัติ'],
                                'approved' => ['bg' => '#0d6efd', 'text' => '#fff', 'label' => 'ยืนยันแล้ว'],
                                'rejected' => ['bg' => '#dc3545', 'text' => '#fff', 'label' => 'ถูกปฏิเสธ'],
                                'completed' => ['bg' => '#198754', 'text' => '#fff', 'label' => 'ประเมินแล้ว']
                            ];
                            $sc = $status_colors[$h['status']];
                            ?>
                            <span
                                style="background-color: <?php echo $sc['bg']; ?>; color: <?php echo $sc['text']; ?>; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?php echo $sc['label']; ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if ($h['status'] === 'completed'): ?>
                                <a href="view_result.php?id=<?php echo $h['id']; ?>" class="btn-gradient"
                                    style="padding: 5px 10px; font-size: 12px; text-decoration: none;"><i
                                        class="fas fa-search"></i> ดูผล</a>
                            <?php else: ?>
                                <span style="color: #ccc;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="6" style="padding: 20px; text-align: center;">ยังไม่มีประวัติการนิเทศ</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div><?php require_once __DIR__ . '/../includes/footer.php'; ?>
