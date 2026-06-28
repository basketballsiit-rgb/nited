<?php
// includes/notification_helper.php

/**
 * Add a new notification
 *
 * @param PDO $pdo The PDO database connection
 * @param int $user_id The ID of the user receiving the notification
 * @param string $title Notification title
 * @param string $message Notification message body
 * @param string $link Optional link to redirect when clicked
 * @return bool True on success, false on failure
 */
function addNotification($pdo, $user_id, $title, $message, $link = '#') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, link) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $title, $message, $link]);
    } catch (PDOException $e) {
        error_log("Failed to add notification: " . $e->getMessage());
        return false;
    }
}
?>
