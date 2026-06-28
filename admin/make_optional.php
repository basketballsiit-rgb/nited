<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("UPDATE lp_criteria_items SET is_optional = 1 WHERE description LIKE '%กรมพัฒนาฝีมือแรงงาน%'");
echo "Updated to Optional: " . $stmt->rowCount() . " rows.";
?>
