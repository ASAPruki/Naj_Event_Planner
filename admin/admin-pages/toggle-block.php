<?php
session_start();
require "../../APIs/connect.php";

if (!isset($_SESSION['admin_id']) || !isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: users.php");
    exit();
}

$user_id = intval($_GET['id']);
$action = $_GET['action'];

if ($action === 'block') {
    $stmt = $conn->prepare("UPDATE users SET blocked = 1 WHERE id = ?");
} elseif ($action === 'unblock') {
    $stmt = $conn->prepare("UPDATE users SET blocked = 0 WHERE id = ?");
} else {
    header("Location: users.php");
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Optionally log the action (for your admin activity log)
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$ip_address = $_SERVER['REMOTE_ADDR'];
$action_type = $action; // e.g., 'block' or 'unblock'
$log_action = ($action === "block") ? "Blocked" : "Unblocked";
$action_details = "$log_action user ID: $user_id";
$ip_address = $_SERVER['REMOTE_ADDR'];

$log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, ?, ?, ?)");
$log_stmt->bind_param("isss", $admin_id, $action_type, $action_details, $ip_address);
$log_stmt->execute();
$log_stmt->close();


$conn->close();

header("Location: users.php");
exit();
