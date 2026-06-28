<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'supervisor') {
    header("Location: /nited/index.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Get active academic year
$stmt = $pdo->query("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
$active_year = $stmt->fetch();
$year_id = $active_year ? $active_year['id'] : 0;

// Check valid supervision count for this user
$valid_supervision_count = 0;
if ($year_id > 0) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM supervisions 
        WHERE teacher_id = ? 
        AND academic_year_id = ? 
        AND status IN ('approved', 'completed')
    ");
    $stmt->execute([$_SESSION['user_id'], $year_id]);
    $valid_supervision_count = $stmt->fetchColumn();
}
?>

<div class="content-header" style="margin-bottom: 20px;">
    <h2><i class="fas fa-calendar-plus"></i> จองเวลาและการนิเทศ</h2>
    <p class="text-muted">คลิกที่ตารางเวลาเพื่อดู/จอง ช่วงเวลาสำหรับการนิเทศ</p>
</div>

<div class="content-card">
    <div id='calendar'></div>
</div>

<!-- Add Booking Modal -->
<div id="bookingModal" class="modal-overlay"
    style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1050;">
    <div class="modal-content"
        style="background: white; padding: 25px; border-radius: 10px; width: 100%; max-width: 500px;">
        <h3 style="margin-top: 0; margin-bottom: 20px;">จองการนิเทศการสอน</h3>
        <form id="bookingForm">
            <input type="hidden" name="action" value="book_slot">
            <input type="hidden" name="academic_year_id" value="<?php echo $year_id; ?>">
            <input type="hidden" name="start_datetime" id="start_datetime">

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">วันที่จอง</label>
                <input type="text" id="display_date" class="form-control" readonly style="background-color: #f8f9fa;">
            </div>

            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px;">เวลาเริ่มต้น <span
                            style="color:red">*</span></label>
                    <input type="time" id="start_time" name="start_time" class="form-control" required>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px;">เวลาสิ้นสุด <span
                            style="color:red">*</span></label>
                    <input type="time" id="end_time" name="end_time" class="form-control" required>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px;">รหัสวิชา <span style="color:red">*</span></label>
                    <input type="text" name="subject_code" class="form-control" placeholder="เช่น 20000-1401" required>
                </div>
                <div style="flex: 2;">
                    <label style="display: block; margin-bottom: 5px;">รายวิชาที่สอน <span style="color:red">*</span></label>
                    <input type="text" name="subject_name" class="form-control" placeholder="เช่น คณิตศาสตร์อุตสาหกรรม" required>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">ระดับชั้น <span style="color:red">*</span></label>
                <select name="level" class="form-control" required>
                    <option value="">-- เลือกระดับชั้น --</option>
                    <option value="ปวช. 1">ปวช. 1</option>
                    <option value="ปวช. 2">ปวช. 2</option>
                    <option value="ปวช. 3">ปวช. 3</option>
                    <option value="ปวส. 1">ปวส. 1</option>
                    <option value="ปวส. 2">ปวส. 2</option>
                    <option value="ปริญญาตรี">ปริญญาตรี</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">สาขาวิชาที่สอน <span style="color:red">*</span></label>
                <select name="teaching_department" class="form-control" required>
                    <option value="">-- เลือกสาขาวิชาที่สอน --</option>
                    <option value="การบัญชี">การบัญชี</option>
                    <option value="การตลาด">การตลาด</option>
                    <option value="อิเล็กทรอนิกส์">อิเล็กทรอนิกส์</option>
                    <option value="เทคนิคยานยนต์">เทคนิคยานยนต์</option>
                    <option value="ไฟฟ้ากำลัง">ไฟฟ้ากำลัง</option>
                    <option value="สารสนเทศ">สารสนเทศ</option>
                    <option value="ระยะสั้น">ระยะสั้น</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">ไฟล์แผนการสอน (ถ้ามี PDF, DOC, DOCX)</label>
                <input type="file" name="lesson_plan_file" accept=".pdf,.doc,.docx" class="form-control">
            </div>

            <div
                style="background-color: #eef2ff; border: 1px solid #c7d2fe; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 13px; color: #4f46e5;">
                <i class="fas fa-info-circle"></i> <strong>ระบบจะทำการสุ่มผู้นิเทศอัตโนมัติ</strong>
                <?php if ($_SESSION['role'] === 'supervisor'): ?>
                    จากผู้ที่มีบทบาทเป็น "ผู้บริหาร"
                <?php else: ?>
                    จาก "ผู้บริหาร" หรือ ผู้ที่มีวิทยฐานะ "ชำนาญการพิเศษ" / ตำแหน่ง "หัวหน้าสาขาวิชา"
                <?php endif; ?>
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="closeBookingModal()"
                    style="padding: 10px 15px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer; margin-right: 10px;">ยกเลิก</button>
                <button type="submit" class="btn-gradient" style="padding: 10px 20px;">ยืนยันการจอง</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'th',
            events: 'calendar_action.php?action=get_events', // Fetch my events
            selectable: true,
            select: function (info) {
                // Open modal to book a new slot
                if (<?php echo $year_id; ?> === 0) {
                    Swal.fire('ข้อผิดพลาด', 'ไม่พบปีการศึกษาที่กำลังใช้งาน (กรุณาติดต่อผู้ดูแลระบบ)', 'error');
                    return;
                }

                if (<?php echo $valid_supervision_count; ?> >= 1) {
                    Swal.fire('เกินกำหนดโควตา', 'คุณได้รับการอนุมัติวันนิเทศครบ 1 ครั้งในภาคเรียนนี้ตามเกณฑ์แล้ว ไม่สามารถจองเพิ่มได้อีก (ยกเว้นกรณีรองผู้อำนวยการฯ เดินเข้านิเทศเร่งด่วน)', 'warning');
                    return;
                }

                document.getElementById('bookingForm').reset();
                document.getElementById('start_datetime').value = info.startStr;

                // Format for display
                const d = new Date(info.startStr);
                document.getElementById('display_date').value = d.toLocaleDateString('th-TH', {
                    year: 'numeric', month: 'long', day: 'numeric'
                });

                if (info.view.type !== 'dayGridMonth') {
                    const startHour = String(d.getHours()).padStart(2, '0');
                    const startMin = String(d.getMinutes()).padStart(2, '0');
                    document.getElementById('start_time').value = `${startHour}:${startMin}`;

                    const dEnd = new Date(info.endStr);
                    const endHour = String(dEnd.getHours()).padStart(2, '0');
                    const endMin = String(dEnd.getMinutes()).padStart(2, '0');
                    document.getElementById('end_time').value = `${endHour}:${endMin}`;
                }

                document.getElementById('bookingModal').style.display = 'flex';
            },
            eventClick: function (info) {
                let fileLink = '';
                if (info.event.extendedProps.lesson_plan_file) {
                    fileLink = `<br><strong>แผนการสอน:</strong> <a href="/nited/${info.event.extendedProps.lesson_plan_file}" target="_blank">ดาวน์โหลดไฟล์</a>`;
                }

                Swal.fire({
                    title: 'รายละเอียดการนิเทศ',
                    html: `
                    <strong>วิชา:</strong> ${info.event.title}<br>
                    <strong>สถานะ:</strong> ${info.event.extendedProps.status_label}<br>
                    <strong>กรรมการ:</strong> ${info.event.extendedProps.supervisor_name}
                    ${fileLink}
                `,
                    icon: 'info'
                });
            }
        });
        calendar.render();
    });

    function closeBookingModal() {
        document.getElementById('bookingModal').style.display = 'none';
    }

    document.getElementById('bookingForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        Swal.fire({
            title: 'กำลังสุ่มและมอบหมายกรรมการ...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('calendar_action.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'จองสำเร็จ!',
                        text: 'ได้มอบหมายให้กรรมการ: ' + data.assigned_supervisor,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>