<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin');
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add_cat') {
            $title = trim($_POST['title']);
            $order_idx = intval($_POST['order_idx']);
            $stmt = $pdo->prepare("INSERT INTO lp_criteria_categories (title, order_idx) VALUES (?, ?)");
            $stmt->execute([$title, $order_idx]);
            echo json_encode(['status' => 'success']);
        }
        elseif ($action === 'edit_cat') {
            $id = intval($_POST['id']);
            $title = trim($_POST['title']);
            $order_idx = intval($_POST['order_idx']);
            $stmt = $pdo->prepare("UPDATE lp_criteria_categories SET title = ?, order_idx = ? WHERE id = ?");
            $stmt->execute([$title, $order_idx, $id]);
            echo json_encode(['status' => 'success']);
        }
        elseif ($action === 'delete_cat') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM lp_criteria_categories WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
        }
        elseif ($action === 'add_item') {
            $cat_id = intval($_POST['category_id']);
            $is_header = isset($_POST['is_header']) ? 1 : 0;
            $is_optional = isset($_POST['is_optional']) ? 1 : 0;
            $desc = trim($_POST['description']);
            $indicator = trim($_POST['indicator'] ?? '');
            $max_score = intval($_POST['max_score']);
            $order_idx = intval($_POST['order_idx']);
            
            $stmt = $pdo->prepare("INSERT INTO lp_criteria_items (category_id, is_header, is_optional, description, indicator, max_score, order_idx) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cat_id, $is_header, $is_optional, $desc, $indicator, $max_score, $order_idx]);
            echo json_encode(['status' => 'success']);
        }
        elseif ($action === 'edit_item') {
            $id = intval($_POST['id']);
            $is_header = isset($_POST['is_header']) ? 1 : 0;
            $is_optional = isset($_POST['is_optional']) ? 1 : 0;
            $desc = trim($_POST['description']);
            $indicator = trim($_POST['indicator'] ?? '');
            $max_score = intval($_POST['max_score']);
            $order_idx = intval($_POST['order_idx']);
            
            $stmt = $pdo->prepare("UPDATE lp_criteria_items SET is_header = ?, is_optional = ?, description = ?, indicator = ?, max_score = ?, order_idx = ? WHERE id = ?");
            $stmt->execute([$is_header, $is_optional, $desc, $indicator, $max_score, $order_idx, $id]);
            echo json_encode(['status' => 'success']);
        }
        elseif ($action === 'delete_item') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM lp_criteria_items WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
        }
        else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
}
?>
