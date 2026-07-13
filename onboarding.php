<?php
// onboarding.php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['requires_onboarding'])) {
    header('Location: index.php');
    exit;
}
require_once 'config/db.php';

// Fetch departments for dropdown
$stmt_dept = $pdo->query("SELECT name FROM departments ORDER BY name ASC");
$all_departments = $stmt_dept->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กรอกข้อมูลเพิ่มเติม - ระบบนิเทศการจัดการเรียนการสอน</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; font-family: 'Sarabun', sans-serif; }
        .onboarding-card { background: white; padding: 30px 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        select, input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
    </style>
</head>
<body>
    <div class="onboarding-card">
        <h2 style="text-align: center; color: #2c3e50; margin-bottom: 20px;">ข้อมูลบุคลากรเพิ่มเติม</h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">เนื่องจากเป็นการเข้าสู่ระบบด้วยอีเมลครั้งแรก กรุณากรอกข้อมูลเพื่อใช้ในระบบนิเทศให้ครบถ้วน</p>
        
        <form id="onboardingForm">
            <div class="form-group">
                <label for="position">ตำแหน่ง (Position) <span style="color:red">*</span></label>
                <select id="position" class="form-control" required>
                    <option value="">-- เลือกตำแหน่ง --</option>
                    <option value="ข้าราชการครู">ข้าราชการครู</option>
                    <option value="พนักงานราชการ">พนักงานราชการ</option>
                    <option value="ครูพิเศษสอน">ครูพิเศษสอน</option>

                    <option value="ผู้บริหาร">ผู้บริหาร</option>
                </select>
            </div>
            <div class="form-group">
                <label for="academic_standing">วิทยฐานะ (ถ้ามี)</label>
                <select id="academic_standing" class="form-control">
                    <option value="">-- ไม่มีวิทยฐานะ --</option>
                    <option value="ครูชำนาญการ">ครูชำนาญการ</option>
                    <option value="ครูชำนาญการพิเศษ">ครูชำนาญการพิเศษ</option>
                    <option value="ครูเชี่ยวชาญ">ครูเชี่ยวชาญ</option>
                    <option value="ครูเชี่ยวชาญพิเศษ">ครูเชี่ยวชาญพิเศษ</option>
                </select>
            </div>
            <div class="form-group">
                <label for="department">แผนกวิชา / สังกัด <span id="dept_req" style="color:red; display:inline;">*</span></label>
                <select id="department" class="form-control" required>
                    <option value="">-- เลือกแผนกวิชา / สังกัด (ไม่ระบุได้สำหรับผู้บริหาร) --</option>
                    <?php foreach ($all_departments as $d_name): ?>
                        <option value="<?php echo htmlspecialchars($d_name); ?>"><?php echo htmlspecialchars($d_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn-gradient" style="width: 100%; padding: 12px; margin-top: 10px; font-size: 16px; border: none; border-radius: 5px; cursor: pointer;">
                บันทึกข้อมูลและเข้าสู่ระบบ
            </button>
        </form>
    </div>

    <script>
        document.getElementById('position').addEventListener('change', function() {
            const dept = document.getElementById('department');
            const reqMark = document.getElementById('dept_req');
            if (this.value === 'ผู้บริหาร') {
                dept.required = false;
                reqMark.style.display = 'none';
            } else {
                dept.required = true;
                reqMark.style.display = 'inline';
            }
        });

        document.getElementById('onboardingForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const position = document.getElementById('position').value;
            const academic_standing = document.getElementById('academic_standing').value;
            const department = document.getElementById('department').value;

            const formData = new FormData();
            formData.append('position', position);
            formData.append('academic_standing', academic_standing);
            formData.append('department', department);

            Swal.fire({
                title: 'กำลังบันทึกข้อมูล...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('onboarding_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'
                });
            });
        });
    </script>
</body>
</html>
