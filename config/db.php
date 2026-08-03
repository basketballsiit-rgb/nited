<?php
// config/db.php
date_default_timezone_set('Asia/Bangkok');

$host = 'localhost';
$dbname = 'supervision_db';
$username = 'root'; // default XAMPP user
$password = '';     // default XAMPP password

try {
    // Initial connection without dbname in case the database doesn't exist yet
    $pdo_setup = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo_setup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Note: the actual DB creation is handled in setup_db.php, but we keep this file focused on connection 

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($e->getCode() == 1049) {
        // Database doesn't exist yet, which is fine during setup

        $pdo = null;
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}
?>