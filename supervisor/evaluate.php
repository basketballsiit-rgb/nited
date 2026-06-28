<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'executive' && $_SESSION['role'] !== 'teacher') {
    header("Location: /nited/index.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$supervisor_id = $_SESSION['user_id'];
$supervision_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Supervision Info
$stmt = $pdo->prepare("
    SELECT s.*, u.name as teacher_name, ay.year, ay.term 
    FROM supervisions s 
    LEFT JOIN users u ON s.teacher_id = u.id 
    LEFT JOIN academic_years ay ON s.academic_year_id = ay.id
    WHERE s.id = ? AND s.supervisor_id = ?
");
$stmt->execute([$supervision_id, $supervisor_id]);
$supervision = $stmt->fetch();

if (!$supervision || $supervision['status'] !== 'approved') {
    $msg = (!$supervision) ? "ไม่พบข้อมูลการนิเทศนี้" : "สถานะการนิเทศนี้ยังไม่พร้อมให้ประเมิน (กรุณายืนยันก่อน)";
    echo "<div class='content-card'><h3>กำลังโหลด... หากมีข้อผิดพลาดกรุณากลับไปหน้าจดบันทึกปฏิทิน</h3><a href='calendar.php' class='btn-gradient'>กลับ</a></div><script>Swal.fire('ข้อผิดพลาด', '{$msg}', 'error').then(()=>window.location='calendar.php');</script>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch active Criteria categories and items
$stmt = $pdo->query("SELECT * FROM criteria_categories ORDER BY id ASC");
$categories = $stmt->fetchAll();

$items_by_cat = [];
$stmt = $pdo->query("SELECT * FROM criteria_items ORDER BY id ASC");
while ($row = $stmt->fetch()) {
    $items_by_cat[$row['category_id']][] = $row;
}
?>

<div class="content-header"
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-edit"></i> แบบประเมินการจัดการเรียนการสอน</h2>
        <p class="text-muted">ปีการศึกษา
            <?php echo $supervision['term'] . '/' . $supervision['year']; ?>
        </p>
    </div>
    <a href="calendar.php" style="color: #666; text-decoration: none;"><i class="fas fa-arrow-left"></i> กลับ</a>
</div>

<div class="stat-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 20px;">
    <div class="stat-card bg-orange">
        <h3 style="font-size: 14px;"><i class="fas fa-user-graduate"></i> ครูผู้รับการนิเทศ</h3>
        <p class="value" style="font-size: 20px; margin-top: 5px;">
            <?php echo htmlspecialchars($supervision['teacher_name']); ?>
        </p>
    </div>
    <div class="stat-card bg-blue">
        <h3 style="font-size: 14px;"><i class="fas fa-book"></i> รายวิชา/วันเวลา</h3>
        <p class="value" style="font-size: 18px; margin-top: 5px;">
            <?php echo htmlspecialchars($supervision['subject_name']); ?><br>
            <span style="font-size: 14px; font-weight: normal;">วันที่
                <?php echo date('d/m/Y', strtotime($supervision['scheduled_date'])); ?>
            </span>
        </p>
    </div>
</div>

<div class="content-card">
    <div
        style="background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ffeeba;">
        <strong>คำชี้แจง:</strong> ให้ทำเครื่องหมาย (คะแนน) ลงในช่องระดับการประเมินที่ตรงกับความเป็นจริงมากที่สุด
        โดยมีเกณฑ์การประเมิน 5 ระดับ ดังนี้<br>
        5 = ดีมาก, 4 = ดี, 3 = ปานกลาง, 2 = พอใช้, 1 = ปรับปรุง
    </div>

    <form id="evalForm" enctype="multipart/form-data">
        <input type="hidden" name="supervision_id" value="<?php echo $supervision['id']; ?>">

        <?php foreach ($categories as $cat): ?>
            <?php if (isset($items_by_cat[$cat['id']])): ?>
                <div style="margin-bottom: 30px;">
                    <h4 style="color: #E94057; margin-bottom: 15px; border-left: 4px solid #F27121; padding-left: 10px;">
                        <?php echo htmlspecialchars($cat['title']); ?>
                    </h4>
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background-color: #f8f9fa;">
                                <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 40%;">รายการประเมิน</th>
                                <th style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center; width: 30%;">
                                    ระดับคะแนน (เต็ม <span class="max-score-display">0</span>)</th>
                                <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 30%;">ข้อเสนอแนะเพิ่มเติม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items_by_cat[$cat['id']] as $idx => $item): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 15px 10px; vertical-align: top;">
                                        <?php echo ($idx + 1) . '. ' . htmlspecialchars($item['description']); ?>
                                    </td>
                                    <td style="padding: 15px 10px; text-align: center; vertical-align: top;">
                                        <div class="radio-group" style="display: flex; justify-content: center; gap: 10px;"
                                            data-maxscore="<?php echo $item['max_score']; ?>">
                                            <?php for ($i = $item['max_score']; $i >= 1; $i--): ?>
                                                <label
                                                    style="cursor: pointer; display: flex; flex-direction: column; align-items: center;">
                                                    <span>
                                                        <?php echo $i; ?>
                                                    </span>
                                                    <input type="radio" name="scores[<?php echo $item['id']; ?>]"
                                                        value="<?php echo $i; ?>" required
                                                        style="margin-top: 5px; transform: scale(1.2);">
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 15px 10px; vertical-align: top;">
                                        <input type="text" name="comments[<?php echo $item['id']; ?>]"
                                            style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                                            placeholder="ข้อเสนอแนะ...">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($categories)): ?>
            <p style="text-align: center; color: #888;">ไม่พบหัวข้อการประเมินในระบบ กรุณาติดต่อผู้ดูแลระบบ</p>
        <?php else: ?>
            <div style="margin-bottom: 30px;">
                <h4 style="color: #E94057; margin-bottom: 15px; border-left: 4px solid #F27121; padding-left: 10px;">
                    ภาพถ่ายประกอบการนิเทศ (ถ้ามี - รองรับสูงสุด 2 ภาพ)
                </h4>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div
                        style="flex: 1; min-width: 250px; padding: 15px; background: #f9f9f9; border-radius: 8px; border: 1px dashed #ccc; text-align: center;">
                        <label for="evaluation_photo_1"
                            style="display: block; margin-bottom: 10px; font-weight: bold; cursor: pointer;">
                            <i class="fas fa-camera"
                                style="font-size: 24px; color: #E94057; margin-bottom: 10px; display: block;"></i>
                            ภาพที่ 1
                        </label>
                        <input type="file" name="evaluation_photo_1" id="evaluation_photo_1" accept="image/*"
                            capture="environment" style="display: block; margin: 0 auto; max-width: 100%;">
                    </div>
                    <div
                        style="flex: 1; min-width: 250px; padding: 15px; background: #f9f9f9; border-radius: 8px; border: 1px dashed #ccc; text-align: center;">
                        <label for="evaluation_photo_2"
                            style="display: block; margin-bottom: 10px; font-weight: bold; cursor: pointer;">
                            <i class="fas fa-camera"
                                style="font-size: 24px; color: #3498db; margin-bottom: 10px; display: block;"></i>
                            ภาพที่ 2
                        </label>
                        <input type="file" name="evaluation_photo_2" id="evaluation_photo_2" accept="image/*"
                            capture="environment" style="display: block; margin: 0 auto; max-width: 100%;">
                    </div>
                </div>
                <p style="font-size: 13px; color: #666; margin-top: 10px; text-align: center;">รองรับเฉพาะไฟล์รูปภาพ (JPG,
                    PNG) เท่านั้น</p>
            </div>

            <!-- Signature block -->
            <div style="margin-bottom: 30px; background-color: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee;">
                <h4 style="margin-top: 0; color: #333;"><i class="fas fa-signature"></i> ลายมือชื่อผู้นิเทศ</h4>
                
                <input type="hidden" name="signature_base64" id="signatureBase64">
                
                <div style="text-align: center; margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 10px;">วาดลายเซ็นที่นี่:</label>
                    <canvas id="signaturePad" width="400" height="200" style="border: 2px dashed #007bff; border-radius: 8px; background: #fff; touch-action: none; cursor: crosshair; max-width: 100%;"></canvas>
                    <br>
                    <button type="button" class="btn btn-sm" style="margin-top: 10px; background-color: #f8f9fa; border: 1px solid #ddd;" onclick="clearSignature()"><i class="fas fa-eraser"></i> ล้างกระดาน (Clear)</button>
                </div>
                
                <div style="text-align: center; margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">หรืออัปโหลดไฟล์รูปลายเซ็น:</label>
                    <input type="file" name="signature_file" accept="image/*" class="form-control" style="max-width: 400px; margin: 0 auto;">
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px; border-top: 2px solid #eee; padding-top: 20px;">
                <button type="submit" class="btn-gradient" style="padding: 12px 30px; font-size: 16px;"><i
                        class="fas fa-save"></i> บันทึกผลการประเมิน</button>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
    // Small script to update the header max score display dynamically based on first item (assuming all in category share max_score usually)
    document.querySelectorAll('.radio-group').forEach(group => {
        let th = group.closest('table').querySelector('thead th:nth-child(2) span');
        if (th && th.innerText === '0') {
            th.innerText = group.dataset.maxscore;
        }
    });

    // Setup Signature Pad
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

        canvas.addEventListener('touchstart', startDrawing, {passive: false});
        canvas.addEventListener('touchmove', draw, {passive: false});
        canvas.addEventListener('touchend', stopDrawing, {passive: false});
    }

    function clearSignature() {
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = "white";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            document.getElementById('signatureBase64').value = '';
        }
    }

    document.getElementById('evalForm').addEventListener('submit', function (e) {
        e.preventDefault();

        // Auto-scroll to first invalid element if unselected (Handled by standard HTML5 required, but just in case)
        
        const sigBase64 = document.getElementById('signatureBase64') ? document.getElementById('signatureBase64').value : '';
        const sigFile = document.querySelector('input[name="signature_file"]') ? document.querySelector('input[name="signature_file"]').value : '';

        if (!sigBase64 && !sigFile) {
            Swal.fire('กรุณาลงลายมือชื่อ', 'โปรดวาดลายเซ็นบนกระดาน หรืออัปโหลดรูปลายเซ็น ก่อนบันทึกผลการนิเทศ', 'warning');
            return;
        }

        Swal.fire({
            title: 'ยืนยันการบันทึกผล?',
            text: "เมื่อบันทึกแล้ว ระบบจะถือว่ากระบวนการนิเทศเสร็จสิ้น ไม่สามารถกลับมาแก้ไขได้",
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

                fetch('evaluate_action.php', {
                    method: 'POST', body: formData
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success', title: 'ประเมินผลสำเร็จ!', showConfirmButton: false, timer: 1500
                            }).then(() => window.location = 'calendar.php');
                        } else {
                            Swal.fire('ข้อผิดพลาด', data.message, 'error');
                        }
                    })
                    .catch(() => Swal.fire('ข้อผิดพลาด', 'ติดต่อเซิร์ฟเวอร์ไม่ได้', 'error'));
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>