<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Prevent caching to avoid seeing pages after logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบนิเทศการจัดการเรียนการสอน</title>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/nited/assets/css/style.css">
    <link rel="stylesheet" href="/nited/assets/css/layout.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- FullCalendar -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
</head>

<body>
    <div class="wrapper">
        <?php include_once __DIR__ . '/sidebar.php'; ?>
        <div class="main-panel">
            <div class="app-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-bars" id="sidebarToggle" style="cursor: pointer; font-size: 20px;"></i>
                        <img src="/nited/assets/images/logo.png" alt="Logo"
                            style="width: 45px; height: 45px; object-fit: contain; filter: drop-shadow(0 0 5px rgba(255,255,255,0.5));">
                        <div>
                            <h2 style="margin: 0; font-size: 20px;">ระบบนิเทศการจัดการเรียนการสอน</h2>
                            <span style="font-size: 14px; opacity: 0.8;">วิทยาลัยสารพัดช่างน่าน โดย
                                งานพัฒนาหลักสูตรการเรียนการสอน</span>
                        </div>
                    </div>
                    <div class="user-info" style="display: flex; align-items: center; gap: 20px;">
                        
                        <!-- Notification Bell -->
                        <div class="notification-wrapper" style="position: relative;">
                            <a href="#" id="notificationBell" style="color: white; font-size: 20px; text-decoration: none; position: relative;">
                                <i class="fas fa-bell"></i>
                                <span id="notificationBadge" style="display: none; position: absolute; top: -8px; right: -8px; background: #E94057; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: bold;">0</span>
                            </a>
                            <div id="notificationDropdown" class="notification-dropdown" style="display: none; position: absolute; top: 35px; right: 0; width: 350px; background: white; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000; overflow: hidden;">
                                <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa;">
                                    <h4 style="margin: 0; color: #333; font-size: 16px;">การแจ้งเตือน</h4>
                                    <button id="markAllReadBtn" style="background: none; border: none; color: #0d6efd; cursor: pointer; font-size: 12px;">อ่านทั้งหมด</button>
                                </div>
                                <div id="notificationList" style="max-height: 400px; overflow-y: auto; padding: 0; margin: 0; list-style: none;">
                                    <div style="padding: 20px; text-align: center; color: #888; font-size: 14px;">ไม่มีการแจ้งเตือนใหม่</div>
                                </div>
                            </div>
                        </div>

                        <span><i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['name'] ?? 'ผู้ใช้งาน'); ?>
                            (
                            <?php echo htmlspecialchars(ucfirst($_SESSION['role'] ?? '')); ?>)
                        </span>
                        <a href="/nited/logout.php" class="btn-logout"
                            style="color: white; text-decoration: none;"><i
                                class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const bell = document.getElementById('notificationBell');
                    const dropdown = document.getElementById('notificationDropdown');
                    const badge = document.getElementById('notificationBadge');
                    const list = document.getElementById('notificationList');
                    const markAllBtn = document.getElementById('markAllReadBtn');

                    // Toggle dropdown
                    bell.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (dropdown.style.display === 'none') {
                            dropdown.style.display = 'block';
                            fetchNotifications();
                        } else {
                            dropdown.style.display = 'none';
                        }
                    });

                    // Close dropdown when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
                            dropdown.style.display = 'none';
                        }
                    });

                    function fetchNotifications() {
                        fetch('/nited/api/notifications_api.php?action=fetch')
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    // Update badge
                                    if (data.unread_count > 0) {
                                        badge.style.display = 'inline-block';
                                        badge.innerText = data.unread_count > 99 ? '99+' : data.unread_count;
                                    } else {
                                        badge.style.display = 'none';
                                    }

                                    // Render list
                                    list.innerHTML = '';
                                    if (data.notifications.length === 0) {
                                        list.innerHTML = '<div style="padding: 20px; text-align: center; color: #888; font-size: 14px;">ไม่มีการแจ้งเตือนใหม่</div>';
                                    } else {
                                        data.notifications.forEach(notif => {
                                            const bg = notif.is_read == 1 ? '#ffffff' : '#f0f7ff';
                                            const fontWeight = notif.is_read == 1 ? 'normal' : 'bold';
                                            
                                            // Formatting time relative
                                            const date = new Date(notif.created_at);
                                            const now = new Date();
                                            const diffMs = now - date;
                                            const diffMins = Math.floor(diffMs / 60000);
                                            let timeStr = '';
                                            if (diffMins < 60) timeStr = diffMins + ' นาทีที่แล้ว';
                                            else if (diffMins < 1440) timeStr = Math.floor(diffMins/60) + ' ชั่วโมงที่แล้ว';
                                            else timeStr = Math.floor(diffMins/1440) + ' วันที่แล้ว';

                                            const itemHTML = `
                                                <div class="notif-item" style="padding: 12px 15px; border-bottom: 1px solid #eee; background: ${bg}; position: relative; transition: background 0.2s;">
                                                    <a href="${notif.link}" onclick="markAsRead(${notif.id}, this)" style="text-decoration: none; display: block; color: #333; padding-right: 25px;">
                                                        <div style="font-weight: ${fontWeight}; font-size: 14px; margin-bottom: 4px; color: #2c3e50;">${notif.title}</div>
                                                        <div style="font-size: 12px; color: #666; margin-bottom: 6px; line-height: 1.4;">${notif.message}</div>
                                                        <div style="font-size: 10px; color: #aaa;"><i class="far fa-clock"></i> ${timeStr}</div>
                                                    </a>
                                                    <button onclick="deleteNotification(${notif.id})" title="ลบการแจ้งเตือนนี้" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #ccc; cursor: pointer; padding: 5px;">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            `;
                                            list.insertAdjacentHTML('beforeend', itemHTML);
                                        });
                                    }
                                }
                            });
                    }

                    // Initial fetch to get unread count
                    fetchNotifications();

                    // Mark all read
                    markAllBtn.addEventListener('click', function() {
                        fetch('/nited/api/notifications_api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=mark_all_read'
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                fetchNotifications();
                            }
                        });
                    });

                    // Expose functions to global scope
                    window.markAsRead = function(id, el) {
                        fetch('/nited/api/notifications_api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=mark_read&id=' + id
                        });
                        // Allow navigation to proceed naturally
                    };

                    window.deleteNotification = function(id) {
                        if(confirm('ต้องการลบการแจ้งเตือนนี้ใช่หรือไม่?')) {
                            fetch('/nited/api/notifications_api.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'action=delete&id=' + id
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    fetchNotifications();
                                }
                            });
                        }
                    };
                });
            </script>

            <!-- Navbar Sub-menu depending on role (Optional, using Sidebar mainly) -->
            <!-- <div class="app-navbar"> ... </div> -->

            <div class="content-container">