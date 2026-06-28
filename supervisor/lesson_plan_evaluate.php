<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'executive') {
    header("Location: /nited/index.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$reviewer_id = $_SESSION['user_id'];
$lesson_plan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Lesson Plan Info
$stmt = $pdo->prepare("
    SELECT lp.*, u.name as teacher_name, ay.year, ay.term 
    FROM lesson_plans lp 
    LEFT JOIN users u ON lp.teacher_id = u.id 
    LEFT JOIN academic_years ay ON lp.academic_year_id = ay.id
    WHERE lp.id = ? AND lp.reviewer_id = ?
");
$stmt->execute([$lesson_plan_id, $reviewer_id]);
$plan = $stmt->fetch();

if (!$plan) {
    echo "<div class='content-card'><h3>ข้อผิดพลาด</h3><p>ไม่พบข้อมูลแผนการสอนนี้ หรือท่านไม่มีสิทธิ์ประเมิน</p><a href='lesson_plans_review.php' class='btn-gradient'>กลับ</a></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$is_already_evaluated = ($plan['status'] !== 'pending');

// Fetch active Criteria categories and items
$stmt = $pdo->query("SELECT * FROM lp_criteria_categories ORDER BY order_idx ASC, id ASC");
$categories = $stmt->fetchAll();

$items_by_cat = [];
$stmt = $pdo->query("SELECT * FROM lp_criteria_items ORDER BY order_idx ASC, id ASC");
while ($row = $stmt->fetch()) {
    $items_by_cat[$row['category_id']][] = $row;
}

// Fetch existing results if any
$existing_results = [];
if ($is_already_evaluated) {
    $stmt = $pdo->prepare("SELECT * FROM lesson_plan_results WHERE lesson_plan_id = ?");
    $stmt->execute([$lesson_plan_id]);
    $res = $stmt->fetchAll();
    foreach ($res as $r) {
        $existing_results[$r['criteria_item_id']] = $r;
    }
}
?>

<div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-file-signature"></i> ประเมินแผนการจัดการเรียนรู้เต็มเล่ม</h2>
        <p class="text-muted">ปีการศึกษา <?php echo $plan['term'] . '/' . $plan['year']; ?></p>
    </div>
    <a href="lesson_plans_review.php" style="color: #666; text-decoration: none;"><i class="fas fa-arrow-left"></i> กลับ</a>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 20px;">
    <div class="stat-card bg-orange">
        <h3 style="font-size: 14px;"><i class="fas fa-user-graduate"></i> ครูผู้ส่งแผนฯ</h3>
        <p class="value" style="font-size: 20px; margin-top: 5px;"><?php echo htmlspecialchars($plan['teacher_name']); ?></p>
    </div>
    <div class="stat-card bg-blue">
        <h3 style="font-size: 14px;"><i class="fas fa-book"></i> รายวิชา</h3>
        <p class="value" style="font-size: 18px; margin-top: 5px;">
            <?php echo htmlspecialchars($plan['subject_name']); ?><br>
            <a href="/nited/<?php echo htmlspecialchars($plan['file_path']); ?>" target="_blank" style="font-size: 14px; font-weight: normal; color: white; text-decoration: underline;"><i class="fas fa-download"></i> ดาวน์โหลดแผนการสอนเพื่อดูประกอบ</a>
        </p>
    </div>
</div>

<div class="content-card">
    <?php if ($is_already_evaluated): ?>
        <div style="background-color: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #badbcc;">
            <strong><i class="fas fa-check-circle"></i> ท่านได้ประเมินแผนการสอนนี้ไปแล้ว</strong><br>
            สถานะปัจจุบัน: 
            <?php 
                if ($plan['status'] === 'approved') echo "ผ่าน";
                elseif ($plan['status'] === 'revision') echo "กลับไปแก้ไข";
                elseif ($plan['status'] === 'rejected') echo "ไม่ผ่าน";
            ?>
        </div>
    <?php else: ?>
        <div style="background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ffeeba;">
            <strong>คำชี้แจง:</strong> ให้ทำเครื่องหมาย (คะแนน) ลงในช่องระดับการประเมินที่ตรงกับความเป็นจริงมากที่สุด โดยระบุการให้คะแนนดังนี้<br>
            0 = ปรับปรุง<br>
            1 = ดี (กรณีที่คะแนนเต็ม 1) หรือ พอใช้ (กรณีที่คะแนนเต็ม 2)<br>
            2 = ดี
        </div>
    <?php endif; ?>

    <form id="evalPlanForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="evaluate_plan">
        <input type="hidden" name="lesson_plan_id" value="<?php echo $plan['id']; ?>">

        <?php foreach ($categories as $cat): ?>
            <?php if (isset($items_by_cat[$cat['id']])): ?>
                <div style="margin-bottom: 30px;">
                    <h4 style="color: #E94057; margin-bottom: 15px; border-left: 4px solid #F27121; padding-left: 10px;">
                        <?php echo htmlspecialchars($cat['title']); ?>
                    </h4>
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background-color: #f8f9fa;">
                                <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 30%;">หัวข้อ</th>
                                <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 30%;">ตัวชี้วัด/รายการที่ต้องมี</th>
                                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center; width: 20%;">ระดับคะแนน (เต็ม <span class="max-score-display">0</span>)</th>
                                <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 20%;">ข้อสังเกต/เสนอแนะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items_by_cat[$cat['id']] as $idx => $item): ?>
                                <?php $res = $existing_results[$item['id']] ?? null; ?>
                                <?php if (isset($item['is_header']) && $item['is_header']): ?>
                                    <tr style="background-color: #e9ecef; border-bottom: 2px solid #ccc;" class="header-row" data-header-id="<?php echo $item['id']; ?>">
                                        <td colspan="<?php echo isset($item['is_optional']) && $item['is_optional'] ? '2' : '4'; ?>" style="padding: 12px 10px; font-weight: bold; color: #333;">
                                            <?php echo htmlspecialchars($item['description']); ?>
                                        </td>
                                        <?php if (isset($item['is_optional']) && $item['is_optional']): ?>
                                            <td colspan="2" style="padding: 12px 10px; text-align: right; font-weight: bold; color: #333;">
                                                <label style="margin-right: 15px; cursor: pointer;">
                                                    <input type="radio" name="optional_header[<?php echo $item['id']; ?>]" value="1" 
                                                           onchange="toggleOptionalSection(<?php echo $item['id']; ?>, true)" 
                                                           <?php if (!isset($plan['optional_sections']) || (isset($plan['optional_sections']) && strpos($plan['optional_sections'], '['.$item['id'].']') === false)) echo 'checked'; ?>
                                                           <?php if ($is_already_evaluated && $plan['status'] !== 'draft') echo 'disabled'; ?>> 
                                                    มี
                                                </label>
                                                <label style="cursor: pointer;">
                                                    <input type="radio" name="optional_header[<?php echo $item['id']; ?>]" value="0" 
                                                           onchange="toggleOptionalSection(<?php echo $item['id']; ?>, false)"
                                                           <?php if (isset($plan['optional_sections']) && strpos($plan['optional_sections'], '['.$item['id'].']') !== false) echo 'checked'; ?>
                                                           <?php if ($is_already_evaluated && $plan['status'] !== 'draft') echo 'disabled'; ?>> 
                                                    ไม่มี
                                                </label>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php $current_header_id = $item['id']; ?>
                                <?php else: ?>
                                    <tr style="border-bottom: 1px solid #eee;" class="item-row header-group-<?php echo isset($current_header_id) ? $current_header_id : '0'; ?>">
                                        <td style="padding: 15px 10px; vertical-align: top;">
                                            <?php echo ($idx + 1) . '. ' . htmlspecialchars($item['description']); ?>
                                        </td>
                                        <td style="padding: 15px 10px; vertical-align: top; color: #555; white-space: pre-line;">
                                            <?php echo htmlspecialchars($item['indicator'] ?? '-'); ?>
                                        </td>
                                        <td style="padding: 15px 10px; text-align: center; vertical-align: top;">
                                            <div class="radio-group" style="display: flex; justify-content: center; gap: 10px;" data-maxscore="<?php echo $item['max_score']; ?>">
                                                <?php 
                                                // Determine scoring boundaries
                                                $min_v = ($item['max_score'] <= 1) ? 0 : 1; 
                                                for ($i = $item['max_score']; $i >= $min_v; $i--): 
                                                    $label = $i;
                                                    if ($item['max_score'] <= 1) {
                                                        $label = ($i == 1) ? 'ดี (1)' : 'ปรับปรุง (0)';
                                                    }
                                                ?>
                                                    <label style="cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: flex-end;">
                                                        <span style="font-size: 13px; margin-bottom: 5px;"><?php echo $label; ?></span>
                                                        <input type="radio" name="scores[<?php echo $item['id']; ?>]" value="<?php echo $i; ?>" class="score-input" required 
                                                            style="transform: scale(1.2);"
                                                            <?php if ($res && $res['score'] == $i) echo 'checked'; ?>
                                                            <?php if ($is_already_evaluated && $plan['status'] !== 'draft') echo 'disabled'; ?>>
                                                    </label>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td style="padding: 15px 10px; vertical-align: top;">
                                            <input type="text" name="comments[<?php echo $item['id']; ?>]" class="comment-input"
                                                value="<?php echo htmlspecialchars($res['comment'] ?? ''); ?>"
                                                style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;" 
                                                placeholder="ข้อเสนอแนะ..." <?php if ($is_already_evaluated && $plan['status'] !== 'draft') echo 'readonly'; ?>>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div style="margin-bottom: 30px; background-color: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee;">
            <h4 style="margin-top: 0; color: #333;"><i class="fas fa-comment-dots"></i> สรุปผลการประเมินและภาพรวม</h4>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">ผลการพิเคราะห์:</label>
                <?php if ($is_already_evaluated && $plan['status'] !== 'draft'): ?>
                    <input type="text" readonly class="form-control" value="<?php echo ($plan['status']==='approved' ? 'อนุมัติ / ผ่าน' : ($plan['status']==='revision' ? 'ส่งกลับไปแก้ไข' : 'ไม่อนุมัติ')); ?>">
                <?php else: ?>
                    <select name="final_status" class="form-control" required>
                        <option value="">-- เลือกสถานะ --</option>
                        <option value="approved">อนุมัติ / ผ่านการตรวจ</option>
                        <option value="revision">ส่งกลับไปปรับปรุงแก้ไข</option>
                        <option value="rejected">ไม่อนุมัติ / ไม่ผ่าน</option>
                    </select>
                <?php endif; ?>
            </div>

            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">ข้อเสนอแนะภาพรวมแผนการสอน (ถ้ามี):</label>
                <textarea name="overall_comment" class="form-control" rows="3" placeholder="ระบุข้อเสนอแนะต่างๆ..." <?php if ($is_already_evaluated && $plan['status'] !== 'draft') echo 'readonly'; ?>><?php echo htmlspecialchars($plan['review_comment'] ?? ''); ?></textarea>
            </div>
        </div>

        <div style="margin-bottom: 30px; background-color: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee;">
            <h4 style="margin-top: 0; color: #333;"><i class="fas fa-signature"></i> ลายมือชื่อผู้ประเมิน</h4>
            
            <?php if ($is_already_evaluated && $plan['status'] !== 'draft' && !empty($plan['signature_path'])): ?>
                <div style="text-align: center; padding: 20px; border: 1px dashed #ccc; background: #fff; border-radius: 5px;">
                    <img src="<?php echo htmlspecialchars($plan['signature_path']); ?>" alt="ลายมือชื่อ" style="max-width: 100%; max-height: 150px;">
                </div>
            <?php else: ?>
                <input type="hidden" name="signature_base64" id="signatureBase64">
                <input type="hidden" name="has_existing_signature" id="hasExistingSignature" value="<?php echo !empty($plan['signature_path']) ? '1' : '0'; ?>">
                
                <div style="text-align: center; margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 10px;">วาดลายเซ็นที่นี่:</label>
                    <canvas id="signaturePad" width="400" height="200" style="border: 2px dashed #007bff; border-radius: 8px; background: #fff; touch-action: none; cursor: crosshair;"></canvas>
                    <br>
                    <button type="button" class="btn btn-sm" style="margin-top: 10px; background-color: #f8f9fa; border: 1px solid #ddd;" onclick="clearSignature()"><i class="fas fa-eraser"></i> ล้างกระดาน (Clear)</button>
                </div>
                
                <div style="text-align: center; margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">หรืออัปโหลดไฟล์รูปลายเซ็น:</label>
                    <input type="file" name="signature_file" accept="image/*" class="form-control" style="max-width: 400px; margin: 0 auto;">
                </div>
                
                <?php if (!empty($plan['signature_path'])): ?>
                    <div style="text-align: center; margin-top: 15px; padding: 10px; background-color: #d4edda; border-radius: 5px; color: #155724;">
                        <i class="fas fa-check-circle"></i> มีลายเซ็นเดิมถูกบันทึกไว้ในระบบแล้ว <br>
                        <small>(หากต้องการเปลี่ยนลายเซ็น ให้วาดใหม่หรืออัปโหลดไฟล์ใหม่)</small>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!$is_already_evaluated || $plan['status'] === 'draft'): ?>
            <div style="text-align: center; margin-top: 30px; border-top: 2px solid #eee; padding-top: 20px; display: flex; justify-content: center; gap: 15px;">
                <button type="button" class="btn-gradient" style="padding: 12px 20px; font-size: 16px; background: linear-gradient(135deg, #dc3545, #bd2130);" onclick="clearForm()">
                    <i class="fas fa-undo"></i> ล้างข้อมูล (Clear)
                </button>
                <button type="button" class="btn-gradient" style="padding: 12px 30px; font-size: 16px; background: linear-gradient(135deg, #6c757d, #495057);" onclick="saveDraft()">
                    <i class="fas fa-save"></i> บันทึกร่าง (Save Draft)
                </button>
                <button type="submit" class="btn-gradient" style="padding: 12px 30px; font-size: 16px;">
                    <i class="fas fa-paper-plane"></i> บันทึกผลการตรวจแผนฯ (Submit)
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
    function toggleOptionalSection(headerId, isChecked) {
        const rows = document.querySelectorAll('.header-group-' + headerId);
        rows.forEach(row => {
            if (isChecked) {
                row.style.opacity = '1';
                row.querySelectorAll('.score-input').forEach(input => {
                    input.disabled = false;
                    input.required = true;
                });
            } else {
                row.style.opacity = '0.5';
                row.querySelectorAll('.score-input').forEach(input => {
                    input.disabled = true;
                    input.checked = false; // clear selection if any
                    input.required = false; // remove HTML5 required validation
                });
            }
        });
    }

    // Initialize the sections on load
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('input[type="radio"][name^="optional_header"]:checked').forEach(radio => {
            if (radio.value === '0') {
                const headerId = radio.name.match(/\[(\d+)\]/)[1];
                toggleOptionalSection(headerId, false);
            }
        });

        // Setup Signature Pad
        const canvas = document.getElementById('signaturePad');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            let drawing = false;

            // Initialize white background
            ctx.fillStyle = "white";
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            const getPos = (e) => {
                const rect = canvas.getBoundingClientRect();
                if (e.touches && e.touches.length > 0) {
                    return { 
                        x: e.touches[0].clientX - rect.left, 
                        y: e.touches[0].clientY - rect.top 
                    };
                }
                return { 
                    x: e.clientX - rect.left, 
                    y: e.clientY - rect.top 
                };
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
                // Store base64 whenever drawing stops, ignoring the pure white initial state
                document.getElementById('signatureBase64').value = canvas.toDataURL('image/png');
                if (e) e.preventDefault();
            };

            // Mouse Events
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);

            // Touch Events (iPad/Tablets)
            canvas.addEventListener('touchstart', startDrawing, {passive: false});
            canvas.addEventListener('touchmove', draw, {passive: false});
            canvas.addEventListener('touchend', stopDrawing, {passive: false});
        }
    });

    function clearSignature() {
        const canvas = document.getElementById('signaturePad');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = "white";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            document.getElementById('signatureBase64').value = ''; // clear data
        }
    }

    function clearForm() {
        Swal.fire({
            title: 'ยืนยันการล้างข้อมูล?',
            text: "ข้อมูลคะแนนและข้อเสนอแนะที่กรอกไว้ทั้งหมดบนหน้าจอนี้จะถูกล้างค่า (ไม่ส่งผลต่อข้อมูลที่บันทึกไว้ในฐานข้อมูลจนกว่าจะกดบันทึก)",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ล้างข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                // Clear all radio scores
                document.querySelectorAll('.score-input').forEach(input => {
                    input.checked = false;
                });
                
                // Reset optional headers to '1' (มี)
                document.querySelectorAll('input[type="radio"][name^="optional_header"][value="1"]').forEach(input => {
                    input.checked = true;
                    const headerId = input.name.match(/\[(\d+)\]/)[1];
                    toggleOptionalSection(headerId, true);
                });

                // Clear all text inputs and textareas
                document.querySelectorAll('.comment-input, textarea[name="overall_comment"]').forEach(input => {
                    input.value = '';
                });

                // Reset final status select
                const finalStatus = document.querySelector('select[name="final_status"]');
                if (finalStatus) finalStatus.value = '';
            }
        });
    }

    function saveDraft() {
        // Build the form data, bypass standard required validation since it's a draft
        Swal.fire({
            title: 'กำลังบันทึกร่าง...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const formData = new FormData(document.getElementById('evalPlanForm'));
        formData.append('is_draft', '1');

        // Optional fields that might be empty and fail form validation normally
        // Final Status doesn't matter for draft, but we need something to prevent DB errors if strict
        if (!formData.get('final_status')) {
            formData.set('final_status', 'draft'); 
        }

        fetch('lesson_plan_evaluate_action.php', {
            method: 'POST', body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success', title: 'บันทึกร่างสำเร็จ!', showConfirmButton: false, timer: 1500
                }).then(() => window.location.reload());
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        })
        .catch(() => Swal.fire('ข้อผิดพลาด', 'ติดต่อเซิร์ฟเวอร์ไม่ได้', 'error'));
    }
    document.querySelectorAll('.radio-group').forEach(group => {
        let th = group.closest('table').querySelector('thead th:nth-child(3) span');
        if (th && th.innerText === '0') {
            th.innerText = group.dataset.maxscore;
        }
    });

    const form = document.getElementById('evalPlanForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate that required inputs in non-skipped sections are filled
            let allFilled = true;
            document.querySelectorAll('.item-row').forEach(row => {
                if (row.style.opacity !== '0.5') { // If the section is active
                    const group = row.querySelector('.radio-group');
                    if (group) {
                        const isChecked = Array.from(group.querySelectorAll('.score-input')).some(radio => radio.checked);
                        if (!isChecked) allFilled = false;
                    }
                }
            });

            if (!allFilled) {
                Swal.fire('ข้อมูลไม่ครบ', 'กรุณาให้คะแนนครบทุกข้อย่อยที่ไม่ถูกข้าม (มี/ไม่มี)', 'warning');
                return;
            }

            // Signature Validation on Submit
            const statusVal = document.querySelector('select[name="final_status"]').value;
            const sigBase64 = document.getElementById('signatureBase64') ? document.getElementById('signatureBase64').value : '';
            const sigFile = document.querySelector('input[name="signature_file"]') ? document.querySelector('input[name="signature_file"]').value : '';
            const hasExisting = document.getElementById('hasExistingSignature') ? document.getElementById('hasExistingSignature').value : '0';

            // Require signature if not draft and no existing signature
            if (statusVal && statusVal !== 'draft' && statusVal !== '' && !sigBase64 && !sigFile && hasExisting === '0') {
                Swal.fire('กรุณาลงลายมือชื่อ', 'โปรดวาดลายเซ็นบนกระดาน หรืออัปโหลดรูปลายเซ็น ก่อนบันทึกผลการตรวจ', 'warning');
                return;
            }

            Swal.fire({
                title: 'ยืนยันการบันทึกผลการตรวจแผนฯ?',
                text: "เมื่อบันทึกแล้ว จะไม่สามารถแก้ไขได้อีก คุณครูผู้ส่งจะเห็นผลการประเมินตามนี้",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'บันทึกเลย',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData(this);

                    Swal.fire({
                        title: 'กำลังบันทึกข้อมูล...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch('lesson_plan_evaluate_action.php', {
                        method: 'POST', body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success', title: 'ประเมินแผนสำเร็จ!', showConfirmButton: false, timer: 1500
                            }).then(() => window.location = 'lesson_plans_review.php');
                        } else {
                            Swal.fire('ข้อผิดพลาด', data.message, 'error');
                        }
                    })
                    .catch(() => Swal.fire('ข้อผิดพลาด', 'ติดต่อเซิร์ฟเวอร์ไม่ได้', 'error'));
                }
            });
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
