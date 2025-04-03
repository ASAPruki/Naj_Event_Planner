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

// Get event details to create notification
$event_query = "SELECT r.*, u.id as user_id FROM reservations r LEFT JOIN users u ON r.user_id = u.id WHERE r.id = ?";
$event_stmt = $conn->prepare($event_query);
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
$event = $event_result->fetch_assoc();
$event_stmt->close();

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

    // Create notification for user if user_id exists
    if (isset($event['user_id']) && !empty($event['user_id'])) {
        $user_id = $event['user_id'];
        $event_type = ucfirst($event['event_type']);
        $notification_type = '';
        $notification_message = '';

        // Set notification type and message based on status
        switch ($status) {
            case 'confirmed':
                $notification_type = 'success';
                $notification_message = "Your $event_type event on " . date('F d, Y', strtotime($event['event_date'])) . " has been confirmed.";
                break;
            case 'cancelled':
                $notification_type = 'danger';
                $notification_message = "Your $event_type event on " . date('F d, Y', strtotime($event['event_date'])) . " has been cancelled.";
                if (!empty($admin_notes)) {
                    $notification_message .= "\nReason: $admin_notes";
                }
                break;
            case 'completed':
                $notification_type = 'info';
                $notification_message = "Your $event_type event on " . date('F d, Y', strtotime($event['event_date'])) . " has been marked as completed.";
                break;
            default:
                $notification_type = 'warning';
                $notification_message = "The status of your $event_type event on " . date('F d, Y', strtotime($event['event_date'])) . " has been updated to $status.";
        }

        // Insert notification
        $notification_query = "INSERT INTO notifications (user_id, event_id, message, type) VALUES (?, ?, ?, ?)";
        $notification_stmt = $conn->prepare($notification_query);
        $notification_stmt->bind_param("iiss", $user_id, $event_id, $notification_message, $notification_type);
        $notification_stmt->execute();
        $notification_stmt->close();
    }

    // Redirect back to event details with success message
    header("Location: event-details.php?id=$event_id&status=updated");
} else {
    // Redirect back to event details with error message
    header("Location: event-details.php?id=$event_id&status=error");
}

$update_stmt->close();
$conn->close();
