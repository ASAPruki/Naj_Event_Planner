<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['image_id']) || !isset($_POST['accessory_id'])) {
    header("Location: accessories.php");
    exit();
}

$image_id = (int)$_POST['image_id'];
$accessory_id = (int)$_POST['accessory_id'];

require "../../APIs/connect.php";

// Get image path before deleting
$path_stmt = $conn->prepare("SELECT image_url FROM accessory_images WHERE id = ? AND accessory_id = ?");
$path_stmt->bind_param("ii", $image_id, $accessory_id);
$path_stmt->execute();
$path_result = $path_stmt->get_result();

if ($path_result->num_rows === 1) {
    $image_data = $path_result->fetch_assoc();
    $image_url = "../uploads/accessories/" . $image_data['image_url'];

    // Delete from database
    $delete_stmt = $conn->prepare("DELETE FROM accessory_images WHERE id = ? AND accessory_id = ?");
    $delete_stmt->bind_param("ii", $image_id, $accessory_id);

    if ($delete_stmt->execute()) {
        // Delete file from server if it exists
        if (file_exists($image_url)) {
            unlink($image_url);
        }

        // Log the activity
        $admin_id = $_SESSION['admin_id'];
        $action_details = "Admin deleted image (ID: $image_id) from accessory (ID: $accessory_id)";
        $ip_address = $_SERVER['REMOTE_ADDR'];

        $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'delete_image', ?, ?)");
        $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
        $log_stmt->execute();
        $log_stmt->close();

        header("Location: view-accessory.php?id=$accessory_id&success=Image deleted successfully");
    } else {
        header("Location: view-accessory.php?id=$accessory_id&error=Failed to delete image from database");
    }

    $delete_stmt->close();
} else {
    header("Location: view-accessory.php?id=$accessory_id&error=Image not found");
}

$path_stmt->close();
$conn->close();
exit();
