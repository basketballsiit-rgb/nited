<?php
// supervisor/evaluation_history.php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'executive') {
    header("Location: /nited/index.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$supervisor_id = $_SESSION['user_id'];

// Fetch history of supervisions (approved or completed)
$stmt = $pdo->prepare("
    SELECT s.*, u.name as teacher_name, ay.year, ay.term 
    FROM supervisions s 
    LEFT JOIN users u ON s.teacher_id = u.id 
    LEFT JOIN academic_years ay ON s.academic_year_id = ay.id
    WHERE s.supervisor_id = ? AND s.status IN ('approved', 'completed')
    ORDER BY s.scheduled_date DESC
");
$stmt->execute([$supervisor_id]);
$history = $stmt->fetchAll();
?>

<div class="content-header" style="margin-bottom: 20px;">
    <h2><i class="fas fa-edit"></i> ประวัติและแก้ไขผลการนิเทศ</h2>
    <p class="text-muted">รายการนิเทศที่ดำเนินการประเมินแล้ว หรือรอการประเมิน</p>
</div>

<div class="content-card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">ปีการศึกษา</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">วิชาที่นิเทศ</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">วันเวลาที่นิเทศ</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">ครูผู้สอน (ผู้รับการนิเทศ)</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd; text-align: center;">สถานะ</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd; text-align: center;">การจัดการ</th>
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
                        <td style="padding: 12px;"><?php echo htmlspecialchars($h['teacher_name']); ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <?php
                            $status_colors = [
                                'approved' => ['bg' => '#ffc107', 'text' => '#000', 'label' => 'รอประเมิน'],
                                'completed' => ['bg' => '#198754', 'text' => '#fff', 'label' => 'ประเมินแล้ว']
                            ];
                            $sc = isset($status_colors[$h['status']]) ? $status_colors[$h['status']] : ['bg' => '#ccc', 'text' => '#000', 'label' => $h['status']];
                            ?>
                            <span style="background-color: <?php echo $sc['bg']; ?>; color: <?php echo $sc['text']; ?>; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?php echo $sc['label']; ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if ($h['status'] === 'completed'): ?>
                                <a href="evaluate.php?id=<?php echo $h['id']; ?>" class="btn-gradient"
                                    style="padding: 5px 10px; font-size: 12px; text-decoration: none; background: linear-gradient(135deg, #f39c12, #d35400); color: white;">
                                    <i class="fas fa-edit"></i> แก้ไขผลประเมิน
                                </a>
                                <a href="/nited/teacher/export_observation_pdf.php?id=<?php echo $h['id']; ?>" class="btn-gradient"
                                    style="padding: 5px 10px; font-size: 12px; text-decoration: none; margin-left: 5px;" target="_blank">
                                    <i class="fas fa-print"></i> พิมพ์ PDF
                                </a>
                            <?php else: ?>
                                <a href="evaluate.php?id=<?php echo $h['id']; ?>" class="btn-gradient"
                                    style="padding: 5px 10px; font-size: 12px; text-decoration: none;">
                                    <i class="fas fa-clipboard-check"></i> ประเมินผล
                                </a>
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
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
