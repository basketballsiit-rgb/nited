<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Get available academic years for filter
$stmt = $pdo->query("SELECT * FROM academic_years ORDER BY year DESC, term DESC");
$years = $stmt->fetchAll();

$selected_year_id = isset($_GET['year_id']) ? intval($_GET['year_id']) : 0;
if ($selected_year_id === 0 && !empty($years)) {
    // defaults to active year or first available
    $active = array_filter($years, fn($y) => $y['is_active'] == 1);
    $selected_year_id = !empty($active) ? array_values($active)[0]['id'] : $years[0]['id'];
}

// Fetch completed supervisions
$report_data = [];
if ($selected_year_id > 0) {
    $stmt = $pdo->prepare("
        SELECT 
            s.id as supervision_id,
            s.subject_code,
            s.subject_name,
            s.scheduled_date,
            u.id as teacher_id, 
            u.name as teacher_name, 
            u.department,
            su.name as supervisor_name
        FROM supervisions s
        JOIN users u ON s.teacher_id = u.id
        LEFT JOIN users su ON s.supervisor_id = su.id
        WHERE s.academic_year_id = ? AND s.status = 'completed'
        ORDER BY u.department ASC, u.name ASC, s.scheduled_date ASC
    ");
    $stmt->execute([$selected_year_id]);
    $report_data = $stmt->fetchAll();
}

// Group data by department
$grouped_data = [];
foreach ($report_data as $row) {
    $dept = empty($row['department']) ? 'ไม่ระบุสาขาวิชา' : $row['department'];
    if (!isset($grouped_data[$dept])) {
        $grouped_data[$dept] = [];
    }
    $grouped_data[$dept][] = $row;
}
?>

<div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-file-pdf"></i> รายงานผลการนิเทศ (รายบุคคล)</h2>
        <p class="text-muted">ตรวจสอบและดาวน์โหลดผลการนิเทศของครูแต่ละท่าน แยกตามสาขาวิชา</p>
    </div>

    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
        <label for="year_id" style="font-weight: bold;">เลือกปีการศึกษา:</label>
        <select name="year_id" id="year_id" class="form-control" style="width: 200px;" onchange="this.form.submit()">
            <?php foreach ($years as $y): ?>
                <option value="<?php echo $y['id']; ?>" <?php echo ($selected_year_id == $y['id']) ? 'selected' : ''; ?>>
                    <?php echo $y['term'] . '/' . $y['year']; ?>
                    <?php echo ($y['is_active']) ? '(ปัจจุบัน)' : ''; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (empty($grouped_data)): ?>
    <div class="content-card" style="text-align: center; padding: 40px; color: #888;">
        <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
        <h3>ไม่พบข้อมูลผลการนิเทศ</h3>
        <p>ยังไม่มีการประเมินที่เสร็จสิ้นในปีการศึกษานี้</p>
    </div>
<?php else: ?>
    <?php foreach ($grouped_data as $dept => $supervisions): ?>
        <div class="content-card" style="margin-bottom: 30px;">
            <h3 style="border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 20px; color: #333;">
                <i class="fas fa-building" style="color: #4f46e5;"></i> สาขาวิชา: <?php echo htmlspecialchars($dept); ?>
                <span style="font-size: 14px; font-weight: normal; color: #666; float: right; margin-top: 5px;">
                    จำนวน <?php echo count($supervisions); ?> รายการ
                </span>
            </h3>
            
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th style="padding: 12px; border-bottom: 2px solid #ddd; width: 5%;">ลำดับ</th>
                        <th style="padding: 12px; border-bottom: 2px solid #ddd; width: 25%;">ชื่อ-สกุล (ครูผู้สอน)</th>
                        <th style="padding: 12px; border-bottom: 2px solid #ddd; width: 30%;">วิชาที่รับการนิเทศ</th>
                        <th style="padding: 12px; border-bottom: 2px solid #ddd; width: 20%;">กรรมการผู้นิเทศ</th>
                        <th style="padding: 12px; border-bottom: 2px solid #ddd; width: 10%;">วันที่นิเทศ</th>
                        <th style="padding: 12px; border-bottom: 2px solid #ddd; width: 10%; text-align: center;">ผลการประเมิน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($supervisions as $idx => $row): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;"><?php echo $idx + 1; ?></td>
                            <td style="padding: 12px; font-weight: bold;"><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                            <td style="padding: 12px;">
                                <?php echo htmlspecialchars($row['subject_code']); ?> 
                                <?php echo htmlspecialchars($row['subject_name']); ?>
                            </td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($row['supervisor_name']); ?></td>
                            <td style="padding: 12px;"><?php echo date('d/m/Y', strtotime($row['scheduled_date'])); ?></td>
                            <td style="padding: 12px; text-align: center;">
                                <a href="/nited/teacher/export_observation_pdf.php?id=<?php echo $row['supervision_id']; ?>" 
                                   target="_blank" 
                                   class="btn-gradient" 
                                   style="padding: 5px 10px; font-size: 12px; text-decoration: none; display: inline-block;">
                                   <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
