<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'executive') {
    die("Unauthorized");
}
require_once __DIR__ . '/../config/db.php';

// Check if vendor folder exists
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die("<h3>ข้อผิดพลาด: ไม่พบโฟลเดอร์ vendor</h3><p>คุณลืมอัปโหลดโฟลเดอร์ 'vendor' ขึ้นเซิร์ฟเวอร์ หรือยังไม่ได้รัน 'composer install' ครับ</p>");
}
require_once __DIR__ . '/../vendor/autoload.php';

$teacher_id = $_SESSION['user_id'];
$supervision_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If executive or supervisor is viewing, we allow them if they are evaluating or are admins/exec.
// For simplicity, let's just allow if supervision exists.
$stmt = $pdo->prepare("
    SELECT s.*, u.name as teacher_name, u.department, su.name as supervisor_name, ay.year, ay.term 
    FROM supervisions s 
    LEFT JOIN users u ON s.teacher_id = u.id 
    LEFT JOIN users su ON s.supervisor_id = su.id
    LEFT JOIN academic_years ay ON s.academic_year_id = ay.id
    WHERE s.id = ? AND s.status = 'completed'
");
$stmt->execute([$supervision_id]);
$supervision = $stmt->fetch();

if (!$supervision) {
    die("No completed observation found.");
}

// Security: Check if user is the teacher, supervisor, or executive
if ($_SESSION['role'] === 'teacher' && $supervision['teacher_id'] != $_SESSION['user_id']) {
    die("Unauthorized access to this document.");
}
if ($_SESSION['role'] === 'supervisor' && $supervision['supervisor_id'] != $_SESSION['user_id']) {
    if ($supervision['teacher_id'] != $_SESSION['user_id']) {
         die("Unauthorized access to this document.");
    }
}

// Fetch results
$stmt = $pdo->prepare("
    SELECT r.score, r.comment, i.description, i.max_score, c.title as category_title, c.weight
    FROM supervision_results r
    JOIN criteria_items i ON r.criteria_item_id = i.id
    JOIN criteria_categories c ON i.category_id = c.id
    WHERE r.supervision_id = ?
    ORDER BY c.id ASC, i.id ASC
");
$stmt->execute([$supervision_id]);
$results = $stmt->fetchAll();

$grouped_results = [];
foreach ($results as $r) {
    $cat = $r['category_title'];
    if (!isset($grouped_results[$cat])) {
        $grouped_results[$cat] = [];
    }
    $grouped_results[$cat][] = $r;
}

// Variables for Header
$subject_code = htmlspecialchars($supervision['subject_code'] ?? '-');
$subject_name = htmlspecialchars($supervision['subject_name'] ?? '-');
$level = htmlspecialchars($supervision['level'] ?? '-');
$department = htmlspecialchars($supervision['department'] ?? '-');
$teacher_name = htmlspecialchars($supervision['teacher_name'] ?? '-');
$term = htmlspecialchars($supervision['term'] ?? '-');
$year = htmlspecialchars($supervision['year'] ?? '-');
$date = date('d/m/Y', strtotime($supervision['scheduled_date']));
$start_time = date('H:i', strtotime($supervision['scheduled_date']));
$end_time = date('H:i', strtotime($supervision['end_time']));
$supervisor_name = htmlspecialchars($supervision['supervisor_name'] ?? '-');
$signature_path = $supervision['signature_path'] ? '../' . $supervision['signature_path'] : '';


$html = '
<style>
    body, div, span, p {
        font-family: "sarabun", sans-serif;
        font-size: 16pt;
        color: #000;
        line-height: 1.5;
    }
    table, th, td {
        font-family: "sarabun", sans-serif;
        font-size: 16pt;
        line-height: 1.5;
    }
    .header {
        text-align: center;
        font-size: 13pt;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .paragraph-intro {
        text-align: center;
        font-size: 13pt;
        font-weight: bold;
        margin-bottom: 5px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    th, td {
        border: none;
        padding: 3px 2px;
        vertical-align: top;
    }
    th {
        text-align: left;
        font-weight: bold;
    }
    .text-right {
        text-align: right;
    }
    .text-center {
        text-align: center;
    }
    .cat-title {
        font-weight: bold;
        padding-top: 5px;
        padding-bottom: 0px;
    }
    .signature-area {
        margin-top: 15px;
        width: 100%;
    }
    .sig-box {
        text-align: center;
        font-size: 12pt;
        font-weight: bold;
        line-height: 1.5;
    }
</style>
';

$html .= '<div class="header">รายงานผลการนิเทศการจัดการเรียนการสอน</div>';

$html .= '<div class="paragraph-intro">';
$html .= "รายงานผลการนิเทศการจัดการเรียนการสอน รายวิชา {$subject_code} {$subject_name} ระดับ {$level} สาขาวิชา {$department} ของ {$teacher_name} ภาคเรียนที่ {$term} ปีการศึกษา {$year} วันที่ทำการนิเทศ {$date} เวลา {$start_time} - {$end_time} น. โดยมีรายละเอียดผลการประเมินดังนี้";
$html .= '</div>';


$html .= '<table>';
$html .= '<thead><tr>';
$html .= '<th style="width: 70%;">รายการประเมิน</th>';
$html .= '<th class="text-center" style="width: 15%;">คะแนนสูงสุด</th>';
$html .= '<th class="text-center" style="width: 15%;">คะแนนที่ได้</th>';
$html .= '</tr></thead>';
$html .= '<tbody>';

$total_score = 0;
$total_max = 0;

foreach ($grouped_results as $cat => $items) {
    $html .= '<tr><td colspan="3" class="cat-title">' . htmlspecialchars($cat) . '</td></tr>';
    foreach ($items as $item) {
        $raw_desc = $item['description'];
        $raw_desc = preg_replace("/\r|\n/", " ", $raw_desc);
        $desc = htmlspecialchars(trim($raw_desc));
        
        $max = $item['max_score'];
        $score = $item['score'];
        $total_max += $max;
        $total_score += $score;
        
        $html .= '<tr>';
        $html .= '<td style="padding-left: 10px;">';
        $html .= '<table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">';
        $html .= '<tr>';
        $html .= '<td style="width: 15px; border: none; padding: 0; vertical-align: top;">-</td>';
        $html .= '<td style="border: none; padding: 0; vertical-align: top;">' . $desc . '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</td>';
        $html .= '<td class="text-center">' . $max . '</td>';
        $html .= '<td class="text-center">' . $score . '</td>';
        $html .= '</tr>';
        
        if (!empty($item['comment'])) {
            $html .= '<tr>';
            $html .= '<td colspan="3" style="padding-left: 30px; color: #555; font-size: 14pt;">หมายเหตุ: ' . htmlspecialchars($item['comment']) . '</td>';
            $html .= '</tr>';
        }
    }
}

$html .= '<tr>';
$html .= '<td class="text-right" style="font-weight:bold; padding-top:10px;">รวมคะแนนทั้งสิ้น:</td>';
$html .= '<td class="text-center" style="font-weight:bold; padding-top:10px;">' . $total_max . '</td>';
$html .= '<td class="text-center" style="font-weight:bold; padding-top:10px;">' . $total_score . '</td>';
$html .= '</tr>';

$percent = ($total_max > 0) ? round(($total_score / $total_max) * 100, 2) : 0;
$html .= '<tr>';
$html .= '<td class="text-right" style="font-weight:bold;">คิดเป็นร้อยละ:</td>';
$html .= '<td colspan="2" class="text-center" style="font-weight:bold;">' . $percent . '%</td>';
$html .= '</tr>';

$html .= '</tbody></table>';

// Signature
$html .= '<table class="signature-area"><tr>';

// Left: Teacher
$html .= '<td style="width: 50%;" class="sig-box">';
$html .= '<br><br><br>';
$html .= '(ลงชื่อ)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>';
$html .= '(' . $teacher_name . ')<br>';
$html .= 'ครูผู้สอน / ผู้รับการนิเทศ';
$html .= '</td>';

// Right: Supervisor
$html .= '<td style="width: 50%;" class="sig-box">';
if ($signature_path && file_exists($signature_path)) {
    $html .= '<img src="' . $signature_path . '" style="height: 50px; max-width: 200px;"><br>';
} else {
    $html .= '<br><br><br>';
}
$html .= '(ลงชื่อ)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>';
$html .= '(' . $supervisor_name . ')<br>';
$html .= 'กรรมการนิเทศ';
$html .= '</td>';

$html .= '</tr></table>';

// Photos (if any)
$photo_path_1 = $supervision['photo_path'] ?? '';
$photo_path_2 = $supervision['photo_path_2'] ?? '';

if (!empty($photo_path_1) || !empty($photo_path_2)) {
    $html .= '<div style="text-align: center; page-break-before: always; padding-top: 20px;">';
    $html .= '<div style="font-size: 16pt; font-weight: bold; margin-bottom: 20px;">ภาพถ่ายประกอบการนิเทศ</div>';
    
    if (!empty($photo_path_1)) {
        $p1_local = __DIR__ . '/../' . $photo_path_1;
        if (file_exists($p1_local)) {
            $html .= '<div style="margin-bottom: 30px; text-align: center;">';
            $html .= '<img src="' . $p1_local . '" style="max-height: 380px; max-width: 600px; border: 1px solid #ccc; padding: 5px;">';
            $html .= '</div>';
        } else {
            $html .= '<div style="color:red; font-size:12pt; border:1px dashed red; padding:5px; margin-bottom: 30px;">ไม่พบไฟล์รูปภาพที่ 1</div>';
        }
    }
    
    if (!empty($photo_path_2)) {
        $p2_local = __DIR__ . '/../' . $photo_path_2;
        if (file_exists($p2_local)) {
            $html .= '<div style="margin-bottom: 30px; text-align: center;">';
            $html .= '<img src="' . $p2_local . '" style="max-height: 380px; max-width: 600px; border: 1px solid #ccc; padding: 5px;">';
            $html .= '</div>';
        } else {
            $html .= '<div style="color:red; font-size:12pt; border:1px dashed red; padding:5px; margin-bottom: 30px;">ไม่พบไฟล์รูปภาพที่ 2</div>';
        }
    }
    
    $html .= '</div>';
} else {
    // If DB is empty, tell the user explicitly
    $html .= '<div style="margin-top: 15px; text-align: center; color: red; font-size: 12pt; border: 1px dashed red; padding: 5px; page-break-inside: avoid;">';
    $html .= '<strong>ไม่พบภาพถ่ายในฐานข้อมูล</strong><br>สาเหตุอาจเกิดจากตอนที่กรรมการกด "บันทึกผลการประเมิน" ระบบเซิร์ฟเวอร์ปฏิเสธการอัปโหลดไฟล์รูปภาพ (Permission Denied)';
    $html .= '</div>';
}

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
        'margin_left' => 25,
        'margin_right' => 10,
        'margin_top' => 15,
        'margin_bottom' => 15,
        // Set tempDir to our uploads folder which is guaranteed to be 777 writable
        'tempDir' => __DIR__ . '/../uploads'
    ]);

    $mpdf->SetTitle('Observation Report - ' . $subject_name);
    $mpdf->WriteHTML($html);
    $mpdf->Output('Observation_Report_' . date('Ymd_His') . '.pdf', 'I'); // I = inline browser, D = download
} catch (Exception $e) {
    die("<h3>เกิดข้อผิดพลาดในการสร้าง PDF:</h3><pre>" . $e->getMessage() . "</pre>");
}
?>
