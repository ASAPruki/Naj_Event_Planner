<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: events.php");
    exit();
}

// Get admin information
$admin_id = $_SESSION['admin_id'];

// Get form data
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';

// Validate data
if ($event_id <= 0 || !in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
    header("Location: events.php");
    exit();
}

require "../../APIs/connect.php";

// Update event status
$update_query = "UPDATE reservations SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("ssi", $status, $admin_notes, $event_id);

if ($update_stmt->execute()) {
    // Log admin activity
    $action_type = "update_status";
    $action_details = "Updated event #$event_id status to $status";
    if (!empty($admin_notes)) {
        $action_details .= " with notes: $admin_notes";
    }

    $log_query = "INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $log_stmt->bind_param("isss", $admin_id, $action_type, $action_details, $ip_address);
    $log_stmt->execute();
    $log_stmt->close();

    // Redirect back to event details with success message
    header("Location: event-details.php?id=$event_id&status=updated");
} else {
    // Redirect back to event details with error message
    header("Location: event-details.php?id=$event_id&status=error");
}

$update_stmt->close();
$conn->close();
