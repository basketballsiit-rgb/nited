<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตรวจสอบค่าการอัปโหลดไฟล์</title>
    <style>
        body { font-family: Tahoma, sans-serif; padding: 20px; background: #f4f6f9; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        h2 { color: #333; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; width: 40%; }
        .highlight { color: #d9534f; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h2>ข้อมูลการตั้งค่าอัปโหลดไฟล์ของเซิร์ฟเวอร์</h2>
        <p>ค่าเหล่านี้ถูกตั้งมาจากไฟล์ <code>php.ini</code> บนเซิร์ฟเวอร์ (service.npc.ac.th)</p>
        
        <table>
            <tr>
                <th>ขนาดไฟล์สูงสุดที่อัปโหลดได้ต่อไฟล์<br><small>(upload_max_filesize)</small></th>
                <td class="highlight"><?php echo ini_get('upload_max_filesize'); ?></td>
            </tr>
            <tr>
                <th>ขนาดข้อมูลรวมสูงสุดที่ส่งมาได้<br><small>(post_max_size)</small></th>
                <td class="highlight"><?php echo ini_get('post_max_size'); ?></td>
            </tr>
            <tr>
                <th>จำนวนไฟล์สูงสุดต่อการอัปโหลด 1 ครั้ง<br><small>(max_file_uploads)</small></th>
                <td><?php echo ini_get('max_file_uploads'); ?> ไฟล์</td>
            </tr>
            <tr>
                <th>ระยะเวลาประมวลผลสูงสุด<br><small>(max_execution_time)</small></th>
                <td><?php echo ini_get('max_execution_time'); ?> วินาที</td>
            </tr>
        </table>
        
        <div style="margin-top: 20px; font-size: 14px; color: #555;">
            <strong>วิธีแก้ไข (หากต้องการให้ไฟล์ใหญ่ขึ้น):</strong><br>
            1. เข้าไปที่เครื่องเซิร์ฟเวอร์ หาไฟล์ <code>php.ini</code> (มักอยู่ในโฟลเดอร์ xampp/php หรือ etc/php)<br>
            2. ค้นหาคำว่า <code>upload_max_filesize</code> และเปลี่ยนค่า (เช่น เป็น 20M)<br>
            3. ค้นหาคำว่า <code>post_max_size</code> และเปลี่ยนค่า (เช่น เป็น 25M) <br>
            <i>* แนะนำให้ post_max_size มีค่ามากกว่า upload_max_filesize เสมอ</i><br>
            4. <b>Restart Apache หรือ Web Server</b> เพื่อให้การตั้งค่ามีผล
        </div>
        
        <br>
        <a href="/nited/admin/index.php" style="display: inline-block; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">กลับหน้าแรก</a>
    </div>
</body>
</html>
