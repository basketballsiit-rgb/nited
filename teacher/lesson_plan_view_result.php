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
$lesson_plan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Lesson Plan Info
$stmt = $pdo->prepare("
    SELECT lp.*, u.name as reviewer_name, ay.year, ay.term 
    FROM lesson_plans lp 
    LEFT JOIN users u ON lp.reviewer_id = u.id 
    LEFT JOIN academic_years ay ON lp.academic_year_id = ay.id
    WHERE lp.id = ? AND lp.teacher_id = ?
");
$stmt->execute([$lesson_plan_id, $teacher_id]);
$plan = $stmt->fetch();

if (!$plan || $plan['status'] === 'pending') {
    echo "<div class='content-card'><h3>ข้อผิดพลาด</h3><p>ไม่พบข้อมูลแผนการสอนนี้ หรือยังไม่ถูกประเมิน</p><a href='lesson_plans.php' class='btn-gradient'>กลับ</a></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
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
$results = [];
$total_score = 0;
$max_possible = 0;
foreach ($res as $r) {
    $results[$r['criteria_item_id']] = $r;
}
?>

<div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-file-invoice"></i> ผลการตรวจแผนการจัดการเรียนรู้เต็มเล่ม</h2>
        <p class="text-muted">ปีการศึกษา <?php echo $plan['term'] . '/' . $plan['year']; ?></p>
    </div>
    <a href="lesson_plans.php" style="color: #666; text-decoration: none;"><i class="fas fa-arrow-left"></i> กลับไปประวัติ</a>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 20px;">
    <div class="stat-card bg-orange">
        <h3 style="font-size: 14px;"><i class="fas fa-user-tie"></i> กรรมการผู้ประเมิน</h3>
        <p class="value" style="font-size: 20px; margin-top: 5px;"><?php echo htmlspecialchars($plan['reviewer_name']); ?></p>
    </div>
    <div class="stat-card bg-blue">
        <h3 style="font-size: 14px;"><i class="fas fa-book"></i> รายวิชา</h3>
        <p class="value" style="font-size: 18px; margin-top: 5px;">
            <?php echo htmlspecialchars($plan['subject_name']); ?><br>
            <span style="font-size: 14px; font-weight: normal;">วันที่ตรวจเสร็จ: <?php echo date('d/m/Y H:i', strtotime($plan['reviewed_at'])); ?> น.</span>
        </p>
    </div>
</div>

<div class="content-card">
    <div style="background-color: <?php echo ($plan['status'] === 'approved') ? '#d1e7dd' : (($plan['status'] === 'revision') ? '#fff3cd' : '#f8d7da'); ?>; 
                color: <?php echo ($plan['status'] === 'approved') ? '#0f5132' : (($plan['status'] === 'revision') ? '#856404' : '#842029'); ?>; 
                padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid <?php echo ($plan['status'] === 'approved') ? '#badbcc' : (($plan['status'] === 'revision') ? '#ffeeba' : '#f5c2c7'); ?>;">
        <h3 style="margin-top: 0; margin-bottom: 5px;">
            <i class="fas <?php echo ($plan['status'] === 'approved') ? 'fa-check-circle' : (($plan['status'] === 'revision') ? 'fa-exclamation-triangle' : 'fa-times-circle'); ?>"></i> 
            ผลการวิเคราะห์: 
            <?php 
                if ($plan['status'] === 'approved') echo "ผ่านการอนุมัติ";
                elseif ($plan['status'] === 'revision') echo "ส่งกลับไปปรับปรุงแก้ไข";
                elseif ($plan['status'] === 'rejected') echo "ไม่ผ่านการอนุมัติ";
            ?>
        </h3>
        <?php if (!empty($plan['review_comment'])): ?>
            <hr style="border-color: rgba(0,0,0,0.1); margin: 10px 0;">
            <strong>ข้อเสนอแนะภาพรวมจากกรรมการ:</strong><br>
            <?php echo nl2br(htmlspecialchars($plan['review_comment'])); ?>
        <?php endif; ?>
    </div>

    <h3 style="margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;"><i class="fas fa-list-alt"></i> รายละเอียดคะแนนการประเมิน</h3>

    <?php foreach ($categories as $cat): ?>
        <?php if (isset($items_by_cat[$cat['id']])): ?>
            <div style="margin-bottom: 30px;">
                <h4 style="color: #E94057; margin-bottom: 15px; border-left: 4px solid #F27121; padding-left: 10px;">
                    <?php echo htmlspecialchars($cat['title']); ?>
                </h4>
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background-color: #f8f9fa;">
                            <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 25%;">หัวข้อ</th>
                            <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 35%;">ตัวชี้วัด/รายการที่ต้องมี</th>
                            <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center; width: 15%;">คะแนนที่ได้</th>
                            <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 25%;">ข้อสังเกต/เสนอแนะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $in_skipped_section = false;
                        foreach ($items_by_cat[$cat['id']] as $idx => $item): 
                            if (isset($item['is_header']) && $item['is_header']) {
                                // Check if this section is skipped
                                $in_skipped_section = (isset($plan['optional_sections']) && strpos($plan['optional_sections'], '['.$item['id'].']') !== false);
                                
                                echo '<tr style="background-color: #e9ecef; border-bottom: 2px solid #ccc;">';
                                echo '<td colspan="4" style="padding: 12px 10px; font-weight: bold; color: #333;">';
                                echo htmlspecialchars($item['description']);
                                if ($in_skipped_section) {
                                    echo ' <span style="color: #dc3545; font-size: 13px; font-weight: normal;">(ไม่ได้ประเมินในส่วนนี้)</span>';
                                }
                                echo '</td></tr>';
                                continue;
                            }

                            if (!$in_skipped_section) {
                                $r = $results[$item['id']] ?? null; 
                                $score = $r ? floatval($r['score']) : 0;
                                $total_score += $score;
                                $max_possible += $item['max_score'];
                                
                                $score_text = $score;
                                if ($item['max_score'] <= 1) {
                                    $score_text = ($score == 1) ? 'ดี (1)' : 'ปรับปรุง (0)';
                                }
                            } else {
                                $score_text = '-';
                                $r = null;
                            }
                        ?>
                            <tr style="border-bottom: 1px solid #eee; <?php echo $in_skipped_section ? 'opacity: 0.5;' : ''; ?>">
                                <td style="padding: 12px 10px; vertical-align: top;">
                                    <?php echo ($idx + 1) . '. ' . htmlspecialchars($item['description']); ?>
                                </td>
                                <td style="padding: 12px 10px; vertical-align: top; color: #555; white-space: pre-line;">
                                    <?php echo htmlspecialchars($item['indicator'] ?? '-'); ?>
                                </td>
                                <td style="padding: 12px 10px; text-align: center; vertical-align: top; font-weight: bold; font-size: 16px; color: #4f46e5;">
                                    <?php echo $score_text; ?>
                                </td>
                                <td style="padding: 12px 10px; vertical-align: top; color: #555;">
                                    <?php echo $r && !empty($r['comment']) ? htmlspecialchars($r['comment']) : '<span style="color:#aaa;">-</span>'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div style="background-color: #f1f8ff; border: 1px solid #cce5ff; padding: 20px; border-radius: 8px; text-align: center; margin-top: 30px;">
        <h3 style="margin: 0; color: #004085;">คะแนนรวมทั้งหมด</h3>
        <div style="font-size: 36px; font-weight: bold; color: #0d6efd; margin-top: 10px;">
            <?php echo number_format($total_score, 2); ?> <span style="font-size: 20px; color: #666;">/ <?php echo $max_possible; ?></span>
        </div>
        <?php
            $percentage = ($max_possible > 0) ? ($total_score / $max_possible) * 100 : 0;
            $grade = 'ปรับปรุง';
            $grade_color = '#dc3545';
            if ($percentage >= 80) { $grade = 'ดีมาก'; $grade_color = '#28a745'; }
            elseif ($percentage >= 70) { $grade = 'ดี'; $grade_color = '#17a2b8'; }
            elseif ($percentage >= 60) { $grade = 'ปานกลาง'; $grade_color = '#ffc107'; }
            elseif ($percentage >= 50) { $grade = 'พอใช้'; $grade_color = '#fd7e14'; }
        ?>
        <div style="margin-top: 10px; font-size: 16px;">
            คิดเป็น <strong style="color: #333;"><?php echo number_format($percentage, 2); ?>%</strong> 
            (ระดับคุณภาพ: <span style="color: <?php echo $grade_color; ?>; font-weight: bold;"><?php echo $grade; ?></span>)
        </div>
    </div>

    <?php if (!empty($plan['signature_path'])): ?>
    <div style="background-color: #fff; border: 1px solid #eee; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px;">
        <h3 style="margin: 0; margin-bottom: 10px; color: #333; font-size: 18px;"><i class="fas fa-signature"></i> ลายมือชื่อผู้ประเมิน</h3>
        <img src="<?php echo htmlspecialchars($plan['signature_path']); ?>" alt="ลายเซ็น" style="max-width: 100%; max-height: 120px; border: 1px dashed #ccc; padding: 10px; border-radius: 5px; background: #fafafa; margin-bottom: 10px;">
        <p style="margin: 0; font-size: 16px; color: #555;">(ลงชื่อ) <?php echo htmlspecialchars($plan['reviewer_name'] ?? ''); ?> (ผู้ประเมิน)</p>
    </div>
    <?php endif; ?>

    <?php if (empty($plan['teacher_signature_path'])): ?>
        <div class="content-card" style="margin-top: 30px; background-color: #fcfcfc; border: 2px dashed #3498db;">
            <h3 style="color: #3498db; text-align: center;"><i class="fas fa-pen-nib"></i> เซ็นรับทราบผลการประเมิน</h3>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">กรุณาเซ็นชื่อรับทราบผลการประเมินก่อนที่จะส่งออกเป็นเอกสาร PDF</p>
            
            <form id="teacherSignForm">
                <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                <input type="hidden" name="type" value="lesson_plan">
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
        <div style="background-color: #fff; border: 1px solid #eee; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px;">
            <h3 style="margin: 0; margin-bottom: 10px; color: #333; font-size: 18px;"><i class="fas fa-signature"></i> ลายมือชื่อผู้รับการประเมิน</h3>
            <img src="<?php echo htmlspecialchars($plan['teacher_signature_path']); ?>" alt="ลายเซ็น" style="max-width: 100%; max-height: 120px; border: 1px dashed #ccc; padding: 10px; border-radius: 5px; background: #fafafa; margin-bottom: 10px;">
            <p style="margin: 0; font-size: 16px; color: #555;">(ลงชื่อ) <?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?> (ผู้สอน)</p>
            <p style="margin: 0; font-size: 14px; color: #888;">วันที่รับทราบ: <?php echo date('d/m/Y H:i', strtotime($plan['teacher_signed_at'])); ?></p>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="export_lesson_plan_pdf.php?id=<?php echo $plan['id']; ?>" target="_blank" class="btn-gradient hide-on-print" 
               style="display: inline-block; background: linear-gradient(135deg, #2c3e50, #3498db); text-decoration: none; padding: 10px 20px; color: white; border-radius: 5px;"><i class="fas fa-file-pdf"></i> พิมพ์ผลการประเมิน (PDF)</a>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
