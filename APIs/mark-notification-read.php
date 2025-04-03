<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get notification ID from POST data
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($data['id']) ? (int)$data['id'] : 0;

if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

require "connect.php";

// Get notification details to check if it belongs to the user and get event_id
$query = "SELECT n.*, r.id as event_id FROM notifications n LEFT JOIN reservations r ON n.event_id = r.id WHERE n.id = ? AND n.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $notification_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Notification not found or not authorized']);
    $stmt->close();
    $conn->close();
    exit();
}

$notification = $result->fetch_assoc();
$stmt->close();

// Mark notification as read
$update_query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("ii", $notification_id, $user_id);

if ($update_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Notification marked as read',
        'event_id' => $notification['event_id']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}

$update_stmt->close();
$conn->close();
