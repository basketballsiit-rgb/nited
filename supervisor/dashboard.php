<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('supervisor');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$supervisor_id = $_SESSION['user_id'];

// Get active academic year
$stmt = $pdo->query("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
$active_year = $stmt->fetch();
$year_id = $active_year ? $active_year['id'] : 0;
$year_txt = $active_year ? ($active_year['term'] . '/' . $active_year['year']) : 'ยังไม่ได้กำหนด';

// Fetch stats for the logged-in supervisor
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM supervisions WHERE supervisor_id = $supervisor_id AND academic_year_id = $year_id")->fetchColumn(),
    'pending_approval' => $pdo->query("SELECT COUNT(*) FROM supervisions WHERE supervisor_id = $supervisor_id AND academic_year_id = $year_id AND status = 'pending'")->fetchColumn(),
    'pending_eval' => $pdo->query("SELECT COUNT(*) FROM supervisions WHERE supervisor_id = $supervisor_id AND academic_year_id = $year_id AND status = 'approved'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM supervisions WHERE supervisor_id = $supervisor_id AND academic_year_id = $year_id AND status = 'completed'")->fetchColumn()
];

// Fetch recent requests (pending or approved)
$stmt = $pdo->prepare("
    SELECT s.*, u.name as teacher_name 
    FROM supervisions s 
    LEFT JOIN users u ON s.teacher_id = u.id 
    WHERE s.supervisor_id = ? AND s.status IN ('pending', 'approved')
    ORDER BY s.scheduled_date ASC LIMIT 5
");
$stmt->execute([$supervisor_id]);
$recent = $stmt->fetchAll();
?>

<div class="content-header" style="margin-bottom: 20px;">
    <h2>แดชบอร์ด (กรรมการผู้นิเทศ)</h2>
    <p class="text-muted">ปีการศึกษาปัจจุบัน: <strong><?php echo htmlspecialchars($year_txt); ?></strong></p>
</div>

<div class="stat-grid">
    <div class="stat-card bg-red">
        <h3><i class="fas fa-bell"></i> รอการยืนยันเวลา</h3>
        <p class="value"><?php echo $stats['pending_approval']; ?></p>
        <i class="fas fa-calendar-times icon"></i>
    </div>
    <div class="stat-card bg-orange">
        <h3><i class="fas fa-edit"></i> รอประเมินผล</h3>
        <p class="value"><?php echo $stats['pending_eval']; ?></p>
        <i class="fas fa-clipboard-list icon"></i>
    </div>
    <div class="stat-card bg-green">
        <h3><i class="fas fa-check-circle"></i> ประเมินเสร็จสิ้น</h3>
        <p class="value"><?php echo $stats['completed']; ?></p>
        <i class="fas fa-check icon"></i>
    </div>
    <div class="stat-card bg-blue">
        <h3><i class="fas fa-layer-group"></i> ภาระงานทั้งหมด (เทอมนี้)</h3>
        <p class="value"><?php echo $stats['total']; ?></p>
        <i class="fas fa-users icon"></i>
    </div>
</div>

<div class="content-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0;"><i class="fas fa-tasks"></i> รายการนิเทศที่ต้องดำเนินการ</h3>
        <a href="/nited/supervisor/calendar.php" class="btn-gradient" style="text-decoration: none; font-size: 14px;"><i class="fas fa-calendar-alt"></i> ดูในรูปแบบปฏิทิน</a>
    </div>
    
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 10px; border-bottom: 1px solid #ddd;">ครูผู้รับการนิเทศ</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd;">วิชาที่สอน</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd;">วันเวลาที่นิเทศ</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center;">สถานะ</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $r): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?php echo htmlspecialchars($r['teacher_name']); ?></td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($r['subject_name']); ?></td>
                    <td style="padding: 10px;">
                        <?php echo date('d/m/Y', strtotime($r['scheduled_date'])); ?><br>
                        <small style="color:#666;"><?php echo date('H:i', strtotime($r['scheduled_date'])) . ' - ' . date('H:i', strtotime($r['end_time'])); ?></small>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <?php if ($r['status'] == 'pending'): ?>
                            <span style="background-color: #ffc107; color: #000; padding: 4px 8px; border-radius: 4px; font-size: 12px;">รอการยืนยันเวลา</span>
                        <?php elseif ($r['status'] == 'approved'): ?>
                            <span style="background-color: #0d6efd; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px;">รอดำเนินการประเมิน</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <?php if ($r['status'] == 'pending'): ?>
                            <!-- Action handled in Calendar, but provide a shortcut here -->
                            <a href="/nited/supervisor/calendar.php" style="background: #198754; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px;">จัดการผ่านปฏิทิน</a>
                        <?php elseif ($r['status'] == 'approved'): ?>
                            <a href="/nited/supervisor/evaluate.php?id=<?php echo $r['id']; ?>" style="background: #0d6efd; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px;"><i class="fas fa-edit"></i> ทำการประเมิน</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?>
                <tr><td colspan="5" style="padding: 20px; text-align: center;">ไม่มีภาระงานค้าง</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
