<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // Category Actions
        if ($action === 'create_cat') {
            $title = $_POST['title'];
            $weight = floatval($_POST['weight']);
            $stmt = $pdo->prepare("INSERT INTO criteria_categories (title, weight) VALUES (?, ?)");
            $stmt->execute([$title, $weight]);
            echo json_encode(['status' => 'success']);

        } elseif ($action === 'update_cat') {
            $id = $_POST['cat_id'];
            $title = $_POST['title'];
            $weight = floatval($_POST['weight']);
            $stmt = $pdo->prepare("UPDATE criteria_categories SET title=?, weight=? WHERE id=?");
            $stmt->execute([$title, $weight, $id]);
            echo json_encode(['status' => 'success']);

        } elseif ($action === 'delete_cat') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM criteria_categories WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);

            // Item Actions
        } elseif ($action === 'create_item') {
            $cat_id = $_POST['category_id'];
            $desc = $_POST['description'];
            $score = intval($_POST['max_score']);
            $stmt = $pdo->prepare("INSERT INTO criteria_items (category_id, description, max_score) VALUES (?, ?, ?)");
            $stmt->execute([$cat_id, $desc, $score]);
            echo json_encode(['status' => 'success']);

        } elseif ($action === 'update_item') {
            $id = $_POST['item_id'];
            $desc = $_POST['description'];
            $score = intval($_POST['max_score']);
            $stmt = $pdo->prepare("UPDATE criteria_items SET description=?, max_score=? WHERE id=?");
            $stmt->execute([$desc, $score, $id]);
            echo json_encode(['status' => 'success']);

        } elseif ($action === 'delete_item') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM criteria_items WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);

        } else {
            echo json_encode(['status' => 'error', 'message' => 'คำสั่งไม่ถูกต้อง']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Database Error: ' . $e->getMessage()]);
    }
}
?>