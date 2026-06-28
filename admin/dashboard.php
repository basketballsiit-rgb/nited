<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Fetch summary dashboard stats
$stats = [
    'teachers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn(),
    'supervisors' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'supervisor'")->fetchColumn(),
    'total_supervisions' => $pdo->query("SELECT COUNT(*) FROM supervisions")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM supervisions WHERE status = 'completed'")->fetchColumn()
];

// Get active academic year
$stmt = $pdo->query("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
$active_year = $stmt->fetch();
$year_id = $active_year ? $active_year['id'] : 0;

// Fetch stats by department
$dept_stats = [];
if ($year_id > 0) {
    $stmt = $pdo->prepare("
        SELECT 
            u.department, 
            COUNT(DISTINCT u.id) as total_teachers,
            COUNT(DISTINCT CASE WHEN s.status = 'completed' THEN u.id END) as supervised_count,
            COUNT(DISTINCT CASE WHEN lp.status IN ('approved', 'revision', 'rejected') THEN u.id END) as plan_evaluated_count
        FROM users u
        LEFT JOIN supervisions s ON u.id = s.teacher_id AND s.academic_year_id = ?
        LEFT JOIN lesson_plans lp ON u.id = lp.teacher_id AND lp.academic_year_id = ?
        WHERE u.role = 'teacher' 
          AND u.department IS NOT NULL 
          AND u.department != ''
        GROUP BY u.department
        ORDER BY u.department ASC
    ");
    $stmt->execute([$year_id, $year_id]);
    $dept_stats = $stmt->fetchAll();
}
?>

<div class="content-header" style="margin-bottom: 20px;">
    <h2>แดชบอร์ดผู้ดูแลระบบ</h2>
    <p class="text-muted">ภาพรวมของระบบนิเทศการจัดการเรียนการสอน</p>
</div>

<div class="stat-grid">
    <div class="stat-card bg-blue">
        <h3><i class="fas fa-chalkboard-teacher"></i> จำนวนครูทั้งหมด</h3>
        <p class="value">
            <?php echo $stats['teachers']; ?>
        </p>
        <i class="fas fa-users icon"></i>
    </div>
    <div class="stat-card bg-orange">
        <h3><i class="fas fa-user-tie"></i> จำนวนกรรมการนิเทศ</h3>
        <p class="value">
            <?php echo $stats['supervisors']; ?>
        </p>
        <i class="fas fa-user-check icon"></i>
    </div>
    <div class="stat-card bg-green">
        <h3><i class="fas fa-clipboard-check"></i> จำนวนการนิเทศที่เสร็จสิ้น</h3>
        <p class="value">
            <?php echo $stats['completed']; ?>
        </p>
        <i class="fas fa-tasks icon"></i>
    </div>
    <div class="stat-card bg-red">
        <h3><i class="fas fa-calendar-alt"></i> การจัดสอนทั้งหมดในระบบ</h3>
        <p class="value">
            <?php echo $stats['total_supervisions']; ?>
        </p>
        <i class="fas fa-layer-group icon"></i>
    </div>
</div>

<?php
$lp_stats = [
    'total_plans' => $pdo->query("SELECT COUNT(*) FROM lesson_plans")->fetchColumn(),
    'completed_plans' => $pdo->query("SELECT COUNT(*) FROM lesson_plans WHERE status IN ('approved', 'revision', 'rejected')")->fetchColumn(),
    'pending_plans' => $pdo->query("SELECT COUNT(*) FROM lesson_plans WHERE status = 'pending'")->fetchColumn()
];

// Fetch reviewer workload for lesson plans
$reviewer_workload = $pdo->query("
    SELECT u.name, 
           COUNT(lp.id) as total_assigned,
           SUM(CASE WHEN lp.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
           SUM(CASE WHEN lp.status != 'pending' THEN 1 ELSE 0 END) as completed_count
    FROM users u
    JOIN lesson_plans lp ON u.id = lp.reviewer_id
    GROUP BY u.id
    ORDER BY pending_count DESC, total_assigned DESC
")->fetchAll();
?>

<div class="content-header" style="margin-top: 40px; margin-bottom: 20px;">
    <h2><i class="fas fa-file-signature"></i> สถิติการตรวจแผนการจัดการเรียนรู้เต็มเล่ม</h2>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
        <h3><i class="fas fa-file-upload"></i> แผนที่ส่งมาทั้งหมด</h3>
        <p class="value"><?php echo $lp_stats['total_plans']; ?></p>
        <i class="fas fa-copy icon"></i>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b, #38f9d7); color: #000;">
        <h3 style="color: #000;"><i class="fas fa-check-circle"></i> ตรวจเสร็จแล้ว</h3>
        <p class="value" style="color: #000;"><?php echo $lp_stats['completed_plans']; ?></p>
        <i class="fas fa-clipboard-check icon" style="color: rgba(0,0,0,0.1);"></i>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #f6d365, #fda085); color: #000;">
        <h3 style="color: #000;"><i class="fas fa-hourglass-half"></i> ค้างตรวจ</h3>
        <p class="value" style="color: #000;"><?php echo $lp_stats['pending_plans']; ?></p>
        <i class="fas fa-clock icon" style="color: rgba(0,0,0,0.1);"></i>
    </div>
</div>

<div class="content-card" style="margin-top: 20px;">
    <h3><i class="fas fa-tasks"></i> ภาระงานกรรมการ (ตรวจแผนเต็มเล่ม)</h3>
    <table style="width: 100%; border-collapse: collapse; text-align: left; margin-top: 15px;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 10px; border-bottom: 2px solid #ddd;">ชื่อกรรมการ</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: center;">ได้รับมอบหมายทั้งหมด</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: center;">ตรวจแล้ว</th>
                <th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: center;">ค้างตรวจ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reviewer_workload as $rw): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?php echo htmlspecialchars($rw['name']); ?></td>
                    <td style="padding: 10px; text-align: center; font-weight: bold;"><?php echo $rw['total_assigned']; ?></td>
                    <td style="padding: 10px; text-align: center; color: #198754;"><?php echo $rw['completed_count']; ?></td>
                    <td style="padding: 10px; text-align: center; <?php echo ($rw['pending_count'] > 0) ? 'color: #dc3545; font-weight: bold;' : 'color: #aaa;'; ?>">
                        <?php echo $rw['pending_count']; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reviewer_workload)): ?>
                <tr><td colspan="4" style="padding: 20px; text-align: center; color: #888;">ยังไม่มีการมอบหมายงานตรวจแผน</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="content-header" style="margin-top: 40px; margin-bottom: 20px;">
    <h2><i class="fas fa-building"></i> ความคืบหน้าแยกตามสาขาวิชา</h2>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <?php foreach ($dept_stats as $ds): ?>
    <div style="background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 4px solid #4f46e5;">
        <h3 style="margin-top: 0; margin-bottom: 15px; color: #333; font-size: 1.1rem;"><?php echo htmlspecialchars($ds['department']); ?></h3>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
            <span style="color: #666;"><i class="fas fa-users"></i> จำนวนครูทั้งหมด</span>
            <span style="font-weight: bold;"><?php echo $ds['total_teachers']; ?> คน</span>
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
            <span style="color: #666;"><i class="fas fa-chalkboard-teacher"></i> ได้รับการนิเทศแล้ว</span>
            <span style="font-weight: bold; color: <?php echo ($ds['supervised_count'] == $ds['total_teachers'] && $ds['total_teachers'] > 0) ? '#198754' : '#f59e0b'; ?>;">
                <?php echo $ds['supervised_count']; ?> / <?php echo $ds['total_teachers']; ?>
            </span>
        </div>
        
        <div style="display: flex; justify-content: space-between;">
            <span style="color: #666;"><i class="fas fa-file-signature"></i> ประเมินแผนการสอนแล้ว</span>
            <span style="font-weight: bold; color: <?php echo ($ds['plan_evaluated_count'] == $ds['total_teachers'] && $ds['total_teachers'] > 0) ? '#198754' : '#f59e0b'; ?>;">
                <?php echo $ds['plan_evaluated_count']; ?> / <?php echo $ds['total_teachers']; ?>
            </span>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($dept_stats)): ?>
        <p style="grid-column: 1 / -1; text-align: center; color: #888; background: white; padding: 20px; border-radius: 10px;">ยังไม่มีข้อมูลสาขาวิชา</p>
    <?php endif; ?>
</div>

<div class="content-card" style="margin-top: 30px;">
    <h3>ยินดีต้อนรับสู่ระบบบริหารจัดการ</h3>
    <p>คุณสามารถจัดการผู้ใช้งาน หัวข้อการประเมินการนิเทศ และแบบฟอร์มตรวจแผน ได้จากเมนูด้านซ้ายมือ</p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>