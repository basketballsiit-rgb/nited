<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT id, description, is_header, is_optional FROM lp_criteria_items WHERE is_header = 1");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
?>
