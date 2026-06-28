<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Fetch departments
$stmt = $pdo->query("SELECT * FROM departments ORDER BY name ASC");
$departments = $stmt->fetchAll();
?>

<div class="content-header"
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-building"></i> จัดการสาขาวิชา (แผนก)</h2>
        <p class="text-muted">เพิ่ม ลบ แก้ไข รายชื่อสาขาวิชาในระบบ</p>
    </div>
    <button class="btn-gradient" onclick="openAddModal()"><i class="fas fa-plus"></i> เพิ่มสาขาวิชา</button>
</div>

<div class="content-card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background-color: #f8f9fa; border-bottom: 2px solid #ddd;">
                    <th style="padding: 12px; border-bottom: 1px solid #ddd; width: 10%;">ลำดับ</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd; width: 60%;">ชื่อสาขาวิชา</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd; text-align: center; width: 30%;">จัดการ
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $idx => $dept): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">
                            <?php echo $idx + 1; ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <button onclick='openEditModal(<?php echo json_encode($dept); ?>)'
                                style="background: none; border: none; color: #0d6efd; cursor: pointer; margin-right: 10px;"
                                title="แก้ไข"><i class="fas fa-edit"></i> แก้ไข</button>
                            <button onclick="deleteDept(<?php echo $dept['id']; ?>)"
                                style="background: none; border: none; color: #dc3545; cursor: pointer;" title="ลบ"><i
                                    class="fas fa-trash-alt"></i> ลบ</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($departments)): ?>
                    <tr>
                        <td colspan="3" style="padding: 20px; text-align: center; color: #888;">ไม่มีข้อมูลสาขาวิชา</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Overlay styles -->
<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1050;
    }

    .modal-content {
        background: white;
        padding: 25px;
        border-radius: 10px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .close-modal {
        float: right;
        font-size: 24px;
        cursor: pointer;
        color: #aaa;
        border: none;
        background: none;
        line-height: 1;
    }

    .close-modal:hover {
        color: #333;
    }
</style>

<!-- Add/Edit Department Modal -->
<div id="deptModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal()">&times;</button>
        <h3 id="modalTitle" style="margin-top: 0; margin-bottom: 20px;">เพิ่มสาขาวิชา</h3>

        <form id="deptForm">
            <input type="hidden" id="dept_id" name="id">
            <input type="hidden" name="action" id="form_action" value="create">

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px;">ชื่อสาขาวิชา <span style="color:red">*</span></label>
                <input type="text" id="name" name="name"
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                    placeholder="เช่น ช่างยนต์" required>
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="closeModal()"
                    style="padding: 10px 15px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer; margin-right: 10px;">ยกเลิก</button>
                <button type="submit" class="btn-gradient" style="padding: 10px 20px;">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('deptForm').reset();
        document.getElementById('dept_id').value = '';
        document.getElementById('form_action').value = 'create';
        document.getElementById('modalTitle').innerText = 'เพิ่มสาขาวิชา';
        document.getElementById('deptModal').style.display = 'flex';
    }

    function openEditModal(dept) {
        document.getElementById('deptForm').reset();
        document.getElementById('dept_id').value = dept.id;
        document.getElementById('form_action').value = 'update';
        document.getElementById('modalTitle').innerText = 'แก้ไขสาขาวิชา';
        document.getElementById('name').value = dept.name;
        document.getElementById('deptModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('deptModal').style.display = 'none';
    }

    document.getElementById('deptForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        Swal.fire({
            title: 'กำลังบันทึกข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('department_action.php', {
            method: 'POST',
            body: formData
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
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้', 'error');
            });
    });

    function deleteDept(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "คุณต้องการลบสาขาวิชานี้ใช่หรือไม่?",
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

                fetch('department_action.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบสำเร็จ!',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('ข้อผิดพลาด', data.message, 'error');
                        }
                    });
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>