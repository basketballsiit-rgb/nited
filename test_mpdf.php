<?php
require_once __DIR__ . '/vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf([
    'default_font' => 'sarabun'
]);

$mpdf->WriteHTML('<h1 style="font-family: sarabun;">ทดสอบฟอนต์ Sarabun</h1><p>นี่คือการทดสอบภาษาไทย</p>');
$mpdf->Output('test_mpdf.pdf', 'F');
echo "PDF created";
?>
