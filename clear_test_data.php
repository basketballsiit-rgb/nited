<?php
// clear_test_data.php
require 'config/db.php';

echo "<h1>ระบบล้างข้อมูลทดสอบ (Clear Test Data)</h1>";

if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<p style='color:red;'><strong>คำเตือน:</strong> การทำงานนี้จะลบข้อมูลการจองนิเทศ, ผลการประเมิน, และการส่งแผนการสอนทั้งหมดทิ้งถาวร!<br>";
    echo "ข้อมูลที่ <strong>จะไม่ถูกลบ</strong> ได้แก่: ข้อมูลบุคลากร (Users), ปีการศึกษา (Academic Years), และหัวข้อเกณฑ์การประเมิน (Criteria)</p>";
    echo "<a href='?confirm=yes' style='display:inline-block; margin-top:20px; padding: 10px 20px; background: #e74c3c; color: white; text-decoration: none; border-radius: 5px;'>ยืนยันการล้างข้อมูลทดสอบ</a>";
    exit;
}

try {
    // ปิดการเช็ค Foreign Key ชั่วคราวเพื่อให้ TRUNCATE ได้
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // ล้างข้อมูลการทำธุรกรรมทั้งหมด
    $pdo->exec("TRUNCATE TABLE supervision_results");
    $pdo->exec("TRUNCATE TABLE supervisions");
    $pdo->exec("TRUNCATE TABLE lesson_plan_results");
    $pdo->exec("TRUNCATE TABLE lesson_plans");

    // เปิดการเช็ค Foreign Key กลับมา
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<h3 style='color:green;'>ล้างข้อมูลการทดสอบเรียบร้อยแล้ว!</h3>";
    echo "<ul>";
    echo "<li>ล้างตาราง <code>supervision_results</code> (ผลการนิเทศ)</li>";
    echo "<li>ล้างตาราง <code>supervisions</code> (การจองนิเทศ)</li>";
    echo "<li>ล้างตาราง <code>lesson_plan_results</code> (ผลการตรวจแผนฯ)</li>";
    echo "<li>ล้างตาราง <code>lesson_plans</code> (ประวัติการส่งแผนฯ)</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<p><strong>คำแนะนำเพิ่มเติม:</strong> หากต้องการประหยัดพื้นที่เซิร์ฟเวอร์ ให้เข้าไปลบไฟล์ทดสอบที่อยู่ในโฟลเดอร์เหล่านี้ด้วยครับ:<br>";
    echo "1. <code>/uploads/lesson_plans/</code><br>";
    echo "2. <code>/uploads/full_lesson_plans/</code></p>";
    
    // ลบไฟล์นี้ทิ้งอัตโนมัติเพื่อความปลอดภัย
    @unlink(__FILE__);
    echo "<p style='color:#7f8c8d; font-size:14px;'><em>* ไฟล์สคริปต์นี้ลบตัวเองออกจากเซิร์ฟเวอร์แล้วเพื่อความปลอดภัย</em></p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
