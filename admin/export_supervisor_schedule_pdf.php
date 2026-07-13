<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}
require_once __DIR__ . '/../config/db.php';

// Check if vendor folder exists
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die("<h3>ข้อผิดพลาด: ไม่พบโฟลเดอร์ vendor</h3><p>คุณลืมอัปโหลดโฟลเดอร์ 'vendor' ขึ้นเซิร์ฟเวอร์ หรือยังไม่ได้รัน 'composer install' ครับ</p>");
}
require_once __DIR__ . '/../vendor/autoload.php';

$supervisor_id = isset($_GET['supervisor_id']) ? intval($_GET['supervisor_id']) : 0;
$year_id = isset($_GET['year_id']) ? intval($_GET['year_id']) : 0;

if (!$supervisor_id || !$year_id) {
    die("ข้อมูลไม่ครบถ้วน (Missing parameters)");
}

// Fetch Supervisor details
$stmt = $pdo->prepare("SELECT id, name, role, position, academic_standing FROM users WHERE id = ?");
$stmt->execute([$supervisor_id]);
$supervisor = $stmt->fetch();

if (!$supervisor) {
    die("ไม่พบข้อมูลกรรมการ");
}

// Fetch Academic Year details
$stmt = $pdo->prepare("SELECT year, term FROM academic_years WHERE id = ?");
$stmt->execute([$year_id]);
$acad_year = $stmt->fetch();

if (!$acad_year) {
    die("ไม่พบข้อมูลปีการศึกษา");
}

