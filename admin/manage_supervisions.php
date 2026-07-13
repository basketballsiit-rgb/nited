<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';

// Fetch all academic years for filter
$stmt = $pdo->query("SELECT * FROM academic_years ORDER BY year DESC, term DESC");
$years = $stmt->fetchAll();

$selected_year_id = $_GET['year_id'] ?? null;
if (!$selected_year_id && count($years) > 0) {
    // Find active year
    foreach ($years as $y) {
        if ($y['is_active']) {
            $selected_year_id = $y['id'];
            break;
        }
    }
    if (!$selected_year_id) $selected_year_id = $years[0]['id'];
}

// Fetch supervisions for selected year
$supervisions = [];
if ($selected_year_id) {
    $stmt = $pdo->prepare("
        SELECT s.*, 
               t.name as teacher_name, 
               sup.name as supervisor_name
        FROM supervisions s
        JOIN users t ON s.teacher_id = t.id
        JOIN users sup ON s.supervisor_id = sup.id
        WHERE s.academic_year_id = ?
        ORDER BY s.scheduled_date DESC
    ");
    $stmt->execute([$selected_year_id]);
    $supervisions = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-calendar-times"></i> จัดการข้อมูลการจองนิเทศ</h2>
        <p class="text-muted">ผู้ดูแลระบบสามารถตรวจสอบและยกเลิก/ลบการจองนิเทศที่เกิดจากข้อผิดพลาดได้</p>
    </div>
</div>

<div class="content-card" style="margin-bottom: 20px;">
    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
        <label for="year_id" style="font-weight: bold;">เลือกภาคเรียน/ปีการศึกษา:</label>
        <select name="year_id" id="year_id" class="form-control" style="width: 250px;" onchange="this.form.submit()">
            <?php foreach ($years as $y): ?>
                <option value="<?php echo $y['id']; ?>" <?php echo $selected_year_id == $y['id'] ? 'selected' : ''; ?>>
                    ภาคเรียนที่ <?php echo htmlspecialchars($y['term']); ?>/<?php echo htmlspecialchars($y['year']); ?>
                    <?php echo $y['is_active'] ? '(ปัจจุบัน)' : ''; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="content-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>วันที่/เวลา</th>
                    <th>ครูผู้สอน</th>
                    <th>วิชา</th>
                    <th>กรรมการนิเทศ</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($supervisions) > 0): ?>
                    <?php foreach ($supervisions as $s): ?>
                        <tr>
                            <td>
                                <?php 
                                echo date('d/m/Y', strtotime($s['scheduled_date'])) . '<br>';
                                echo date('H:i', strtotime($s['scheduled_date'])) . ' - ' . date('H:i', strtotime($s['end_time'])) . ' น.';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($s['teacher_name']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($s['subject_code']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($s['subject_name']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($s['supervisor_name']); ?></td>
                            <td>
                                <?php 
                                    $status_badges = [
                                        'pending' => '<span style="background: #f1c40f; color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 12px;">รออนุมัติ</span>',
                                        'approved' => '<span style="background: #2ecc71; color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 12px;">อนุมัติแล้ว</span>',
                                        'rejected' => '<span style="background: #e74c3c; color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 12px;">ถูกปฏิเสธ</span>',
                                        'completed' => '<span style="background: #3498db; color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 12px;">ประเมินแล้ว</span>'
                                    ];
                                    echo $status_badges[$s['status']] ?? $s['status'];
                                ?>
                            </td>
                            <td>
                                <button onclick="deleteSupervision(<?php echo $s['id']; ?>)" class="btn btn-sm" style="background-color: #e74c3c; color: white; border: none; padding: 5px 10px;">
                                    <i class="fas fa-trash-alt"></i> ลบ/ยกเลิก
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 20px; color: #7f8c8d;">ไม่พบข้อมูลการจองในภาคเรียนนี้</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function deleteSupervision(id) {
    if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลการจองนิเทศนี้?\n(การกระทำนี้ไม่สามารถย้อนกลับได้)')) {
        fetch('supervision_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete&id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
        });
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
