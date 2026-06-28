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

// Get active academic year
$stmt = $pdo->query("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
$active_year = $stmt->fetch();
$year_id = $active_year ? $active_year['id'] : 0;
$year_txt = $active_year ? ($active_year['term'] . '/' . $active_year['year']) : 'ยังไม่ได้กำหนดปีการศึกษา';

// Fetch submitted lesson plans
$stmt = $pdo->prepare("
    SELECT lp.*, u.name as reviewer_name 
    FROM lesson_plans lp
    LEFT JOIN users u ON lp.reviewer_id = u.id
    WHERE lp.teacher_id = ?
    ORDER BY lp.submitted_at DESC
");
$stmt->execute([$teacher_id]);
$lesson_plans = $stmt->fetchAll();
?>

<div class="content-header" style="margin-bottom: 20px;">
    <h2><i class="fas fa-file-upload"></i> ส่งแผนการจัดการเรียนรู้ (แบบสมรรถนะวิชาชีพเต็มเล่ม)</h2>
    <p class="text-muted">ปีการศึกษาปัจจุบัน: <strong><?php echo htmlspecialchars($year_txt); ?></strong></p>
</div>

<!-- Upload New Plan Form -->
<div class="content-card" style="margin-bottom: 30px;">
    <h3><i class="fas fa-plus-circle"></i> ส่งแผนการสอนใหม่</h3>
    <form id="submitPlanForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="submit_plan">
        <input type="hidden" name="academic_year_id" value="<?php echo $year_id; ?>">
        
        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 15px;">
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">รหัสวิชา <span style="color:red">*</span></label>
                <input type="text" name="subject_code" class="form-control" placeholder="เช่น 20000-1401" required>
            </div>
            <div style="flex: 2; min-width: 250px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">ชื่อวิชาที่สอน <span style="color:red">*</span></label>
                <input type="text" name="subject_name" class="form-control" placeholder="เช่น คณิตศาสตร์อุตสาหกรรม" required>
            </div>
        </div>
        
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">ระดับชั้น <span style="color:red">*</span></label>
                <select name="level" class="form-control" required>
                    <option value="">-- เลือกระดับชั้น --</option>
                    <option value="ปวช. 1">ปวช. 1</option>
                    <option value="ปวช. 2">ปวช. 2</option>
                    <option value="ปวช. 3">ปวช. 3</option>
                    <option value="ปวส. 1">ปวส. 1</option>
                    <option value="ปวส. 2">ปวส. 2</option>
                    <option value="ปริญญาตรี">ปริญญาตรี</option>
                </select>
            </div>
            <div style="flex: 2; min-width: 250px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold;">ไฟล์แผนการสอน (PDF/Word/ZIP) <span style="color:red">*</span></label>
                <input type="file" name="lesson_plan_file" class="form-control" accept=".pdf,.doc,.docx,.zip" required>
            </div>
        </div>
        
        <div style="margin-top: 15px; padding: 10px; background-color: #eef2ff; border-left: 4px solid #4f46e5; border-radius: 4px; font-size: 13px;">
            <i class="fas fa-info-circle"></i> เมื่อกดส่ง ระบบจะทำการสุ่มและมอบหมายให้กรรมการ 1 ท่าน เป็นผู้ประเมินแผนการสอนนี้โดยอัตโนมัติ
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn-gradient"><i class="fas fa-paper-plane"></i> ยืนยันการส่งไฟล์</button>
        </div>
    </form>
</div>

<!-- List of Submitted Plans -->
<div class="content-card">
    <h3 style="margin-bottom: 20px;"><i class="fas fa-list"></i> ประวัติการส่งแผนการสอน</h3>
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 12px; border-bottom: 1px solid #ddd;">วันที่ส่ง</th>
                <th style="padding: 12px; border-bottom: 1px solid #ddd;">รายวิชา</th>
                <th style="padding: 12px; border-bottom: 1px solid #ddd;">กรรมการประเมิน</th>
                <th style="padding: 12px; border-bottom: 1px solid #ddd; text-align: center;">สถานะ</th>
                <th style="padding: 12px; border-bottom: 1px solid #ddd; text-align: center;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lesson_plans as $plan): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">
                        <?php echo date('d/m/Y', strtotime($plan['submitted_at'])); ?><br>
                        <small style="color: #888;"><?php echo date('H:i', strtotime($plan['submitted_at'])); ?> น.</small>
                    </td>
                    <td style="padding: 12px;">
                        <strong><?php echo htmlspecialchars($plan['subject_name']); ?></strong><br>
                        <small style="color: #666;">รหัสวิชา: <?php echo htmlspecialchars($plan['subject_code']); ?></small><br>
                        <small style="color: #666;">ระดับ: <?php echo htmlspecialchars($plan['level']); ?></small>
                    </td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($plan['reviewer_name'] ?? '-'); ?></td>
                    <td style="padding: 12px; text-align: center;">
                        <?php if ($plan['status'] === 'pending'): ?>
                            <span style="background-color: #ffc107; color: #000; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">รอการตรวจ</span>
                        <?php elseif ($plan['status'] === 'approved'): ?>
                            <span style="background-color: #198754; color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">ประเมินแล้ว (ผ่าน)</span>
                        <?php elseif ($plan['status'] === 'revision'): ?>
                            <span style="background-color: #fd7e14; color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">กลับไปแก้ไข</span>
                        <?php elseif ($plan['status'] === 'rejected'): ?>
                            <span style="background-color: #dc3545; color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">ไม่ผ่านการประเมิน</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <a href="/nited/<?php echo htmlspecialchars($plan['file_path']); ?>" target="_blank" style="color: #0d6efd; text-decoration: none; margin-right: 15px;" title="ดาวน์โหลดไฟล์ที่ส่ง">
                            <i class="fas fa-download"></i> โหลดไฟล์
                        </a>
                        
                        <?php if ($plan['status'] !== 'pending'): ?>
                            <a href="lesson_plan_view_result.php?id=<?php echo $plan['id']; ?>" class="btn-gradient" style="padding: 5px 10px; font-size: 13px; text-decoration: none;">ดูผลตรวจ</a>
                        <?php else: ?>
                            <span style="color: #aaa; font-size: 13px;"><i class="fas fa-clock"></i> รอกรรมการ</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($lesson_plans)): ?>
                <tr><td colspan="5" style="padding: 30px; text-align: center; color: #888;">ยังไม่มีประวัติการส่งแผนการสอน</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('submitPlanForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const yearId = <?php echo $year_id; ?>;
    if (yearId === 0) {
        Swal.fire('ข้อผิดพลาด', 'ยังไม่ได้กำหนดปีการศึกษาปัจจุบัน กรุณาติดต่อผู้ดูแลระบบ', 'error');
        return;
    }

    const formData = new FormData(this);

    Swal.fire({
        title: 'กำลังอัปโหลดไฟล์...',
        text: 'ระบบกำลังมอบหมายให้กรรมการประเมิน กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('lesson_plan_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'ส่งแผนการสอนสำเร็จ!',
                text: 'ได้มอบหมายให้กรรมการ: ' + data.assigned_reviewer,
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('ข้อผิดพลาด', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('ข้อผิดพลาด', 'ติดต่อเซิร์ฟเวอร์ไม่ได้', 'error');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
