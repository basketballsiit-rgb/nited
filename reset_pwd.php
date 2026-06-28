<?php
require 'config/db.php';
try {
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE username = 'admin'");
    $stmt->execute(['password' => password_hash('123456', PASSWORD_DEFAULT)]);
    echo 'Password reset successfully to: 123456';
    // Delete this file after running for security
    @unlink(__FILE__);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
