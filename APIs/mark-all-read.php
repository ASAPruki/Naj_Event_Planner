<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

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

// Update all notifications to mark as read
$query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Check if update was successful
if ($stmt->affected_rows >= 0) {
    echo json_encode(['success' => true, 'count' => $stmt->affected_rows]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
}

$stmt->close();
$conn->close();
