<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if notification ID is provided
if (!isset($_POST['notification_id']) || !is_numeric($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

$notification_id = (int)$_POST['notification_id'];
$user_id = $_SESSION['user_id'];

// Database connection parameters
$host = "localhost";
$username = "root";
$password = "99Vm6tBhw";
$database = "najevents_db";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Update notification to mark as read
$query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $notification_id, $user_id);
$stmt->execute();

// Check if update was successful
if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}

$stmt->close();
$conn->close();
