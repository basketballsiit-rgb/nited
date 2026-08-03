<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'executive') {
    header("Location: /nited/index.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$supervisor_id = $_SESSION['user_id'];
?>

<div class="content-header"
    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2><i class="fas fa-calendar-check"></i> จัดการคำขอนิเทศ</h2>
        <p class="text-muted">คลิกที่กิจกรรมในปฏิทินเพื่อยืนยัน ปฏิเสธ หรือประเมินผล</p>
    </div>
</div>

<div class="content-card">
    <div id='calendar'></div>
</div>

<!-- Manage Slot Modal -->
<div id="manageModal" class="modal-overlay"
    style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1050;">
    <div class="modal-content"
        style="background: white; padding: 25px; border-radius: 10px; width: 100%; max-width: 500px;">
        <h3 style="margin-top: 0; margin-bottom: 20px;">รายละเอียดการนิเทศ</h3>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <p><strong>ครูผู้สอน:</strong> <span id="m_teacher"></span></p>
            <p><strong>วิชา:</strong> <span id="m_subject"></span></p>
            <p><strong>วันเวลา:</strong> <span id="m_time"></span></p>
            <p><strong>สถานะปัจจุบัน:</strong> <span id="m_status" style="font-weight: bold;"></span></p>
            <p id="m_file_container" style="display: none;"><strong>แผนการสอน:</strong> <a id="m_file_link" href="#" target="_blank">ดาวน์โหลดไฟล์</a></p>
        </div>

        <div id="action-buttons" style="text-align: center; display: flex; gap: 10px; justify-content: center;">
            <!-- Buttons injected by JS based on status -->
        </div>

        <div style="text-align: right; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
            <button type="button" onclick="closeManageModal()"
                style="padding: 8px 15px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer;">ปิดหน้าต่าง</button>
        </div>
    </div>
</div>

<script>
    let currentEventId = null;

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
            events: 'sup_calendar_action.php?action=get_events', // fetch my tasks
            eventClick: function (info) {
                currentEventId = info.event.id;
                const props = info.event.extendedProps;

                document.getElementById('m_teacher').innerText = props.teacher_name;
                document.getElementById('m_subject').innerText = info.event.title;

                const start = info.event.start;
                const end = info.event.end;
                let timeStr = `${start.toLocaleDateString('th-TH')} (${start.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })}`;
                if (end) {
                    timeStr += ` - ${end.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })})`;
                } else {
                    timeStr += `)`;
                }
                document.getElementById('m_time').innerText = timeStr;

                const statusEl = document.getElementById('m_status');
                statusEl.innerText = props.status_label;

                if (props.lesson_plan_file) {
                    document.getElementById('m_file_link').href = "/nited/" + props.lesson_plan_file;
                    document.getElementById('m_file_container').style.display = 'block';
                } else {
                    document.getElementById('m_file_container').style.display = 'none';
                }

                const actionsDiv = document.getElementById('action-buttons');
                let btns = '';

                if (props.status === 'pending') {
                    statusEl.style.color = '#ffc107';
                    btns = `
                    <button onclick="updateStatus(${currentEventId}, 'approved')" style="flex:1; background: #198754; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;"><i class="fas fa-check"></i> ยืนยันรับการประเมิน</button>
                    <button onclick="updateStatus(${currentEventId}, 'rejected')" style="flex:1; background: #dc3545; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;"><i class="fas fa-times"></i> ปฏิเสธ/ไม่สะดวก</button>
                `;
                } else if (props.status === 'approved') {
                    statusEl.style.color = '#0d6efd';
                    btns = `
                    <a href="evaluate.php?id=${currentEventId}" style="flex:1; display:inline-block; background: #0d6efd; color: white; text-decoration: none; padding: 10px; border-radius: 5px; cursor: pointer;"><i class="fas fa-edit"></i> ดำเนินการประเมินผล</a>
                `;
                } else if (props.status === 'completed') {
                    statusEl.style.color = '#198754';
                    btns = `
                    <div style="width:100%; text-align:center; color: #198754; font-weight: bold; margin-bottom: 10px;"><i class="fas fa-check-circle"></i> การประเมินเสร็จสิ้นแล้ว</div>
                    <a href="evaluate.php?id=${currentEventId}" style="width:100%; display:inline-block; background: #6c757d; color: white; text-decoration: none; padding: 10px; border-radius: 5px; cursor: pointer; text-align: center;"><i class="fas fa-edit"></i> แก้ไขผลการประเมิน</a>
                `;
                } else if (props.status === 'rejected') {
                    statusEl.style.color = '#dc3545';
                    btns = `
                    <div style="width:100%; text-align:center; color: #dc3545; font-weight: bold;"><i class="fas fa-times-circle"></i> คุณได้ปฏิเสธคำขอนี้แล้ว</div>
                `;
                }

                actionsDiv.innerHTML = btns;
                document.getElementById('manageModal').style.display = 'flex';
            }
        });
        calendar.render();
    });

    function closeManageModal() {
        document.getElementById('manageModal').style.display = 'none';
    }

    function updateStatus(id, newStatus) {
        const actionText = newStatus === 'approved' ? 'ยืนยันการนัดหมาย' : 'ปฏิเสธการนัดหมาย';
        Swal.fire({
            title: actionText + ' ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: newStatus === 'approved' ? '#198754' : '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'ตกลง',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('id', id);
                formData.append('status', newStatus);

                fetch('sup_calendar_action.php', {
                    method: 'POST', body: formData
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1500, showConfirmButton: false })
                                .then(() => location.reload());
                        } else {
                            Swal.fire('ข้อผิดพลาด', data.message, 'error');
                        }
                    });
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>