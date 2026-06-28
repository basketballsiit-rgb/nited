<?php
// onboarding_action.php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['requires_onboarding'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized or no onboarding needed.']);
    exit;
}

require_once 'config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position = trim($_POST['position'] ?? '');
    $academic_standing = trim($_POST['academic_standing'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (empty($position)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลตำแหน่ง']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET position = ?, academic_standing = ?, department = ? WHERE id = ?");
        $stmt->execute([$position, $academic_standing, $department, $user_id]);

        // Update session
        $_SESSION['department'] = $department;
        unset($_SESSION['requires_onboarding']); // Clear the flag!

        // Determine redirect url based on role
        $redirect = 'index.php'; // fallback to index, which will redirectBasedOnRole()
        
        echo json_encode(['status' => 'success', 'redirect' => $redirect]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
