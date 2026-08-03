<?php
// includes/sidebar.php
$role = $_SESSION['role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>


<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <h3 style="margin:0; color:white; text-align:center; padding: 20px;">เมนูหลัก</h3>
    </div>
    <ul class="nav-links">
        <?php if ($role === 'admin'): ?>
            <li><a href="/nited/admin/dashboard.php"
                    class="<?php echo ($current_dir == 'admin' && $current_page == 'dashboard.php') ? 'active' : ''; ?>"><i
                        class="fas fa-home"></i> แดชบอร์ด (Admin)</a></li>
            <li><a href="/nited/admin/users.php"
                    class="<?php echo ($current_dir == 'admin' && $current_page == 'users.php') ? 'active' : ''; ?>"><i
                        class="fas fa-users"></i> จัดการผู้ใช้งานรายบุคคล</a></li>
            <li><a href="/nited/admin/departments.php"
                    class="<?php echo ($current_dir == 'admin' && $current_page == 'departments.php') ? 'active' : ''; ?>"><i
                        class="fas fa-building"></i> จัดการสาขาวิชา</a></li>
            <li><a href="/nited/admin/criteria.php"
                    class="<?php echo ($current_dir == 'admin' && $current_page == 'criteria.php') ? 'active' : ''; ?>"><i
                        class="fas fa-list-ol"></i> จัดการหัวข้อการประเมินการสอน</a></li>
            <li><a href="/nited/admin/lp_criteria_manage.php"
                    class="<?php echo ($current_dir == 'admin' && $current_page == 'lp_criteria_manage.php') ? 'active' : ''; ?>"><i
                        class="fas fa-clipboard-check"></i> จัดการฟอร์มตรวจแผนฯ</a></li>
            <li><a href="/nited/admin/academic_years.php"
                    class="<?php echo ($current_dir == 'admin' && $current_page == 'academic_years.php') ? 'active' : ''; ?>"><i
                        class="fas fa-calendar-alt"></i> ปีการศึกษา</a></li>
            <li><a href="/nited/admin/supervisor_schedules.php"
                    class="<?php echo ($current_dir == 'admin' && $current_page == 'supervisor_schedules.php') ? 'active' : ''; ?>"><i
                        class="fas fa-calendar-day"></i> ตารางการนิเทศรายบุคคล</a></li>
            <li><a href="/nited/admin/manage_supervisions.php"
                    class="<?php echo ($current_dir == 'admin' && $current_page == 'manage_supervisions.php') ? 'active' : ''; ?>"><i
                        class="fas fa-calendar-times"></i> จัดการข้อมูลการจองนิเทศ</a></li>
            <li><a href="/nited/admin/teacher_reports.php"
                    class="<?php echo ($current_dir == 'admin' && $current_page == 'teacher_reports.php') ? 'active' : ''; ?>"><i
                        class="fas fa-file-pdf"></i> รายงานผลการนิเทศรายบุคคล</a></li>
            <li><a href="/nited/admin/manuals.php"
                    class="<?php echo ($current_dir == 'admin' && $current_page == 'manuals.php') ? 'active' : ''; ?>"><i
                        class="fas fa-book"></i> จัดการคู่มือการใช้งาน</a></li>
        <?php elseif ($role === 'teacher'): ?>
            <li><a href="/nited/teacher/dashboard.php"
                    class="<?php echo ($current_dir == 'teacher' && $current_page == 'dashboard.php') ? 'active' : ''; ?>"><i
                        class="fas fa-home"></i> แดชบอร์ด (ครู)</a></li>
            <li><a href="/nited/teacher/calendar.php"
                    class="<?php echo ($current_dir == 'teacher' && $current_page == 'calendar.php') ? 'active' : ''; ?>"><i
                        class="fas fa-calendar-plus"></i> จองวันนิเทศ</a></li>
            <li><a href="/nited/teacher/history.php"
                    class="<?php echo ($current_dir == 'teacher' && $current_page == 'history.php') ? 'active' : ''; ?>"><i
                        class="fas fa-history"></i> ประวัติการนิเทศ</a></li>
            <li class="sidebar-header"
                style="padding: 10px 15px; font-size: 12px; color: #aaa; text-transform: uppercase; margin-top: 10px;">
                แผนการจัดการเรียนรู้เต็มเล่ม</li>
            <li><a href="/nited/teacher/lesson_plans.php"
                    class="<?php echo ($current_dir == 'teacher' && $current_page == 'lesson_plans.php') ? 'active' : ''; ?>"><i
                        class="fas fa-file-upload"></i> ส่งแผนการจัดการเรียนรู้</a></li>
            <li class="sidebar-header"
                style="padding: 10px 15px; font-size: 12px; color: #aaa; text-transform: uppercase; margin-top: 10px;">
                อื่นๆ</li>
            <li><a href="/nited/teacher/manuals.php"
                    class="<?php echo ($current_dir == 'teacher' && $current_page == 'manuals.php') ? 'active' : ''; ?>"><i
                        class="fas fa-book"></i> คู่มือการใช้งาน</a></li>
        <?php elseif ($role === 'supervisor'): ?>
            <li><a href="/nited/supervisor/dashboard.php"
                    class="<?php echo ($current_dir == 'supervisor' && $current_page == 'dashboard.php') ? 'active' : ''; ?>"><i
                        class="fas fa-home"></i> แดชบอร์ด (กรรมการ)</a></li>
            <li><a href="/nited/supervisor/calendar.php"
                    class="<?php echo ($current_dir == 'supervisor' && $current_page == 'calendar.php') ? 'active' : ''; ?>"><i
                        class="fas fa-calendar-check"></i> จัดการคำขอนิเทศ</a></li>
            <li><a href="/nited/supervisor/evaluation_history.php"
                    class="<?php echo ($current_dir == 'supervisor' && $current_page == 'evaluation_history.php') ? 'active' : ''; ?>"><i
                        class="fas fa-edit"></i> ประวัติและแก้ไขผลนิเทศ</a></li>
            <li><a href="/nited/supervisor/lesson_plans_review.php"
                    class="<?php echo ($current_dir == 'supervisor' && $current_page == 'lesson_plans_review.php') ? 'active' : ''; ?>"><i
                        class="fas fa-file-signature"></i> ตรวจแผนการจัดการเรียนรู้</a></li>
            <li class="sidebar-header"
                style="padding: 10px 15px; font-size: 12px; color: #aaa; text-transform: uppercase; margin-top: 10px;">
                ในฐานะผู้รับการนิเทศ/ผู้สอน</li>
            <li><a href="/nited/teacher/calendar.php"
                    class="<?php echo ($current_dir == 'teacher' && $current_page == 'calendar.php') ? 'active' : ''; ?>"><i
                        class="fas fa-calendar-plus"></i> จองวันรับการนิเทศ</a></li>
            <li><a href="/nited/teacher/history.php"
                    class="<?php echo ($current_dir == 'teacher' && $current_page == 'history.php') ? 'active' : ''; ?>"><i
                        class="fas fa-history"></i> ประวัติรับการนิเทศ</a></li>
            <li><a href="/nited/teacher/lesson_plans.php"
                    class="<?php echo ($current_dir == 'teacher' && $current_page == 'lesson_plans.php') ? 'active' : ''; ?>"><i
                        class="fas fa-file-upload"></i> ส่งแผนการจัดการเรียนรู้</a></li>
            <li class="sidebar-header"
                style="padding: 10px 15px; font-size: 12px; color: #aaa; text-transform: uppercase; margin-top: 10px;">
                อื่นๆ</li>
            <li><a href="/nited/supervisor/manuals.php"
                    class="<?php echo ($current_dir == 'supervisor' && $current_page == 'manuals.php') ? 'active' : ''; ?>"><i
                        class="fas fa-book"></i> คู่มือการใช้งาน</a></li>
        <?php elseif ($role === 'executive'): ?>
            <li><a href="/nited/executive/dashboard.php"
                    class="<?php echo ($current_dir == 'executive' && $current_page == 'dashboard.php') ? 'active' : ''; ?>"><i
                        class="fas fa-chart-pie"></i> แดชบอร์ดผู้บริหาร</a></li>
            <li><a href="/nited/executive/reports.php"
                    class="<?php echo ($current_dir == 'executive' && $current_page == 'reports.php') ? 'active' : ''; ?>"><i
                        class="fas fa-file-alt"></i> รายงานผลการนิเทศ</a></li>
            
            <?php 
            $user_pos = $_SESSION['position'] ?? '';
            if (strpos($user_pos, 'รองผู้อำนวยการ') !== false): 
            ?>
            <li class="sidebar-header"
                style="padding: 10px 15px; font-size: 12px; color: #aaa; text-transform: uppercase; margin-top: 10px; color: #ff9800;">
                <i class="fas fa-bolt"></i> เครื่องมือด่วน</li>
            <li><a href="/nited/executive/adhoc_supervision.php"
                    class="<?php echo ($current_dir == 'executive' && $current_page == 'adhoc_supervision.php') ? 'active' : ''; ?>" style="color: #ff9800;"><i
                        class="fas fa-walking"></i> นิเทศเร่งด่วน (Walk-in)</a></li>
            <?php endif; ?>

            <li class="sidebar-header"
                style="padding: 10px 15px; font-size: 12px; color: #aaa; text-transform: uppercase; margin-top: 10px;">
                การดำเนินการนิเทศและการตรวจ</li>
            <li><a href="/nited/supervisor/calendar.php"
                    class="<?php echo ($current_dir == 'supervisor' && $current_page == 'calendar.php') ? 'active' : ''; ?>"><i
                        class="fas fa-calendar-check"></i> จัดการคำขอนิเทศ</a></li>
            <li><a href="/nited/supervisor/lesson_plans_review.php"
                    class="<?php echo ($current_dir == 'supervisor' && $current_page == 'lesson_plans_review.php') ? 'active' : ''; ?>"><i
                        class="fas fa-file-signature"></i> ตรวจแผนการจัดการเรียนรู้</a></li>
            <li class="sidebar-header"
                style="padding: 10px 15px; font-size: 12px; color: #aaa; text-transform: uppercase; margin-top: 10px;">
                อื่นๆ</li>
            <li><a href="/nited/supervisor/manuals.php"
                    class="<?php echo ($current_dir == 'supervisor' && $current_page == 'manuals.php') ? 'active' : ''; ?>"><i
                        class="fas fa-book"></i> คู่มือการใช้งาน</a></li>
        <?php endif; ?>
    </ul>
</div>