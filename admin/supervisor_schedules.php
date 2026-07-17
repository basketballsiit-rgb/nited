<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Get academic years for dropdown
$stmt = $pdo->query("SELECT * FROM academic_years ORDER BY is_active DESC, year DESC, term DESC");
$years = $stmt->fetchAll();

// Determine selected year
$selected_year_id = $_GET['year_id'] ?? null;
if (!$selected_year_id && count($years) > 0) {
    foreach ($years as $y) {
        if ($y['is_active']) {
            $selected_year_id = $y['id'];
            break;
        }
    }
    if (!$selected_year_id) $selected_year_id = $years[0]['id'];
}

// Get supervisors and executives
$stmt = $pdo->prepare("SELECT id, name, role, position, academic_standing FROM users WHERE role IN ('supervisor', 'executive') ORDER BY role, name");
$stmt->execute();
$supervisors = $stmt->fetchAll();

// Get supervisions for the selected year
$supervisions = [];
if ($selected_year_id) {
    $stmt = $pdo->prepare("
        SELECT s.*, u.name as teacher_name 
        FROM supervisions s 
        JOIN users u ON s.teacher_id = u.id 
        WHERE s.academic_year_id = ? AND s.status IN ('approved', 'completed')
        ORDER BY s.scheduled_date ASC
    ");
    $stmt->execute([$selected_year_id]);
    $supervisions_data = $stmt->fetchAll();
    
    // Group by supervisor_id
    foreach ($supervisions_data as $row) {
        $supervisions[$row['supervisor_id']][] = $row;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-calendar-day"></i> ตารางการนิเทศรายบุคคล</h2>
        <p class="text-muted">ตรวจสอบและพิมพ์ตารางการนิเทศของคณะกรรมการแต่ละท่าน สำหรับจัดทำคำสั่ง</p>
    </div>
</div>

<div class="content-card" style="margin-bottom: 20px;">
    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
        <label for="year_id" style="font-weight: bold;">เลือกภาคเรียน/ปีการศึกษา:</label>
        <select name="year_id" id="year_id" class="form-control" style="width: 250px;" onchange="this.form.submit()">
            <?php foreach ($years as $y): ?>
                <option value="<?php echo $y['id']; ?>" <?php echo $selected_year_id == $y['id'] ? 'selected' : ''; ?>>
                    ภาคเรียนที่ <?php echo htmlspecialchars($y['term']); ?>/<?php echo htmlspecialchars($y['year']); ?>
                    <?php echo $y['is_active'] ? '(ปัจจุบัน)' : ''; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php foreach ($supervisors as $sup): ?>
    <?php 
    $sups_list = $supervisions[$sup['id']] ?? []; 
    $role_name = ($sup['role'] == 'executive') ? 'ผู้บริหาร' : 'กรรมการนิเทศ';
    ?>
    <div class="content-card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
            <div>
                <h3 style="margin: 0; color: #2c3e50;">
                    <i class="fas fa-user-tie" style="color: #3498db; margin-right: 10px;"></i>
                    <?php echo htmlspecialchars($sup['name']); ?> 
                    <span style="font-size: 14px; font-weight: normal; color: #7f8c8d; background: #ecf0f1; padding: 3px 8px; border-radius: 12px; margin-left: 10px;">
                        <?php echo $role_name; ?>
                    </span>
                </h3>
                <p style="margin: 5px 0 0 35px; color: #7f8c8d; font-size: 14px;">
                    ตำแหน่ง: <?php echo htmlspecialchars($sup['position'] ?? '-'); ?>
                    <?php if (!empty($sup['academic_standing'])) echo " วิทยฐานะ: " . htmlspecialchars($sup['academic_standing']); ?>
                </p>
            </div>
            
            <?php if (count($sups_list) > 0): ?>
                <a href="export_supervisor_schedule_pdf.php?supervisor_id=<?php echo $sup['id']; ?>&year_id=<?php echo $selected_year_id; ?>" target="_blank" class="btn btn-sm" style="background-color: #e74c3c; color: white; border: none; padding: 8px 15px;">
                    <i class="fas fa-file-pdf"></i> พิมพ์ตาราง (PDF)
                </a>
            <?php else: ?>
                <button class="btn btn-sm" style="background-color: #bdc3c7; color: white; border: none; padding: 8px 15px; cursor: not-allowed;" disabled>
                    <i class="fas fa-file-pdf"></i> ไม่มีตาราง
                </button>
            <?php endif; ?>
        </div>

        <?php
            $normal_list = [];
            $urgent_list = [];
            foreach ($sups_list as $row) {
                if (isset($row['is_urgent']) && $row['is_urgent'] == 1) {
                    $urgent_list[] = $row;
                } else {
                    $normal_list[] = $row;
                }
            }

            if (!function_exists('renderScheduleAdminHtml')) {
                function renderScheduleAdminHtml($title, $list) {
                    if (count($list) == 0) return '';
                    $html = '<h5 style="margin-top: 15px; margin-bottom: 10px; color: #34495e;">' . $title . '</h5>';
                    $html .= '<div class="table-responsive"><table><thead><tr>';
                    $html .= '<th style="width: 5%; text-align: center;">ลำดับ</th>';
                    $html .= '<th style="width: 15%;">วัน/เดือน/ปี</th>';
                    $html .= '<th style="width: 15%;">เวลา</th>';
                    $html .= '<th style="width: 25%;">ครูผู้สอน/ผู้รับการนิเทศ</th>';
                    $html .= '<th style="width: 25%;">รหัส-ชื่อรายวิชา</th>';
                    $html .= '<th style="width: 15%;">ระดับชั้น</th>';
                    $html .= '</tr></thead><tbody>';

                    $thai_months = [
                        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
                    ];

                    foreach ($list as $index => $row) {
                        $dateObj = new DateTime($row['scheduled_date']);
                        $d = $dateObj->format('j');
                        $m = $thai_months[(int)$dateObj->format('n')];
                        $y = (int)$dateObj->format('Y') + 543;
                        $formatted_date = "$d $m $y";
                        
                        $html .= '<tr>';
                        $html .= '<td style="text-align: center;">' . ($index + 1) . '</td>';
                        $html .= '<td>' . $formatted_date . '</td>';
                        $html .= '<td>' . date('H:i', strtotime($row['scheduled_date'])) . ' - ' . date('H:i', strtotime($row['end_time'])) . ' น.</td>';
                        $html .= '<td>' . htmlspecialchars($row['teacher_name']) . '</td>';
                        $html .= '<td>' . htmlspecialchars($row['subject_code'] . ' ' . $row['subject_name']) . '</td>';
                        $html .= '<td>' . htmlspecialchars($row['level']) . '</td>';
                        $html .= '</tr>';
                    }
                    $html .= '</tbody></table></div>';
                    return $html;
                }
            }
        ?>

        <?php if (count($sups_list) > 0): ?>
            <?php 
                if (count($normal_list) > 0) {
                    echo renderScheduleAdminHtml('1. การนิเทศตามตารางนัดหมาย', $normal_list);
                } else {
                    echo '<p style="color: #7f8c8d; font-style: italic;">ไม่มีการนิเทศตามตารางนัดหมาย</p>';
                }
                if (count($urgent_list) > 0) {
                    echo renderScheduleAdminHtml('2. การนิเทศแบบเร่งด่วน', $urgent_list);
                }
            ?>
        <?php else: ?>
            <p style="text-align: center; color: #95a5a6; padding: 20px 0;">ไม่มีตารางการนิเทศในภาคเรียนนี้</p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
