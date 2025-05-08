<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Check if admin is super_admin
if ($_SESSION['admin_role'] !== 'super_admin') {
    header("Location: admin-dashboard.php");
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['record_id']) || !isset($_POST['payment_type']) || !isset($_POST['decline_reason'])) {
    header("Location: financials.php");
    exit();
}

$record_id = $_POST['record_id'];
$payment_type = $_POST['payment_type'];
$reservation_id = $_POST['reservation_id'];
$decline_reason = trim($_POST['decline_reason']);

// Validate inputs
if (empty($decline_reason)) {
    header("Location: financial-details.php?id=" . $record_id . "&error=reason_required");
    exit();
}

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];

require "../../APIs/connect.php";

// Get the current receipt filename
$query = "SELECT " . ($payment_type === 'deposit' ? 'deposit_receipt' : 'full_payment_receipt') . " AS receipt_filename, 
          (SELECT user_id FROM reservations WHERE id = financial_records.reservation_id) AS user_id 
          FROM financial_records WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: financials.php");
    exit();
}

$row = $result->fetch_assoc();
$receipt_filename = $row['receipt_filename'];
$user_id = $row['user_id'];
$stmt->close();

// Delete the receipt file if it exists
if (!empty($receipt_filename)) {
    $file_path = "uploads/receipts/" . $receipt_filename;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Update the financial record
if ($payment_type === 'deposit') {
    $update_query = "UPDATE financial_records SET deposit_receipt = NULL WHERE id = ?";
} else {
    $update_query = "UPDATE financial_records SET full_payment_receipt = NULL WHERE id = ?";
}

$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("i", $record_id);
$update_stmt->execute();
$update_stmt->close();

// Create notification for user
$notification_type = $payment_type === 'deposit' ? 'deposit_declined' : 'full_payment_declined';
$notification_message = "Your " . ($payment_type === 'deposit' ? 'deposit' : 'full payment') . " receipt for reservation #" . $reservation_id . " has been declined. Reason: " . $decline_reason;

$notification_query = "INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)";
$notification_stmt = $conn->prepare($notification_query);
$notification_stmt->bind_param("iss", $user_id, $notification_type, $notification_message);
$notification_stmt->execute();
$notification_stmt->close();

// Log admin activity
$action_details = "Declined " . $payment_type . " receipt for financial record #" . $record_id . ". Reason: " . $decline_reason;
$log_query = "INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'payment_decline', ?, ?)";
$log_stmt = $conn->prepare($log_query);
$ip_address = $_SERVER['REMOTE_ADDR'];
$log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
$log_stmt->execute();
$log_stmt->close();

$conn->close();

// Redirect back to the financial details page
header("Location: financial-details.php?id=" . $record_id . "&declined=1");
exit();
