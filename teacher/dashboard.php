<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('teacher');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$teacher_id = $_SESSION['user_id'];

// Get active academic year
$stmt = $pdo->query("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
$active_year = $stmt->fetch();
$year_id = $active_year ? $active_year['id'] : 0;
$year_txt = $active_year ? ($active_year['term'] . '/' . $active_year['year']) : 'ยังไม่ได้กำหนด';

// Fetch stats for the logged-in teacher
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM supervisions WHERE teacher_id = $teacher_id AND academic_year_id = $year_id")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM supervisions WHERE teacher_id = $teacher_id AND academic_year_id = $year_id AND status IN ('pending', 'approved')")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM supervisions WHERE teacher_id = $teacher_id AND academic_year_id = $year_id AND status = 'completed'")->fetchColumn()
];

// Fetch recent history
$stmt = $pdo->prepare("
    SELECT s.*, u.name as supervisor_name 
    FROM supervisions s 
    LEFT JOIN users u ON s.supervisor_id = u.id 
    WHERE s.teacher_id = ? 
    ORDER BY s.scheduled_date DESC LIMIT 5
");
$stmt->execute([$teacher_id]);
$recent = $stmt->fetchAll();
?>

<div class="content-header" style="margin-bottom: 20px;">
    <h2>แดชบอร์ด (ครูผู้สอน)</h2>
    <p class="text-muted">ปีการศึกษาปัจจุบัน: <strong><?php echo htmlspecialchars($year_txt); ?></strong></p>
</div>

<div class="stat-grid">
    <div class="stat-card bg-orange">
        <h3><i class="fas fa-calendar-alt"></i> การนิเทศที่รอประเมิน/รอยืนยัน</h3>
        <p class="value"><?php echo $stats['pending']; ?></p>
        <i class="fas fa-clock icon"></i>
    </div>
    <div class="stat-card bg-green">
        <h3><i class="fas fa-check-circle"></i> การนิเทศที่ประเมินแล้ว</h3>
        <p class="value"><?php echo $stats['completed']; ?></p>
        <i class="fas fa-clipboard-check icon"></i>
    </div>
    <div class="stat-card bg-blue">
        <h3><i class="fas fa-layer-group"></i> รวมการนิเทศทั้งหมด (เทอมนี้)</h3>
        <p class="value"><?php echo $stats['total']; ?></p>
        <i class="fas fa-list icon"></i>
    </div>
</div>

<div class="content-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0;"><i class="fas fa-history"></i> ประวัติการนิเทศล่าสุด</h3>
        <a href="/nited/teacher/calendar.php" class="btn-gradient" style="text-decoration: none; font-size: 14px;"><i class="fas fa-plus"></i> จองเวลาเพิ่ม</a>
    </div>
    
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 10px; border-bottom: 1px solid #ddd;">วิชาที่สอน</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd;">วันเวลาที่นิเทศ</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd;">กรรมการผู้นิเทศ</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center;">สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $r): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?php echo htmlspecialchars($r['subject_name']); ?></td>
                    <td style="padding: 10px;">
                        <?php echo date('d/m/Y', strtotime($r['scheduled_date'])); ?><br>
                        <small style="color:#666;"><?php echo date('H:i', strtotime($r['scheduled_date'])) . ' - ' . date('H:i', strtotime($r['end_time'])); ?></small>
                    </td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($r['supervisor_name']); ?></td>
                    <td style="padding: 10px; text-align: center;">
                        <?php
                            $status_colors = [
                                'pending' => ['bg' => '#ffc107', 'text' => '#000', 'label' => 'รออนุมัติ'],
                                'approved' => ['bg' => '#0d6efd', 'text' => '#fff', 'label' => 'ยืนยันแล้ว'],
                                'rejected' => ['bg' => '#dc3545', 'text' => '#fff', 'label' => 'ถูกปฏิเสธ'],
                                'completed' => ['bg' => '#198754', 'text' => '#fff', 'label' => 'ประเมินแล้ว']
                            ];
                            $sc = $status_colors[$r['status']];
                        ?>
                        <span style="background-color: <?php echo $sc['bg']; ?>; color: <?php echo $sc['text']; ?>; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            <?php echo $sc['label']; ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?>
                <tr><td colspan="4" style="padding: 20px; text-align: center;">ยังไม่มีประวัติการนิเทศ</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
