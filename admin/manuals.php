<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';

// Fetch manuals
$stmt = $pdo->query("SELECT * FROM manuals ORDER BY created_at DESC");
$manuals = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-book"></i> จัดการคู่มือการใช้งาน</h2>
        <p class="text-muted">อัปโหลดและจัดการคู่มือสำหรับครูผู้สอนและกรรมการนิเทศ</p>
    </div>
    <button class="btn-gradient" onclick="openAddModal()">
        <i class="fas fa-plus"></i> เพิ่มคู่มือใหม่
    </button>
</div>

<div class="content-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">ชื่อคู่มือ</th>
                    <th style="width: 20%;">กลุ่มเป้าหมาย</th>
                    <th style="width: 20%;">วันที่อัปโหลด</th>
                    <th style="width: 20%;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($manuals) > 0): ?>
                    <?php foreach ($manuals as $index => $m): ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($m['title']); ?></td>
                            <td style="text-align: center;">
                                <?php 
                                    if ($m['role_target'] == 'all') echo '<span style="color:#0dcaf0; font-weight:bold;">ทุกคน</span>';
                                    elseif ($m['role_target'] == 'teacher') echo '<span style="color:#198754; font-weight:bold;">ครูผู้สอน</span>';
                                    elseif ($m['role_target'] == 'supervisor') echo '<span style="color:#0d6efd; font-weight:bold;">กรรมการนิเทศ</span>';
                                ?>
                            </td>
                            <td style="text-align: center;"><?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?></td>
                            <td style="text-align: center;">
                                <a href="/nited/<?php echo htmlspecialchars($m['file_path']); ?>" target="_blank" class="btn btn-sm" style="background-color:#17a2b8; color:white; border:none; padding:5px 10px; text-decoration:none;"><i class="fas fa-eye"></i> ดูไฟล์</a>
                                <button onclick="deleteManual(<?php echo $m['id']; ?>)" class="btn btn-sm btn-danger" style="padding:5px 10px;"><i class="fas fa-trash"></i> ลบ</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; color: #999; padding: 20px;">ไม่มีข้อมูลคู่มือ</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1050;">
    <div class="modal-content" style="background: white; padding: 25px; border-radius: 10px; width: 100%; max-width: 500px;">
        <h3 style="margin-top: 0; margin-bottom: 20px;">เพิ่มคู่มือใหม่</h3>
        <form id="addForm">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">ชื่อคู่มือ <span style="color:red">*</span></label>
                <input type="text" name="title" required class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">กลุ่มเป้าหมาย <span style="color:red">*</span></label>
                <select name="role_target" required class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="all">ทุกคน (ครู และ กรรมการ)</option>
                    <option value="teacher">เฉพาะครูผู้สอน</option>
                    <option value="supervisor">เฉพาะกรรมการนิเทศ / ผู้บริหาร</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">ไฟล์คู่มือ (PDF, Word, PPT) <span style="color:red">*</span></label>
                <input type="file" name="manual_file" required accept=".pdf,.doc,.docx,.ppt,.pptx" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="text-align: right; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                <button type="button" onclick="closeAddModal()" style="padding: 8px 15px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer; margin-right: 10px;">ยกเลิก</button>
                <button type="submit" class="btn-gradient" style="padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; color: white;">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
    document.getElementById('addForm').reset();
}

document.getElementById('addForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    Swal.fire({
        title: 'กำลังอัปโหลด...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('manual_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire({icon: 'success', title: 'บันทึกสำเร็จ', timer: 1500, showConfirmButton: false})
            .then(() => location.reload());
        } else {
            Swal.fire('ข้อผิดพลาด', data.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
    });
});

function deleteManual(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบคู่มือนี้ใช่หรือไม่? ไฟล์ที่อัปโหลดจะถูกลบไปด้วย",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('manual_action.php', {
                method: 'POST', body: formData
            })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire({icon: 'success', title: 'ลบสำเร็จ', timer: 1500, showConfirmButton: false})
                    .then(() => location.reload());
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
