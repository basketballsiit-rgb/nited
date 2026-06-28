<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('executive');
// Additional check for Deputy Director
$user_pos = $_SESSION['position'] ?? '';
if (strpos($user_pos, 'รองผู้อำนวยการ') === false) {
    header("Location: /nited/executive/dashboard.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Get active academic year
$stmt = $pdo->query("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
$active_year = $stmt->fetch();
$year_id = $active_year ? $active_year['id'] : 0;

// Fetch teachers list (teachers + supervisors)
$stmt = $pdo->query("SELECT id, name, department FROM users WHERE role IN ('teacher', 'supervisor') ORDER BY name ASC");
$teachers = $stmt->fetchAll();
?>

<div class="content-header" style="margin-bottom: 20px;">
    <h2><i class="fas fa-walking"></i> นิเทศเร่งด่วน (Walk-in Supervision)</h2>
    <p class="text-muted">สร้างบันทึกการนิเทศโดยพลการ (ไม่ต้องรอครูจอง) และเข้าสู่หน้าจอประเมินทันที</p>
</div>

<div class="content-card" style="max-width: 600px; margin: 0 auto;">
    <?php if ($year_id == 0): ?>
        <div class="alert alert-danger" style="color: #721c24; background-color: #f8d7da; padding: 15px; border-radius: 5px;">
            <i class="fas fa-exclamation-triangle"></i> ไม่พบปีการศึกษาที่กำลังใช้งาน กรุณาตั้งค่าปีการศึกษาก่อน
        </div>
    <?php else: ?>
        <form id="adhocForm">
            <input type="hidden" name="academic_year_id" value="<?php echo $year_id; ?>">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">เลือกผู้สอนที่ต้องการนิเทศ <span style="color:red">*</span></label>
                <select name="teacher_id" class="form-control" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="">-- เลือกครูผู้สอน --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo $t['id']; ?>">
                            <?php echo htmlspecialchars($t['name']) . ($t['department'] ? ' (' . htmlspecialchars($t['department']) . ')' : ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">รหัสวิชา <span style="color:red">*</span></label>
                    <input type="text" name="subject_code" class="form-control" placeholder="เช่น 20000-1401" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc;">
                </div>
                <div style="flex: 2;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">รายวิชาที่สอน <span style="color:red">*</span></label>
                    <input type="text" name="subject_name" class="form-control" placeholder="เช่น คณิตศาสตร์อุตสาหกรรม" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">ระดับชั้น <span style="color:red">*</span></label>
                <select name="level" class="form-control" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="">-- เลือกระดับชั้น --</option>
                    <option value="ปวช. 1">ปวช. 1</option>
                    <option value="ปวช. 2">ปวช. 2</option>
                    <option value="ปวช. 3">ปวช. 3</option>
                    <option value="ปวส. 1">ปวส. 1</option>
                    <option value="ปวส. 2">ปวส. 2</option>
                    <option value="ปริญญาตรี">ปริญญาตรี</option>
                </select>
            </div>

            <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba; margin-bottom: 20px; font-size: 14px; color: #856404;">
                <i class="fas fa-info-circle"></i> ระบบจะบันทึกวัน-เวลาการนิเทศเป็น **ตอนนี้** ทันที และจะพาคุณเข้าสู่หน้าจอให้คะแนนการนิเทศโดยอัตโนมัติ
            </div>

            <button type="submit" class="btn-gradient" style="width: 100%; padding: 12px; font-size: 16px; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-arrow-right"></i> สร้างบันทึกและไปหน้าประเมิน
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
document.getElementById('adhocForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    Swal.fire({
        title: 'กำลังสร้างบันทึกการนิเทศ...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('adhoc_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'สร้างบันทึกสำเร็จ!',
                text: 'กำลังพาท่านไปหน้าประเมิน...',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location = '/nited/supervisor/evaluate.php?id=' + data.supervision_id;
            });
        } else {
            Swal.fire('ข้อผิดพลาด', data.message, 'error');
        }
    })
    .catch(() => {
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