// Fetch supervisions (only approved or completed)
$stmt = $pdo->prepare("
    SELECT s.*, u.name as teacher_name 
    FROM supervisions s 
    JOIN users u ON s.teacher_id = u.id 
    WHERE s.academic_year_id = ? AND s.supervisor_id = ? AND s.status IN ('approved', 'completed')
    ORDER BY s.scheduled_date ASC
");
$stmt->execute([$year_id, $supervisor_id]);
$schedules = $stmt->fetchAll();

$term = htmlspecialchars($acad_year['term']);
$year = htmlspecialchars($acad_year['year']);
$sup_name = htmlspecialchars($supervisor['name']);
$sup_position = htmlspecialchars($supervisor['position'] ?? '-');
$sup_standing = htmlspecialchars($supervisor['academic_standing'] ?? '');

$role_title = ($supervisor['role'] == 'executive') ? 'ผู้บริหาร' : 'กรรมการนิเทศ';

$html = '
<style>
    body {
        font-family: "sarabun", sans-serif;
        font-size: 11pt;
        color: #000;
        line-height: 1.4;
    }
    .header {
        text-align: center;
        font-size: 16pt;
        font-weight: bold;
        margin-bottom: 20px;
    }
    .sub-header {
        text-align: center;
        font-size: 14pt;
        margin-bottom: 30px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    th, td {
        border: 1px solid #000;
        padding: 12px 5px;
        vertical-align: middle;
        white-space: nowrap;
    }
    th {
        font-weight: bold;
        text-align: center;
        background-color: #f2f2f2;
    }
    .text-center {
        text-align: center;
    }
    .signature-area {
        margin-top: 50px;
        width: 100%;
    }
    .sig-box {
        text-align: center;
        font-size: 11pt;
        line-height: 1.5;
    }
</style>
';

$html .= '<div class="header">ตารางการนิเทศการจัดการเรียนการสอน (รายบุคคล)</div>';
$html .= '<div class="sub-header">';
$html .= "ประจำภาคเรียนที่ {$term} ปีการศึกษา {$year}<br>";
$html .= "ชื่อ-สกุล: {$sup_name} ({$role_title})";
if ($sup_position !== '-') {
    $html .= "<br>ตำแหน่ง: {$sup_position}";
    if ($sup_standing) $html .= " วิทยฐานะ: {$sup_standing}";
}
$html .= '</div>';

$html .= '<table>';
$html .= '<thead><tr>';
$html .= '<th style="width: 10%;">ลำดับ</th>';
$html .= '<th style="width: 18%;">วัน/เดือน/ปี</th>';
$html .= '<th style="width: 15%;">เวลา</th>';
$html .= '<th style="width: 22%;">ครูผู้รับการนิเทศ</th>';
$html .= '<th style="width: 23%;">รหัส-ชื่อรายวิชา</th>';
$html .= '<th style="width: 12%;">ระดับชั้น</th>';
$html .= '</tr></thead>';
$html .= '<tbody>';

if (count($schedules) > 0) {
    $thai_months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];

    foreach ($schedules as $index => $row) {
        $dateObj = new DateTime($row['scheduled_date']);
        $d = $dateObj->format('j');
        $m = $thai_months[(int)$dateObj->format('n')];
        $y = (int)$dateObj->format('Y') + 543;
        $formatted_date = "$d $m $y";
        
        $start_time = date('H:i', strtotime($row['scheduled_date']));
        $end_time = date('H:i', strtotime($row['end_time']));
        
        $html .= '<tr>';
        $html .= '<td class="text-center">' . ($index + 1) . '</td>';
        $html .= '<td class="text-center">' . $formatted_date . '</td>';
        $html .= '<td class="text-center">' . $start_time . ' - ' . $end_time . ' น.</td>';
        $html .= '<td>' . htmlspecialchars($row['teacher_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['subject_code'] . ' ' . $row['subject_name']) . '</td>';
        $html .= '<td class="text-center">' . htmlspecialchars($row['level']) . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="6" class="text-center">ไม่มีตารางการนิเทศ</td></tr>';
}

$html .= '</tbody></table>';

// Signatures
$html .= '<table class="signature-area" style="border: none;"><tr>';

// Left: Supervisor receiving the schedule
$html .= '<td style="width: 50%; border: none;" class="sig-box">';
$html .= '<table style="margin: 0 auto; border: none; width: 90%;">';
$html .= '<tr>';
$html .= '<td style="width: 25%; text-align: right; border: none; padding-right: 5px;">(ลงชื่อ)</td>';
$html .= '<td style="width: 75%; text-align: left; border: none; padding: 0;">.........................................................</td>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<td style="border: none; padding: 0;"></td>';
$html .= '<td style="text-align: center; border: none; padding: 0; padding-top: 5px;">(' . $sup_name . ')<br>กรรมการนิเทศ/ผู้ประเมิน</td>';
$html .= '</tr>';
$html .= '</table>';
$html .= '</td>';

// Right: Head of Academic or similar
$html .= '<td style="width: 50%; border: none;" class="sig-box">';
$html .= '<table style="margin: 0 auto; border: none; width: 90%;">';
$html .= '<tr>';
$html .= '<td style="width: 25%; text-align: right; border: none; padding-right: 5px;">(ลงชื่อ)</td>';
$html .= '<td style="width: 75%; text-align: left; border: none; padding: 0;">.........................................................</td>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<td style="border: none; padding: 0;"></td>';
$html .= '<td style="text-align: center; border: none; padding: 0; padding-top: 5px;">(.........................................................)<br>หัวหน้างาน/ฝ่ายวิชาการ</td>';
$html .= '</tr>';
$html .= '</table>';
$html .= '</td>';

$html .= '</tr></table>';

try {
    // Config mPDF custom fonts
    $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];
    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new \Mpdf\Mpdf([
        'fontDir' => array_merge($fontDirs, [
            __DIR__ . '/../fonts',
        ]),
        'fontdata' => $fontData + [
            'sarabun' => [
                'R' => 'THSarabunNew.ttf',
                'B' => 'THSarabunNew-Bold.ttf',
                'I' => 'THSarabunNew-Italic.ttf',
                'BI' => 'THSarabunNew-BoldItalic.ttf',
            ]
        ],
        'default_font' => 'sarabun',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 20,
        'margin_bottom' => 20,
        'tempDir' => __DIR__ . '/../uploads'
    ]);

    $mpdf->SetTitle('Schedule - ' . $sup_name);
    $mpdf->WriteHTML($html);
    $mpdf->Output('Schedule_' . $supervisor_id . '_' . date('Ymd') . '.pdf', 'I'); 
} catch (Exception $e) {
    die("<h3>เกิดข้อผิดพลาดในการสร้าง PDF:</h3><pre>" . $e->getMessage() . "</pre>");
}
?>
