<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/vendor/autoload.php';

// 1. Prepare Dummy Images
$dummy_img_1 = 'uploads/dummy_test_1.jpg';
$dummy_img_2 = 'uploads/dummy_test_2.png';

// Just create a tiny 1x1 image for testing if they don't exist
if (!file_exists(__DIR__ . '/' . $dummy_img_1)) {
    $im = imagecreatetruecolor(200, 200);
    $text_color = imagecolorallocate($im, 233, 14, 91);
    imagestring($im, 5, 5, 5,  'Test Image 1', $text_color);
    imagejpeg($im, __DIR__ . '/' . $dummy_img_1);
    imagedestroy($im);
}
if (!file_exists(__DIR__ . '/' . $dummy_img_2)) {
    $im = imagecreatetruecolor(200, 200);
    $text_color = imagecolorallocate($im, 0, 0, 255);
    imagestring($im, 5, 5, 5,  'Test Image 2', $text_color);
    imagepng($im, __DIR__ . '/' . $dummy_img_2);
    imagedestroy($im);
}

// 2. Insert Dummy Data
$pdo->exec("INSERT IGNORE INTO users (id, username, password, name, role) VALUES (9999, 'test_t', 'x', 'Test Teacher', 'teacher')");
$pdo->exec("INSERT IGNORE INTO users (id, username, password, name, role) VALUES (8888, 'test_s', 'x', 'Test Supervisor', 'supervisor')");
$pdo->exec("INSERT IGNORE INTO academic_years (id, year, term, is_active) VALUES (9999, '2569', '1', 1)");

$pdo->exec("DELETE FROM supervisions WHERE id = 9999");
$pdo->exec("
    INSERT INTO supervisions 
    (id, teacher_id, supervisor_id, academic_year_id, subject_code, subject_name, level, scheduled_date, end_time, status, photo_path, photo_path_2) 
    VALUES 
    (9999, 9999, 8888, 9999, '1234-5678', 'Test E2E PDF', 'ปวส.', '2026-06-30 08:00:00', '2026-06-30 10:00:00', 'completed', '$dummy_img_1', '$dummy_img_2')
");

// 3. Test PDF Generation Code (Simulating what export_observation_pdf.php does)
try {
    $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];
    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new \Mpdf\Mpdf([
        'fontDir' => array_merge($fontDirs, [
            __DIR__ . '/fonts',
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
        'tempDir' => __DIR__ . '/uploads'
    ]);

    $html = '<h1>Test Report</h1>';
    $html .= '<div style="text-align: center; page-break-before: always;">';
    $html .= '<img src="' . __DIR__ . '/' . $dummy_img_1 . '" style="max-height: 380px; max-width: 600px;">';
    $html .= '<br><br>';
    $html .= '<img src="' . __DIR__ . '/' . $dummy_img_2 . '" style="max-height: 380px; max-width: 600px;">';
    $html .= '</div>';
    
    $mpdf->WriteHTML($html);
    $mpdf->Output(__DIR__ . '/uploads/test_output.pdf', 'F');
    echo "PDF_SUCCESS\n";
    
} catch (Exception $e) {
    echo "PDF_ERROR: " . $e->getMessage() . "\n";
}

// 4. Clean up DB
$pdo->exec("DELETE FROM supervisions WHERE id = 9999");
$pdo->exec("DELETE FROM academic_years WHERE id = 9999");
$pdo->exec("DELETE FROM users WHERE id = 9999");
$pdo->exec("DELETE FROM users WHERE id = 8888");

?>
