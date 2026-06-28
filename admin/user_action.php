<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $name = $_POST['name'];
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'];
            $position = !empty($_POST['position']) ? $_POST['position'] : null;
            $academic_standing = !empty($_POST['academic_standing']) ? $_POST['academic_standing'] : null;
            $department = !empty($_POST['department']) ? $_POST['department'] : null;

            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, position, academic_standing, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $name, $role, $position, $academic_standing, $department]);

            echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลสำเร็จ']);

        } elseif ($action === 'update') {
            $id = $_POST['user_id'];
            $name = $_POST['name'];
            $username = $_POST['username'];
            $role = $_POST['role'];
            $position = !empty($_POST['position']) ? $_POST['position'] : null;
            $academic_standing = !empty($_POST['academic_standing']) ? $_POST['academic_standing'] : null;
            $department = !empty($_POST['department']) ? $_POST['department'] : null;

            // Check username conflict
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว']);
                exit;
            }

            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, name=?, role=?, position=?, academic_standing=?, department=? WHERE id=?");
                $stmt->execute([$username, $password, $name, $role, $position, $academic_standing, $department, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, name=?, role=?, position=?, academic_standing=?, department=? WHERE id=?");
                $stmt->execute([$username, $name, $role, $position, $academic_standing, $department, $id]);
            }

            echo json_encode(['status' => 'success', 'message' => 'อัปเดตข้อมูลสำเร็จ']);

        } elseif ($action === 'delete') {
            $id = $_POST['user_id'];
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบตัวเองได้']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลสำเร็จ']);

        } elseif ($action === 'import_excel') {
            $users_data_json = $_POST['users_data'] ?? '[]';
            $users_data = json_decode($users_data_json, true);

            if (!is_array($users_data) || empty($users_data)) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่มีข้อมูลที่นำเข้า']);
                exit;
            }

            $imported = 0;
            $errors = 0;

            foreach ($users_data as $row) {
                // Determine headers used in file. 
                $username = trim($row['username'] ?? $row['ชื่อผู้ใช้งาน'] ?? '');
                $name = trim($row['name'] ?? $row['ชื่อ-นามสกุล'] ?? $row['ชื่อ-สกุล'] ?? '');
                $raw_role = trim($row['role'] ?? $row['บทบาท'] ?? 'teacher');
                $position = trim($row['position'] ?? $row['ตำแหน่ง'] ?? '');
                $academic = trim($row['academic_standing'] ?? $row['วิทยฐานะ'] ?? '');
                $department = trim($row['department'] ?? $row['สาขาวิชา'] ?? '');
                $raw_password = trim($row['password'] ?? $row['รหัสผ่าน'] ?? '123456');

                // Required fields (we MUST have at least a Name)
                if (empty($name)) {
                    // Fallback in case they only put name in the 'username' column
                    if (!empty($username)) {
                        $name = $username;
                        $username = '';
                    } else {
                        $errors++;
                        continue;
                    }
                }

                // If username is empty, auto-generate a unique one
                if (empty($username)) {
                    do {
                        $username = 'usr_' . rand(10000, 99999);
                        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                        $stmt_check->execute([$username]);
                    } while ($stmt_check->rowCount() > 0);
                }

                // Map roles if Thai is used
                $role_map = [
                    'ครู' => 'teacher',
                    'กรรมการนิเทศ' => 'supervisor',
                    'ผู้บริหาร' => 'executive',
                    'ผู้ดูแลระบบ' => 'admin'
                ];
                $role = strtolower($raw_role);
                if (isset($role_map[$raw_role])) {
                    $role = $role_map[$raw_role];
                }
                if (!in_array($role, ['admin', 'teacher', 'supervisor', 'executive'])) {
                    $role = 'teacher';
                }

                // Check if exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->rowCount() > 0) {
                    $errors++;
                    continue; // Skip if exists
                }

                $password = password_hash($raw_password, PASSWORD_DEFAULT);

                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role, position, academic_standing, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $password, $name, $role, $position, $academic, $department]);
                    $imported++;
                } catch (Exception $e) {
                    $errors++;
                }
            }

            echo json_encode(['status' => 'success', 'imported' => $imported, 'errors' => $errors]);

        } else {
            echo json_encode(['status' => 'error', 'message' => 'คำสั่งไม่ถูกต้อง']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'ฐานข้อมูลผิดพลาด: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>