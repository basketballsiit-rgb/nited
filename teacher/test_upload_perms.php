<?php
// Script to debug folder permissions
$dir = __DIR__ . '/../uploads/full_lesson_plans';

echo "<h3>ตรวจสอบสิทธิ์โฟลเดอร์</h3>";
echo "Path: " . $dir . "<br>";

if (!file_exists($dir)) {
    echo "<span style='color:red'>ไม่พบโฟลเดอร์นี้! (Folder does not exist)</span><br>";
} else {
    echo "<span style='color:green'>พบโฟลเดอร์แล้ว</span><br>";
    
    // Check if writable
    if (is_writable($dir)) {
        echo "<span style='color:green'>โฟลเดอร์นี้สามารถเขียนไฟล์ได้ (is_writable = true)</span><br>";
        
        // Try creating a test file
        $test_file = $dir . '/test_' . time() . '.txt';
        if (file_put_contents($test_file, 'test')) {
            echo "<span style='color:green'>ทดสอบสร้างไฟล์สำเร็จ!</span><br>";
            unlink($test_file);
        } else {
            echo "<span style='color:red'>ทดสอบสร้างไฟล์ล้มเหลว! (file_put_contents failed)</span><br>";
            $err = error_get_last();
            echo "Error: " . ($err['message'] ?? 'Unknown Error') . "<br>";
        }
        
    } else {
        echo "<span style='color:red'>โฟลเดอร์นี้ไม่สามารถเขียนไฟล์ได้ (is_writable = false)</span><br>";
        
        // Show owner and permissions
        $perms = fileperms($dir);
        echo "Permissions (Octal): " . substr(sprintf('%o', $perms), -4) . "<br>";
        
        if (function_exists('posix_getpwuid')) {
            $owner = posix_getpwuid(fileowner($dir));
            echo "Owner: " . $owner['name'] . "<br>";
            
            $current_user = posix_getpwuid(posix_geteuid());
            echo "Current PHP User: " . $current_user['name'] . "<br>";
        }
    }
}
?>
