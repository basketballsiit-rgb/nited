<?php
session_start();
if (isset($_SESSION['user_id'])) {
    require_once 'includes/auth.php';
    redirectBasedOnRole();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบนิเทศการจัดการเรียนการสอน</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="login-page">

    <div class="login-card">
        <img src="assets/images/logo.png" alt="Logo"
            style="width: 120px; height: 120px; object-fit: contain; margin-bottom: 15px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
        <h2>ระบบนิเทศการสอน</h2>

        <form id="loginForm">
            <div class="form-group">
                <div style="position: relative;">
                    <i class="fas fa-user" style="position: absolute; left: 15px; top: 15px; color: #888;"></i>
                    <input type="text" id="username" class="form-control" placeholder="ชื่อผู้ใช้งาน"
                        style="padding-left: 40px;" required>
                </div>
            </div>
            <div class="form-group">
                <div style="position: relative;">
                    <i class="fas fa-lock" style="position: absolute; left: 15px; top: 15px; color: #888;"></i>
                    <input type="password" id="password" class="form-control" placeholder="รหัสผ่าน"
                        style="padding-left: 40px;" required>
                </div>
            </div>
            <button type="submit" class="btn-gradient" style="width: 100%;"><i class="fas fa-sign-in-alt"></i>
                เข้าสู่ระบบ</button>
                
            <div style="text-align: center; margin: 20px 0; position: relative;">
                <hr style="border: 0; border-top: 1px solid #ddd;">
                <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: white; padding: 0 10px; color: #888; font-size: 14px;">หรือ</span>
            </div>
            
            <a href="keycloak_login.php" class="btn-secondary" style="width: 100%; text-align: center; display: block; background-color: #f1f3f5; color: #333; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: bold; transition: all 0.3s; border: 1px solid #ddd;">
                <i class="fas fa-envelope" style="color: #e74c3c;"></i> เข้าสู่ระบบด้วยอีเมลสถานศึกษา
            </a>
        </form>
    </div>

    <?php
    if (isset($_GET['error'])) {
        $error_msg = htmlspecialchars($_GET['error']);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: '{$error_msg}'
                });
            });
        </script>";
    }
    ?>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);

            Swal.fire({
                title: 'กำลังตรวจสอบ...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('login_action.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'เข้าสู่ระบบสำเร็จ',
                            text: 'กำลังพาท่านเข้าสู่ระบบ...',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = data.redirect;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เข้าสู่ระบบล้มเหลว',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้'
                    });
                });
        });
    </script>
</body>

</html>