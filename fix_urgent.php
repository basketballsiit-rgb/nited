<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_urgent'])) {
    $urgent_ids = $_POST['is_urgent'] ?? [];
    
    // First reset all to 0
    $pdo->exec("UPDATE supervisions SET is_urgent = 0");
    
    // Then set selected to 1
    if (count($urgent_ids) > 0) {
        $placeholders = str_repeat('?,', count($urgent_ids) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE supervisions SET is_urgent = 1 WHERE id IN ($placeholders)");
        $stmt->execute($urgent_ids);
    }
    
    echo "<div style='color: green; font-weight: bold; padding: 20px;'>อัปเดตข้อมูลสำเร็จ! (Updated successfully) <a href='admin/supervisor_schedules.php'>กลับไปดูตาราง</a></div>";
    exit;
}

// Fetch all supervisions
$stmt = $pdo->query("
    SELECT s.*, u.name as teacher_name, sup.name as sup_name
    FROM supervisions s 
    JOIN users u ON s.teacher_id = u.id 
    JOIN users sup ON s.supervisor_id = sup.id
    ORDER BY s.scheduled_date DESC
");
$supervisions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Urgent Supervisions</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Tahoma, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #eee; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; font-size: 16px; margin-top: 20px; }
    </style>
</head>
<body>
    <h2>เลือกรายการที่เป็น "การนิเทศแบบเร่งด่วน"</h2>
    <p>ติ๊กถูกหน้ารายการที่เป็นการนิเทศแบบเร่งด่วน (ที่รองฝ่ายฯ ประเมินให้ทันที) แล้วกดบันทึกด้านล่าง</p>
    <form method="POST">
        <table>
            <tr>
                <th>เร่งด่วน?</th>
                <th>วันที่เวลา</th>
                <th>ครูผู้สอน</th>
                <th>ผู้ประเมิน</th>
                <th>วิชา</th>
            </tr>
            <?php foreach ($supervisions as $s): ?>
            <tr>
                <td style="text-align: center;">
                    <input type="checkbox" name="is_urgent[]" value="<?= $s['id'] ?>" <?= $s['is_urgent'] ? 'checked' : '' ?>>
                </td>
                <td><?= $s['scheduled_date'] ?></td>
                <td><?= $s['teacher_name'] ?></td>
                <td><?= $s['sup_name'] ?></td>
                <td><?= $s['subject_name'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button type="submit" name="update_urgent" class="btn">บันทึกการแก้ไข</button>
    </form>
</body>
</html>
