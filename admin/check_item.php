<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT * FROM lp_criteria_items WHERE description LIKE '%กรมพัฒนาฝีมือแรงงาน%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
?>
