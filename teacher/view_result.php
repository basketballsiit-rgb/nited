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

    <?php if (empty($supervision['teacher_signature_path'])): ?>
        <div class="content-card" style="margin-top: 30px; background-color: #fcfcfc; border: 2px dashed #3498db;">
            <h3 style="color: #3498db; text-align: center;"><i class="fas fa-pen-nib"></i> เซ็นรับทราบผลการนิเทศ</h3>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">กรุณาเซ็นชื่อรับทราบผลการนิเทศก่อนที่จะส่งออกเป็นเอกสาร PDF</p>
            
            <form id="teacherSignForm">
                <input type="hidden" name="id" value="<?php echo $supervision['id']; ?>">
                <input type="hidden" name="type" value="supervision">
                <input type="hidden" name="signature_base64" id="signatureBase64">
                
                <div style="text-align: center; margin-bottom: 15px;">
                    <canvas id="signaturePad" width="400" height="200" style="border: 2px solid #ccc; border-radius: 8px; background: #fff; touch-action: none; cursor: crosshair; max-width: 100%;"></canvas>
                    <br>
                    <button type="button" class="btn btn-sm" style="margin-top: 10px; background-color: #f8f9fa; border: 1px solid #ddd;" onclick="clearSignature()"><i class="fas fa-eraser"></i> ล้างกระดาน (Clear)</button>
                </div>
                
                <div style="text-align: center; margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">หรืออัปโหลดไฟล์รูปลายเซ็น:</label>
                    <input type="file" name="signature_file" accept="image/*" class="form-control" style="max-width: 400px; margin: 0 auto;">
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn-gradient" style="padding: 10px 30px; font-size: 16px;"><i class="fas fa-save"></i> ยืนยันการรับทราบ</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div style="background-color: #fff; border: 1px solid #eee; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px; page-break-inside: avoid;">
            <h3 style="margin: 0; margin-bottom: 10px; color: #333; font-size: 18px;"><i class="fas fa-signature"></i> ลายมือชื่อผู้รับการนิเทศ</h3>
            <img src="<?php echo htmlspecialchars($supervision['teacher_signature_path']); ?>" alt="ลายเซ็น" style="max-width: 100%; max-height: 120px; border: 1px dashed #ccc; padding: 10px; border-radius: 5px; background: #fafafa; margin-bottom: 10px;">
            <p style="margin: 0; font-size: 16px; color: #555;">(ลงชื่อ) <?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?> (ผู้สอน)</p>
            <p style="margin: 0; font-size: 14px; color: #888;">วันที่รับทราบ: <?php echo date('d/m/Y H:i', strtotime($supervision['teacher_signed_at'])); ?></p>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="export_observation_pdf.php?id=<?php echo $supervision['id']; ?>" target="_blank" class="btn-gradient hide-on-print"
                style="display: inline-block; background: linear-gradient(135deg, #2c3e50, #3498db); text-decoration: none; padding: 10px 20px; color: white; border-radius: 5px;"><i class="fas fa-file-pdf"></i>
                ส่งออกเป็น PDF</a>
        </div>
    <?php endif; ?>
</div>

<script>
    // Setup Signature Pad if exists
    const canvas = document.getElementById('signaturePad');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let drawing = false;

        ctx.fillStyle = "white";
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const getPos = (e) => {
            const rect = canvas.getBoundingClientRect();
            if (e.touches && e.touches.length > 0) {
                return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
            }
            return { x: e.clientX - rect.left, y: e.clientY - rect.top };
        };

        const startDrawing = (e) => {
            drawing = true;
            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            e.preventDefault(); 
        };

        const draw = (e) => {
            if (!drawing) return;
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.stroke();
            e.preventDefault();
        };

        const stopDrawing = (e) => {
            if (!drawing) return;
            drawing = false;
            ctx.closePath();
            document.getElementById('signatureBase64').value = canvas.toDataURL('image/png');
            if (e) e.preventDefault();
        };

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDrawing);
        canvas.addEventListener('touchcancel', stopDrawing);
    }

    function clearSignature() {
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = "white";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        document.getElementById('signatureBase64').value = "";
    }

    const form = document.getElementById('teacherSignForm');
    if(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const sigBase64 = document.getElementById('signatureBase64').value;
            const fileInput = document.querySelector('input[name="signature_file"]');
            
            let hasBlankCanvas = true;
            if(canvas) {
                const blankCanvas = document.createElement('canvas');
                blankCanvas.width = canvas.width;
                blankCanvas.height = canvas.height;
                const bCtx = blankCanvas.getContext('2d');
                bCtx.fillStyle = "white";
                bCtx.fillRect(0, 0, blankCanvas.width, blankCanvas.height);
                hasBlankCanvas = (sigBase64 === blankCanvas.toDataURL('image/png') || sigBase64 === "");
            }
            
            if (hasBlankCanvas && fileInput.files.length === 0) {
                Swal.fire('ข้อผิดพลาด', 'กรุณาเซ็นชื่อรับทราบ หรืออัปโหลดไฟล์รูปลายเซ็น', 'warning');
                return;
            }

            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('teacher_sign_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ',
                        text: 'ระบบได้บันทึกลายเซ็นของคุณเรียบร้อยแล้ว',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            });
        });
    }
</script>

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