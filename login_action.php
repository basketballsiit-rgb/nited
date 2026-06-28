<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อผู้ใช้งานและรหัสผ่าน']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, username, password, name, role FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Determine redirect path
            $redirect_url = '';
            switch ($user['role']) {
                case 'admin':
                    $redirect_url = 'admin/dashboard.php';
                    break;
                case 'teacher':
                    $redirect_url = 'teacher/dashboard.php';
                    break;
                case 'supervisor':
                    $redirect_url = 'supervisor/dashboard.php';
                    break;
                case 'executive':
                    $redirect_url = 'executive/dashboard.php';
                    break;
            }

            echo json_encode([
                'status' => 'success',
                'redirect' => $redirect_url,
                'role' => $user['role']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดของระบบฐานข้อมูล']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>