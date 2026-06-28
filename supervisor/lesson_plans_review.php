<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'executive') {
    header("Location: /nited/index.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$reviewer_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get active academic year
$stmt = $pdo->query("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
$active_year = $stmt->fetch();
$year_id = $active_year ? $active_year['id'] : 0;
$year_txt = $active_year ? ($active_year['term'] . '/' . $active_year['year']) : 'ยังไม่ได้กำหนด';

// Fetch plans assigned to this reviewer
$query = "
    SELECT lp.*, t.name as teacher_name 
    FROM lesson_plans lp
    JOIN users t ON lp.teacher_id = t.id
";

// If executive, maybe they see everything? User said "System assigns randomly to supervisor or executive".
// We will restrict to only ones assigned to them specifically, unless we want to add an 'all' view later.
$query .= " WHERE lp.reviewer_id = ?";
$query .= " ORDER BY lp.submitted_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$reviewer_id]);
$assigned_plans = $stmt->fetchAll();
?>

<div class="content-header" style="margin-bottom: 20px;">
    <h2><i class="fas fa-file-signature"></i> ตรวจแผนการจัดการเรียนรู้ (แบบสมรรถนะวิชาชีพเต็มเล่ม)</h2>
    <p class="text-muted">รายการแผนการสอนที่ส่งมาให้ท่านเป็นผู้ประเมิน ปีการศึกษา <strong><?php echo htmlspecialchars($year_txt); ?></strong></p>
</div>

<div class="content-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0;"><i class="fas fa-tasks"></i> รายการที่ต้องดำเนินการ</h3>
    </div>
    
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 10px; border-bottom: 1px solid #ddd;">วันที่ส่ง</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd;">ครูผู้ส่ง</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd;">รายวิชา</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center;">สถานะ</th>
                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assigned_plans as $plan): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;">
                        <?php echo date('d/m/Y', strtotime($plan['submitted_at'])); ?><br>
                        <small style="color:#666;"><?php echo date('H:i', strtotime($plan['submitted_at'])); ?> น.</small>
                    </td>
                    <td style="padding: 10px;"><strong><?php echo htmlspecialchars($plan['teacher_name']); ?></strong></td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($plan['subject_name']); ?></td>
                    <td style="padding: 10px; text-align: center;">
                        <?php if ($plan['status'] === 'pending'): ?>
                            <span style="background-color: #ffc107; color: #000; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">รอการตรวจ</span>
                        <?php elseif ($plan['status'] === 'approved'): ?>
                            <span style="background-color: #198754; color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">ประเมินครบถ้วน</span>
                        <?php elseif ($plan['status'] === 'revision'): ?>
                            <span style="background-color: #fd7e14; color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">กลับไปแก้ไข</span>
                        <?php elseif ($plan['status'] === 'rejected'): ?>
                            <span style="background-color: #dc3545; color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">ไม่อนุมัติ</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <a href="/nited/<?php echo htmlspecialchars($plan['file_path']); ?>" target="_blank" style="color: #0d6efd; text-decoration: none; margin-right: 15px;" title="ดาวน์โหลดไฟล์ที่ส่ง">
                            <i class="fas fa-download"></i> โหลดไฟล์
                        </a>
                        
                        <?php if ($plan['status'] === 'pending'): ?>
                            <a href="lesson_plan_evaluate.php?id=<?php echo $plan['id']; ?>" class="btn-gradient" style="background: linear-gradient(135deg, #f39c12, #d35400); padding: 5px 10px; font-size: 12px; text-decoration: none;"><i class="fas fa-edit"></i> ทำการตรวจ</a>
                        <?php else: ?>
                            <a href="lesson_plan_evaluate.php?id=<?php echo $plan['id']; ?>" class="btn-gradient" style="padding: 5px 10px; font-size: 12px; text-decoration: none;"><i class="fas fa-eye"></i> ดูผลประเมิน</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($assigned_plans)): ?>
                <tr><td colspan="5" style="padding: 30px; text-align: center; color: #888;">ยังไม่มีรายการให้ตรวจในขณะนี้</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
