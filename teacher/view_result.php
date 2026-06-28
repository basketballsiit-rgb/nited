<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'supervisor') {
    header("Location: /nited/index.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$teacher_id = $_SESSION['user_id'];
$supervision_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch supervision details
$stmt = $pdo->prepare("
    SELECT s.*, u.name as supervisor_name, ay.year, ay.term 
    FROM supervisions s 
    LEFT JOIN users u ON s.supervisor_id = u.id 
    LEFT JOIN academic_years ay ON s.academic_year_id = ay.id
    WHERE s.id = ? AND s.teacher_id = ? AND s.status = 'completed'
");
$stmt->execute([$supervision_id, $teacher_id]);
$supervision = $stmt->fetch();

if (!$supervision) {
    echo "<div class='content-card'><h3>ไม่พบข้อมูลการประเมิน หรือท่านไม่มีสิทธิ์เข้าถึงข้อมูลนี้</h3><a href='history.php' class='btn-gradient'>กลับไปหน้าประวัติ</a></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch results grouped by category
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
$cat_totals = [];
$total_score_earned = 0;
$total_max_allowed = 0;

foreach ($results as $r) {
    $cat = $r['category_title'];
    if (!isset($grouped_results[$cat])) {
        $grouped_results[$cat] = [];
        $cat_totals[$cat] = ['earned' => 0, 'max' => 0];
    }
    $grouped_results[$cat][] = $r;

    $total_score_earned += $r['score'];
    $total_max_allowed += $r['max_score'];

    $cat_totals[$cat]['earned'] += $r['score'];
    $cat_totals[$cat]['max'] += $r['max_score'];
}

$chart_labels = [];
$chart_data_percent = [];
foreach ($cat_totals as $cat => $totals) {
    $chart_labels[] = $cat;
    $chart_data_percent[] = ($totals['max'] > 0) ? round(($totals['earned'] / $totals['max']) * 100, 2) : 0;
}

$percentage = ($total_max_allowed > 0) ? round(($total_score_earned / $total_max_allowed) * 100, 2) : 0;
?>

<div class="content-header"
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 class="hide-on-print"><i class="fas fa-search"></i> ผลการนิเทศการจัดการเรียนการสอน</h2>
        <p class="text-muted hide-on-print">ปีการศึกษา
            <?php echo $supervision['term'] . '/' . $supervision['year']; ?>
        </p>
    </div>
    <a href="history.php" style="color: #666; text-decoration: none;"><i class="fas fa-arrow-left"></i>
        กลับไปหน้าประวัติ</a>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card bg-orange">
        <h3 style="font-size: 14px;"><i class="fas fa-book"></i> รายวิชา/วันเวลา</h3>
        <p class="value" style="font-size: 18px; margin-top: 5px;">
            <?php echo htmlspecialchars($supervision['subject_name']); ?><br>
            <span style="font-size: 14px; font-weight: normal;">เมื่อ
                <?php echo date('d/m/Y', strtotime($supervision['scheduled_date'])); ?>
            </span>
        </p>
    </div>
    <div class="stat-card bg-blue">
        <h3 style="font-size: 14px;"><i class="fas fa-user-tie"></i> กรรมการผู้นิเทศ</h3>
        <p class="value" style="font-size: 20px; margin-top: 5px;">
            <?php echo htmlspecialchars($supervision['supervisor_name']); ?>
        </p>
    </div>
    <div class="stat-card bg-green">
        <h3 style="font-size: 14px;"><i class="fas fa-chart-line"></i> คะแนนรวม</h3>
        <p class="value" style="font-size: 24px; margin-top: 5px;">
            <?php echo $total_score_earned; ?> /
            <?php echo $total_max_allowed; ?> <small style="font-size: 14px;">(
                <?php echo $percentage; ?>%)
            </small>
        </p>
    </div>
</div>

<!-- Print Only Header -->
<div class="print-header" style="display: none; text-align: center; margin-bottom: 20px;">
    <img src="/nited/assets/images/logo.png" style="width: 80px; margin-bottom: 15px;">
    <h2>รายงานผลการนิเทศการจัดการเรียนการสอน</h2>
    <h3>วิทยาลัยสารพัดช่างน่าน โดย งานพัฒนาหลักสูตรการเรียนการสอน</h3>
    <p>ภาคเรียนที่ <?php echo $supervision['term']; ?> ปีการศึกษา <?php echo $supervision['year']; ?></p>
    <p style="margin-top: 10px;"><strong>รายวิชา:</strong> <?php echo htmlspecialchars($supervision['subject_name']); ?>
    </p>
    <p><strong>ผู้รับการนิเทศ:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?> &nbsp;&nbsp;&nbsp;
        <strong>ผู้นิเทศ:</strong> <?php echo htmlspecialchars($supervision['supervisor_name']); ?>
    </p>
    <p><strong>วันที่:</strong> <?php echo date('d/m/Y', strtotime($supervision['scheduled_date'])); ?>
        &nbsp;&nbsp;&nbsp; <strong>คะแนนรวม:</strong> <?php echo $percentage; ?>%</p>
    <hr style="margin-top: 20px; border: 1px solid #ddd;">
</div>

<div class="content-card" style="margin-bottom: 20px; page-break-inside: avoid;">
    <h3 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">กราฟวิเคราะห์ผลการประเมิน
        (Radar Chart)</h3>
    <div style="width: 100%; max-width: 600px; margin: 0 auto;">
        <canvas id="radarChart"></canvas>
    </div>
</div>

<?php if (!empty($supervision['photo_path']) || !empty($supervision['photo_path_2'])): ?>
    <div class="content-card" style="text-align: center; margin-bottom: 20px;">
        <h3 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">ภาพถ่ายประกอบการนิเทศ</h3>
        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
            <?php if (!empty($supervision['photo_path'])): ?>
                <img src="/nited/<?php echo htmlspecialchars($supervision['photo_path']); ?>" alt="Evaluation Photo 1"
                    style="max-width: 100%; max-height: 400px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); object-fit: contain;">
            <?php endif; ?>
            <?php if (!empty($supervision['photo_path_2'])): ?>
                <img src="/nited/<?php echo htmlspecialchars($supervision['photo_path_2']); ?>" alt="Evaluation Photo 2"
                    style="max-width: 100%; max-height: 400px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); object-fit: contain;">
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="content-card">
    <h3 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">รายละเอียดเกณฑ์การประเมิน (5
        ระดับ)</h3>

    <?php foreach ($grouped_results as $cat_title => $items): ?>
        <div style="margin-bottom: 30px;">
            <h4 style="color: #E94057; margin-bottom: 15px; border-left: 4px solid #F27121; padding-left: 10px;">หมวดหมู่:
                <?php echo htmlspecialchars($cat_title); ?>
            </h4>
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 50%;">รายการประเมิน</th>
                        <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center; width: 15%;">
                            คะแนนที่ได้</th>
                        <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 35%;">ข้อคิดเห็น/ข้อเสนอแนะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $idx => $item): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;">
                                <?php echo ($idx + 1) . '. ' . htmlspecialchars($item['description']); ?>
                            </td>
                            <td style="padding: 10px; text-align: center;">
                                <span
                                    style="background-color: #e9ecef; padding: 4px 10px; border-radius: 20px; font-weight: bold; color: <?php echo ($item['score'] >= 4) ? '#198754' : (($item['score'] == 3) ? '#fd7e14' : '#dc3545'); ?>">
                                    <?php echo $item['score']; ?> /
                                    <?php echo $item['max_score']; ?>
                                </span>
                            </td>
                            <td style="padding: 10px; font-size: 13px; color: #555;">
                                <?php echo !empty($item['comment']) ? htmlspecialchars($item['comment']) : '<span style="color:#ccc;">-</span>'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <?php if (empty($grouped_results)): ?>
        <p style="text-align: center; color: #888; padding: 20px;">ไม่พบรายละเอียดผลคะแนน กรุณาติดต่อผู้ดูแลระบบ</p>
    <?php endif; ?>

    <?php if (!empty($supervision['signature_path'])): ?>
    <div style="background-color: #fff; border: 1px solid #eee; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px; page-break-inside: avoid;">
        <h3 style="margin: 0; margin-bottom: 10px; color: #333; font-size: 18px;"><i class="fas fa-signature"></i> ลายมือชื่อผู้นิเทศ</h3>
        <img src="<?php echo htmlspecialchars($supervision['signature_path']); ?>" alt="ลายเซ็น" style="max-width: 100%; max-height: 120px; border: 1px dashed #ccc; padding: 10px; border-radius: 5px; background: #fafafa; margin-bottom: 10px;">
        <p style="margin: 0; font-size: 16px; color: #555;">(ลงชื่อ) <?php echo htmlspecialchars($supervision['supervisor_name'] ?? ''); ?> (ผู้นิเทศ)</p>
    </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 30px;">
        <a href="export_observation_pdf.php?id=<?php echo $supervision['id']; ?>" target="_blank" class="btn-gradient hide-on-print"
            style="display: inline-block; background: linear-gradient(135deg, #2c3e50, #3498db); text-decoration: none; padding: 10px 20px; color: white; border-radius: 5px;"><i class="fas fa-file-pdf"></i>
            ส่งออกเป็น PDF</a>
    </div>
</div>

<style>
    @media print {
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            background: white;
            color: black;
            font-size: 12pt;
        }

        .sidebar,
        .app-header,
        .btn-gradient,
        a.btn-gradient,
        button,
        .hide-on-print {
            display: none !important;
        }

        .main-panel {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .content-card {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin-bottom: 20px !important;
        }

        .stat-grid {
            display: none !important;
        }

        .print-header {
            display: block !important;
        }

        table {
            border: 1px solid #000 !important;
        }

        th,
        td {
            border: 1px solid #000 !important;
            padding: 8px !important;
        }

        /* Prevent page breaks inside cards/tables */
        .content-card {
            page-break-inside: avoid;
        }

        tr {
            page-break-inside: avoid;
        }
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById('radarChart').getContext('2d');
        const data = {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'ร้อยละคะแนนที่ได้',
                data: <?php echo json_encode($chart_data_percent); ?>,
                fill: true,
                backgroundColor: 'rgba(233, 64, 87, 0.2)',
                borderColor: 'rgba(233, 64, 87, 1)',
                pointBackgroundColor: 'rgba(233, 64, 87, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(233, 64, 87, 1)'
            }]
        };
        const config = {
            type: 'radar',
            data: data,
            options: {
                responsive: true,
                scales: {
                    r: {
                        angleLines: { display: true },
                        suggestedMin: 0,
                        suggestedMax: 100,
                        ticks: {
                            stepSize: 20,
                            callback: function (value) { return value + '%'; }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        };
        new Chart(ctx, config);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>