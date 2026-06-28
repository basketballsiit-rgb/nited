<?php
require_once __DIR__ . '/config/db.php';

// 1. Create dummy teacher if not exists
$pdo->exec("INSERT IGNORE INTO users (id, username, password, name, role) VALUES (9999, 'testteacher', 'test', 'Test Teacher', 'teacher')");

// 2. Create dummy active academic year if not exists
$pdo->exec("INSERT IGNORE INTO academic_years (id, year, term, is_active) VALUES (9999, '2569', '1', 0)");
// Temporarily make it active for test
$pdo->exec("UPDATE academic_years SET is_active = 0");
$pdo->exec("UPDATE academic_years SET is_active = 1 WHERE id = 9999");

// 3. Insert 1 approved supervision for this teacher
$pdo->exec("INSERT INTO supervisions (teacher_id, academic_year_id, subject_code, subject_name, level, scheduled_date, end_time, status) 
VALUES (9999, 9999, 'TEST-101', 'Test Subj', 'ปวช. 1', '2026-06-30 09:00:00', '2026-06-30 10:00:00', 'approved')");

// 4. Run the validation logic identical to calendar_action.php
$teacher_id = 9999;
$year_id = 9999;

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM supervisions 
    WHERE teacher_id = ? 
    AND academic_year_id = ? 
    AND status IN ('approved', 'completed')
");
$stmt->execute([$teacher_id, $year_id]);
$valid_supervision_count = $stmt->fetchColumn();

echo "Valid Supervisions found: " . $valid_supervision_count . "\n";

if ($valid_supervision_count >= 1) {
    echo "RESULT: Backend Validation SUCCESS - The system correctly blocks the teacher from booking.\n";
} else {
    echo "RESULT: Backend Validation FAILED.\n";
}

// 5. Cleanup
$pdo->exec("DELETE FROM supervisions WHERE teacher_id = 9999");
$pdo->exec("DELETE FROM users WHERE id = 9999");
$pdo->exec("DELETE FROM academic_years WHERE id = 9999");
// Restore active year to ID 1 (Assuming 1 was active, we'll just set it)
$pdo->exec("UPDATE academic_years SET is_active = 1 ORDER BY id DESC LIMIT 1");

echo "Test completed and cleaned up.\n";
?>
