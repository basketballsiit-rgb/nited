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

<style>
    .supervisions-table th, .supervisions-table td {
        font-size: 13px;
        white-space: nowrap;
        vertical-align: middle;
        padding: 8px 10px;
    }
    .supervisions-table td:nth-child(3) {
        white-space: normal; /* Allow subject name to wrap if really long, but usually code keeps it tight */
    }
</style>

<div class="content-card">
    <div class="table-responsive">
        <table class="supervisions-table" style="width: 100%; border-collapse: collapse;">
            <thead style="background-color: #f8f9fa;">
                <tr>
                    <th style="border-bottom: 2px solid #ddd;">วันที่/เวลา</th>
                    <th style="border-bottom: 2px solid #ddd;">ครูผู้สอน</th>
                    <th style="border-bottom: 2px solid #ddd;">วิชา</th>
                    <th style="border-bottom: 2px solid #ddd;">กรรมการนิเทศ</th>
                    <th style="border-bottom: 2px solid #ddd;">สถานะ</th>
                    <th style="border-bottom: 2px solid #ddd; text-align: center;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($supervisions) > 0): ?>
                    <?php foreach ($supervisions as $s): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td>
                                <?php 
                                echo date('d/m/Y', strtotime($s['scheduled_date'])) . '<br>';
                                echo date('H:i', strtotime($s['scheduled_date'])) . ' - ' . date('H:i', strtotime($s['end_time'])) . ' น.';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($s['teacher_name']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($s['subject_code']); ?></strong><br>
                                <span style="color: #6c757d; font-size: 12px;"><?php echo htmlspecialchars($s['subject_name']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($s['supervisor_name']); ?></td>
                            <td>
                                <?php 
                                    $status_badges = [
                                        'pending' => '<span style="background: #f1c40f; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; white-space: nowrap;">รออนุมัติ</span>',
                                        'approved' => '<span style="background: #2ecc71; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; white-space: nowrap;">อนุมัติแล้ว</span>',
                                        'rejected' => '<span style="background: #e74c3c; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; white-space: nowrap;">ถูกปฏิเสธ</span>',
                                        'completed' => '<span style="background: #3498db; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; white-space: nowrap;">ประเมินแล้ว</span>'
                                    ];
                                    echo $status_badges[$s['status']] ?? $s['status'];
                                ?>
                            </td>
                            <td style="text-align: center;">
                                <button onclick="deleteSupervision(<?php echo $s['id']; ?>)" class="btn btn-sm" style="background-color: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer; white-space: nowrap;">
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
    Swal.fire({
        title: 'ยืนยันการลบข้อมูล?',
        text: "คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลการจองนิเทศนี้? (การกระทำนี้ไม่สามารถย้อนกลับได้)",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#bdc3c7',
        confirmButtonText: 'ใช่, ลบข้อมูลเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            // แสดง Loading
            Swal.fire({
                title: 'กำลังลบข้อมูล...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

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
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด!',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'
                });
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
