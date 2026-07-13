<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'executive') {
    die("Unauthorized");
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

$teacher_id = $_SESSION['user_id'];
$lesson_plan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Lesson Plan Info
$stmt = $pdo->prepare("
    SELECT lp.*, u.name as teacher_name, u.department, su.name as reviewer_name, ay.year, ay.term 
    FROM lesson_plans lp 
    LEFT JOIN users u ON lp.teacher_id = u.id 
    LEFT JOIN users su ON lp.reviewer_id = su.id
    LEFT JOIN academic_years ay ON lp.academic_year_id = ay.id
    WHERE lp.id = ? AND lp.status != 'pending'
");
$stmt->execute([$lesson_plan_id]);
$plan = $stmt->fetch();

if (!$plan) {
    die("No evaluated lesson plan found.");
}

// Security
if ($_SESSION['role'] === 'teacher' && $plan['teacher_id'] != $_SESSION['user_id']) {
    die("Unauthorized access to this document.");
}
if ($_SESSION['role'] === 'supervisor' && $plan['reviewer_id'] != $_SESSION['user_id'] && $plan['teacher_id'] != $_SESSION['user_id']) {
    die("Unauthorized access to this document.");
}

// Fetch active Criteria categories and items
$stmt = $pdo->query("SELECT * FROM lp_criteria_categories ORDER BY order_idx ASC, id ASC");
$categories = $stmt->fetchAll();

$items_by_cat = [];
$stmt = $pdo->query("SELECT * FROM lp_criteria_items ORDER BY order_idx ASC, id ASC");
while ($row = $stmt->fetch()) {
    $items_by_cat[$row['category_id']][] = $row;
}

// Fetch existing results
$stmt = $pdo->prepare("SELECT * FROM lesson_plan_results WHERE lesson_plan_id = ?");
$stmt->execute([$lesson_plan_id]);
$res = $stmt->fetchAll();
$results_map = [];
$total_score = 0;
$total_max = 0;
foreach ($res as $r) {
    $results_map[$r['criteria_item_id']] = $r;
    $total_score += $r['score'];
}

// Variables for Header
$subject_code = htmlspecialchars($plan['subject_code'] ?? '-');
$subject_name = htmlspecialchars($plan['subject_name'] ?? '-');
$level = htmlspecialchars($plan['level'] ?? '-');
$department = htmlspecialchars($plan['department'] ?? '-');
$teacher_name = htmlspecialchars($plan['teacher_name'] ?? '-');
$term = htmlspecialchars($plan['term'] ?? '-');
$year = htmlspecialchars($plan['year'] ?? '-');
$reviewer_name = htmlspecialchars($plan['reviewer_name'] ?? '-');
$signature_path_db = $plan['signature_path'] ?? '';
$sig_local = '';
if (!empty($signature_path_db)) {
    $sig_local = __DIR__ . '/../' . $signature_path_db;
}
$review_comment = htmlspecialchars($plan['review_comment'] ?? '');

$html = '
<style>
    body, div, span, p {
        font-family: "sarabun", sans-serif;
        font-size: 14pt;
        color: #000;
        line-height: 1.3;
    }
    table, th, td {
        font-family: "sarabun", sans-serif;
        font-size: 14pt;
        line-height: 1.3;
    }
    .header {
        text-align: center;
        font-size: 14pt;
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
        margin-bottom: 5px;
    }
    th, td {
        border: none;
        padding: 2px 2px;
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
        padding-top: 3px;
        padding-bottom: 0px;
    }
    .signature-area {
        margin-top: 10px;
        width: 100%;
    }
    .sig-box {
        text-align: center;
        font-size: 12pt;
        font-weight: bold;
        line-height: 1.3;
    }
</style>
';

$html .= '<div class="header">รายงานผลการประเมินแผนจัดการเรียนรู้แบบมุ่งเน้นสมรรถนะวิชาชีพ</div>';

$html .= '<div class="paragraph-intro">';
$html .= "รายงานผลการประเมินแผนจัดการเรียนรู้ มุ่งเน้นสมรรถนะวิชาชีพ รายวิชา {$subject_code} {$subject_name} ระดับ {$level} สาขาวิชา {$department} ของ {$teacher_name} ภาคเรียนที่ {$term} ปีการศึกษา {$year} โดยมีรายละเอียดผลการประเมินดังนี้";
$html .= '</div>';

$html .= '<table>';
$html .= '<thead><tr>';
$html .= '<th style="width: 70%;">รายการประเมิน</th>';
$html .= '<th class="text-center" style="width: 15%;">คะแนนสูงสุด</th>';
$html .= '<th class="text-center" style="width: 15%;">คะแนนที่ได้</th>';
$html .= '</tr></thead>';
$html .= '<tbody>';

foreach ($categories as $cat) {
    if (isset($items_by_cat[$cat['id']])) {
        $html .= '<tr><td colspan="3" class="cat-title">' . htmlspecialchars($cat['title']) . '</td></tr>';
        foreach ($items_by_cat[$cat['id']] as $item) {
            $max = $item['max_score'];
            $total_max += $max;
            $score = isset($results_map[$item['id']]) ? floatval($results_map[$item['id']]['score']) : 0;
            
            $raw_desc = $item['description'];
            $raw_desc = preg_replace("/\r|\n/", " ", $raw_desc);
            $desc = htmlspecialchars(trim($raw_desc));
            
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
        }
    }
}

$html .= '<tr>';
$html .= '<td class="text-right" style="font-weight:bold; padding-top:5px;">รวมคะแนนทั้งสิ้น:</td>';
$html .= '<td class="text-center" style="font-weight:bold; padding-top:5px;">' . $total_max . '</td>';
$html .= '<td class="text-center" style="font-weight:bold; padding-top:5px;">' . $total_score . '</td>';
$html .= '</tr>';

$percent = ($total_max > 0) ? round(($total_score / $total_max) * 100, 2) : 0;
$html .= '<tr>';
$html .= '<td class="text-right" style="font-weight:bold;">คิดเป็นร้อยละ:</td>';
$html .= '<td colspan="2" class="text-center" style="font-weight:bold;">' . $percent . '%</td>';
$html .= '</tr>';

$html .= '</tbody></table>';

if (!empty($review_comment)) {
    $html .= '<div style="margin-top:10px;"><strong>ข้อเสนอแนะเพิ่มเติมจากกรรมการ:</strong><br>' . nl2br($review_comment) . '</div>';
}

$status_text = "ผ่านการอนุมัติ";
if ($plan['status'] === 'revision') $status_text = "ส่งกลับไปปรับปรุงแก้ไข";
if ($plan['status'] === 'rejected') $status_text = "ไม่ผ่านการอนุมัติ";

$html .= '<div style="margin-top:10px; font-weight:bold; text-align: center;">สรุปผลการพิจารณา: ' . $status_text . '</div>';

// Signature
$html .= '<table class="signature-area"><tr>';

// Left: Teacher
$html .= '<td style="width: 50%;" class="sig-box">';
$html .= '<br><br>';
$html .= '(ลงชื่อ)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>';
$html .= '(' . $teacher_name . ')<br>';
$html .= 'ผู้ส่งแผนการสอน / ครูผู้สอน';
$html .= '</td>';

// Right: Reviewer
$html .= '<td style="width: 50%;" class="sig-box">';
if (!empty($sig_local) && file_exists($sig_local)) {
    $html .= '<img src="' . $sig_local . '" style="height: 45px; max-width: 200px;"><br>';
} else {
    $html .= '<br><br><br>';
}
$html .= '(ลงชื่อ)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>';
$html .= '(' . $reviewer_name . ')<br>';
$html .= 'ผู้ตรวจ / ผู้ประเมิน';
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
        'margin_left' => 25,
        'margin_right' => 10,
        'margin_top' => 15,
        'margin_bottom' => 15,
        // Set tempDir to our uploads folder which is guaranteed to be 777 writable
        'tempDir' => __DIR__ . '/../uploads'
    ]);

    $mpdf->SetTitle('Lesson Plan Report - ' . $subject_name);
    $mpdf->WriteHTML($html);
    $mpdf->Output('Lesson_Plan_Report_' . date('Ymd_His') . '.pdf', 'I');
} catch (Exception $e) {
    die("<h3>เกิดข้อผิดพลาดในการสร้าง PDF:</h3><pre>" . $e->getMessage() . "</pre>");
}
?>
