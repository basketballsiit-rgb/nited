<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Fetch Academic Years
$stmt = $pdo->query("SELECT * FROM academic_years ORDER BY year DESC, term DESC");
$years = $stmt->fetchAll();
?>

<div class="content-header"
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-calendar-alt"></i> จัดการปีการศึกษา</h2>
        <p class="text-muted">เพิ่มและกำหนดปีการศึกษา/ภาคเรียนปัจจุบัน</p>
    </div>
    <button class="btn-gradient" onclick="openYearModal()"><i class="fas fa-plus"></i> เพิ่มปีการศึกษา</button>
</div>

<div class="content-card">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background-color: #f8f9fa; border-bottom: 2px solid #ddd;">
                <th style="padding: 12px; border-bottom: 1px solid #ddd;">ปีการศึกษา</th>
                <th style="padding: 12px; border-bottom: 1px solid #ddd;">ภาคเรียน</th>
                <th style="padding: 12px; border-bottom: 1px solid #ddd; text-align: center;">สถานะ</th>
                <th style="padding: 12px; border-bottom: 1px solid #ddd; text-align: center;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($years as $y): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">
                        <?php echo htmlspecialchars($y['year']); ?>
                    </td>
                    <td style="padding: 12px;">
                        <?php echo htmlspecialchars($y['term']); ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <?php if ($y['is_active']): ?>
                            <span
                                style="background-color: #198754; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">ใช้งานปัจจุบัน</span>
                        <?php else: ?>
                            <button onclick="setActiveYear(<?php echo $y['id']; ?>)"
                                style="background: #f8f9fa; border: 1px solid #ddd; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px;">ตั้งเป็นปีปัจจุบัน</button>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <button onclick="deleteYear(<?php echo $y['id']; ?>)"
                            style="background: none; border: none; color: #dc3545; cursor: pointer;" title="ลบ"><i
                                class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($years)): ?>
                <tr>
                    <td colspan="4" style="padding: 20px; text-align: center;">ไม่มีข้อมูลปีการศึกษา</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Year Modal -->
<div id="yearModal" class="modal-overlay"
    style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1050;">
    <div class="modal-content"
        style="background: white; padding: 25px; border-radius: 10px; width: 100%; max-width: 400px;">
        <h3 style="margin-top: 0; margin-bottom: 20px;">เพิ่มปีการศึกษา</h3>

        <form id="yearForm">
            <input type="hidden" name="action" value="create">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">ปีการศึกษา (พ.ศ.) <span
                        style="color:red">*</span></label>
                <input type="number" id="year" name="year"
                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required
                    placeholder="เช่น 2567">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">ภาคเรียน <span style="color:red">*</span></label>
                <select id="term" name="term"
                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3 (ฤดูร้อน)</option>
                </select>
            </div>

            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeYearModal()"
                    style="padding: 10px 15px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer; margin-right: 10px;">ยกเลิก</button>
                <button type="submit" class="btn-gradient" style="padding: 10px 20px;">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openYearModal() {
        document.getElementById('yearForm').reset();
        document.getElementById('yearModal').style.display = 'flex';
    }

    function closeYearModal() {
        document.getElementById('yearModal').style.display = 'none';
    }

    document.getElementById('yearForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('academic_year_action.php', {
            method: 'POST', body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'สำเร็จ!', timer: 1500, showConfirmButton: false }).then(() => location.reload());
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
    });

    function setActiveYear(id) {
        const formData = new FormData();
        formData.append('action', 'set_active');
        formData.append('id', id);

        fetch('academic_year_action.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                }
            });
    }

    function deleteYear(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('academic_year_action.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            location.reload();
                        } else {
                            Swal.fire('ข้อผิดพลาด', data.message, 'error');
                        }
                    });
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>