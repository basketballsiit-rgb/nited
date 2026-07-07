<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';
?>
<!-- SheetJS for Excel Import -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<?php

// Fetch users
$stmt = $pdo->query("SELECT id, username, name, role, academic_standing, position, department, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Fetch departments for dropdown
$stmt_dept = $pdo->query("SELECT name FROM departments ORDER BY name ASC");
$all_departments = $stmt_dept->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="content-header"
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-users"></i> จัดการผู้ใช้งาน</h2>
        <p class="text-muted">เพิ่ม ลบ แก้ไข ข้อมูลบุคลากรในระบบ</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button class="btn-gradient" style="background: linear-gradient(135deg, #11998E, #38EF7D);"
            onclick="document.getElementById('excelFile').click()">
            <i class="fas fa-file-excel"></i> นำเข้าผ่าน Excel
        </button>
        <input type="file" id="excelFile" style="display:none;" accept=".xlsx, .xls, .csv">
        <button class="btn-gradient" onclick="openAddModal()"><i class="fas fa-plus"></i> เพิ่มผู้ใช้งาน</button>
    </div>
</div>

<div class="content-card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background-color: #f8f9fa; border-bottom: 2px solid #ddd;">
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">ชื่อ-สกุล</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">ชื่อผู้ใช้งาน (Username)</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">บทบาท</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd;">ตำแหน่ง/สาขา/วิทยฐานะ</th>
                    <th style="padding: 12px; border-bottom: 1px solid #ddd; text-align: center;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">
                            <?php echo htmlspecialchars($u['name']); ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo htmlspecialchars($u['username']); ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php
                            $roleColors = [
                                'admin' => '#dc3545',
                                'executive' => '#6f42c1',
                                'supervisor' => '#fd7e14',
                                'teacher' => '#198754'
                            ];
                            $color = $roleColors[$u['role']] ?? '#6c757d';
                            ?>
                            <span
                                style="background-color: <?php echo $color; ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?php echo htmlspecialchars(ucfirst($u['role'])); ?>
                            </span>
                        </td>
                        <td style="padding: 12px;">
                            <div style="font-size: 13px; font-weight: bold;">
                                <?php echo htmlspecialchars($u['position']); ?>
                            </div>
                            <div style="font-size: 13px; color: #444;">
                                <?php echo !empty($u['department']) ? htmlspecialchars($u['department']) : ''; ?>
                            </div>
                            <div style="font-size: 12px; color: #666;">
                                <?php echo htmlspecialchars($u['academic_standing']); ?>
                            </div>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <button onclick='openEditModal(<?php echo json_encode($u); ?>)'
                                style="background: none; border: none; color: #0d6efd; cursor: pointer; margin-right: 10px;"
                                title="แก้ไข"><i class="fas fa-edit"></i></button>
                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <button onclick="deleteUser(<?php echo $u['id']; ?>)"
                                    style="background: none; border: none; color: #dc3545; cursor: pointer;" title="ลบ"><i
                                        class="fas fa-trash-alt"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" style="padding: 20px; text-align: center;">ไม่มีข้อมูลผู้ใช้งาน</td>
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
        overflow-y: auto;
        padding: 20px;
    }

    .modal-content {
        background: white;
        padding: 25px;
        border-radius: 10px;
        width: 100%;
        max-width: 500px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        max-height: calc(100vh - 40px);
        overflow-y: auto;
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

<!-- Add/Edit User Modal -->
<div id="userModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal()">&times;</button>
        <h3 id="modalTitle" style="margin-top: 0; margin-bottom: 20px;">เพิ่มผู้ใช้งาน</h3>

        <form id="userForm">
            <input type="hidden" id="user_id" name="user_id">
            <input type="hidden" name="action" id="form_action" value="create">

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">ชื่อ-นามสกุล <span style="color:red">*</span></label>
                <input type="text" id="name" name="name"
                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                    required>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">ชื่อผู้ใช้งาน (Username) <span
                        style="color:red">*</span></label>
                <input type="text" id="username" name="username"
                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                    required>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">รหัสผ่าน <span id="pwd_req"
                        style="color:red">*</span></label>
                <input type="password" id="password" name="password"
                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                <small id="pwd_help"
                    style="color: #666; display: none;">ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</small>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">บทบาท <span style="color:red">*</span></label>
                <select id="role" name="role"
                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                    required>
                    <option value="">-- เลือกบทบาท --</option>
                    <option value="teacher">ครู (Teacher)</option>
                    <option value="supervisor">กรรมการนิเทศ (Supervisor)</option>
                    <option value="executive">ผู้บริหาร (Executive)</option>
                    <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                </select>
            </div>

            <div id="extraFields"
                style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px;">ตำแหน่ง</label>
                    <select id="position" name="position"
                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                        <option value="">-- ไม่ระบุ --</option>
                        <option value="ครูผู้สอน">ครูผู้สอน</option>
                        <option value="หัวหน้าสาขาวิชา">หัวหน้าสาขาวิชา</option>
                        <option value="รองผู้อำนวยการฝ่ายวิชาการ">รองผู้อำนวยการฝ่ายวิชาการ</option>
                        <option value="ผู้อำนวยการ">ผู้อำนวยการ</option>
                    </select>
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display: block; margin-bottom: 5px;">สาขาวิชา (แผนก)</label>
                    <select id="department" name="department"
                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                        <option value="">-- ไม่ระบุ --</option>
                        <?php foreach ($all_departments as $d_name): ?>
                            <option value="<?php echo htmlspecialchars($d_name); ?>"><?php echo htmlspecialchars($d_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px;">วิทยฐานะ</label>
                    <select id="academic_standing" name="academic_standing"
                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                        <option value="">-- ไม่ระบุ --</option>
                        <option value="ครูผู้ช่วย">ครูผู้ช่วย</option>
                        <option value="ชำนาญการ">ชำนาญการ</option>
                        <option value="ชำนาญการพิเศษ">ชำนาญการพิเศษ</option>
                        <option value="เชี่ยวชาญ">เชี่ยวชาญ</option>
                    </select>
                </div>
            </div>

            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeModal()"
                    style="padding: 10px 15px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer; margin-right: 10px;">ยกเลิก</button>
                <button type="submit" class="btn-gradient" style="padding: 10px 20px;">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<script>
    // toggleTeacherFields() removed so fields show for all roles

    function openAddModal() {
        document.getElementById('userForm').reset();
        document.getElementById('user_id').value = '';
        document.getElementById('form_action').value = 'create';
        document.getElementById('modalTitle').innerText = 'เพิ่มผู้ใช้งาน';
        document.getElementById('password').required = true;
        document.getElementById('pwd_req').style.display = 'inline';
        document.getElementById('pwd_help').style.display = 'none';
        document.getElementById('userModal').style.display = 'flex';
    }

    function openEditModal(user) {
        document.getElementById('userForm').reset();
        document.getElementById('user_id').value = user.id;
        document.getElementById('form_action').value = 'update';
        document.getElementById('modalTitle').innerText = 'แก้ไขผู้ใช้งาน';

        document.getElementById('name').value = user.name;
        document.getElementById('username').value = user.username;

        document.getElementById('password').required = false;
        document.getElementById('pwd_req').style.display = 'none';
        document.getElementById('pwd_help').style.display = 'block';

        document.getElementById('role').value = user.role;
        document.getElementById('position').value = user.position || '';
        document.getElementById('department').value = user.department || '';
        document.getElementById('academic_standing').value = user.academic_standing || '';

        document.getElementById('userModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('userModal').style.display = 'none';
    }

    document.getElementById('userForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        Swal.fire({
            title: 'กำลังบันทึกข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('user_action.php', {
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

    function deleteUser(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "คุณต้องการลบผู้ใช้งานนี้ใช่หรือไม่ ข้อมูลที่เกี่ยวข้องจะถูกลบไปด้วย",
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
                formData.append('user_id', id);

                fetch('user_action.php', {
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

    // --- Excel Import Logic ---
    document.getElementById('excelFile').addEventListener('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var data = e.target.result;
                var workbook = XLSX.read(data, { type: 'binary' });
                var firstSheet = workbook.SheetNames[0];

                var rawData = XLSX.utils.sheet_to_json(workbook.Sheets[firstSheet], { header: 1, blankrows: false });

                if (rawData.length === 0) {
                    Swal.fire('ข้อผิดพลาด', 'ไม่พบข้อมูลในไฟล์ Excel', 'error');
                    return;
                }

                var excelData = [];

                // เช็คจำนวนคอลัมน์สูงสุดใน 5 แถวแรก
                var maxCols = 0;
                for (var i = 0; i < Math.min(5, rawData.length); i++) {
                    if (rawData[i].length > maxCols) maxCols = rawData[i].length;
                }

                if (maxCols === 1) {
                    // กรณีมีคอลัมน์เดียว จะถือว่าเป็นรายชื่อทั้งหมด
                    var headerStr = String(rawData[0][0]).toLowerCase().trim();
                    // ตรวจสอบว่าบรรทัดแรกเป็น Header หรือไม่
                    var isHeader = (headerStr === 'name' || headerStr.includes('ชื่อ') || headerStr.includes('รายชื่อ'));
                    var startIndex = isHeader ? 1 : 0;

                    for (var i = startIndex; i < rawData.length; i++) {
                        if (rawData[i] && rawData[i].length > 0 && String(rawData[i][0]).trim() !== '') {
                            excelData.push({ 'name': String(rawData[i][0]).trim() });
                        }
                    }
                } else {
                    // มีหลายคอลัมน์ ให้ใช้การอ่านแบบมี Header
                    excelData = XLSX.utils.sheet_to_json(workbook.Sheets[firstSheet]);
                }

                if (excelData.length === 0) {
                    Swal.fire('ข้อผิดพลาด', 'ไม่พบข้อมูลที่สามารถนำเข้าได้', 'error');
                    return;
                }

                Swal.fire({
                    title: 'กำลังนำเข้าข้อมูล...',
                    text: `พบข้อมูลจำนวน ${excelData.length} รายการ`,
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                const formData = new FormData();
                formData.append('action', 'import_excel');
                formData.append('users_data', JSON.stringify(excelData));

                fetch('user_action.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'นำเข้าสำเร็จ!',
                                html: `นำเข้าผู้ใช้ใหม่: <strong>${result.imported}</strong> รายการ<br>ข้อผิดพลาด/ข้อมูลซ้ำ: <strong>${result.errors}</strong> รายการ`,
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('ข้อผิดพลาด', result.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้', 'error');
                    });
            } catch (err) {
                console.error(err);
                Swal.fire('ข้อผิดพลาด', 'รูปแบบไฟล์ไม่ถูกต้อง หรือไม่สามารถอ่านไฟล์ได้', 'error');
            }
        };
        reader.readAsBinaryString(file);
        this.value = ''; // clear input
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>