<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['accessory_id']) || !isset($_FILES['accessory_images'])) {
    header("Location: accessories.php");
    exit();
}

$accessory_id = (int)$_POST['accessory_id'];

require "../../APIs/connect.php";

// Verify accessory exists
$check_stmt = $conn->prepare("SELECT id, name FROM accessories_inventory WHERE id = ?");
$check_stmt->bind_param("i", $accessory_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $check_stmt->close();
    $conn->close();
    header("Location: accessories.php");
    exit();
}

$accessory = $check_result->fetch_assoc();
$check_stmt->close();

// Create directory if it doesn't exist
$upload_dir = "../uploads/accessories/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Process each uploaded file
$uploaded_files = 0;
$errors = [];

// Get admin information for logging
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$ip_address = $_SERVER['REMOTE_ADDR'];

// Check if accessory_images table exists, create if not
$table_check = $conn->query("SHOW TABLES LIKE 'accessory_images'");
if ($table_check->num_rows == 0) {
    $create_table_sql = "CREATE TABLE accessory_images (
        id INT(11) NOT NULL AUTO_INCREMENT,
        accessory_id INT(11) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY accessory_id (accessory_id)
    )";
    $conn->query($create_table_sql);
}

foreach ($_FILES['accessory_images']['tmp_name'] as $key => $tmp_name) {
    if ($_FILES['accessory_images']['error'][$key] === 0) {
        $file_name = $_FILES['accessory_images']['name'][$key];
        $file_size = $_FILES['accessory_images']['size'][$key];
        $file_tmp = $_FILES['accessory_images']['tmp_name'][$key];
        $file_type = $_FILES['accessory_images']['type'][$key];

        // Generate unique filename
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $unique_name = 'accessory_' . $accessory_id . '_' . uniqid() . '.' . $file_ext;
        $upload_path = $upload_dir . $unique_name;
        $db_path = "uploads/accessories/" . $unique_name;

        // Check file extension
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];
        if (!in_array($file_ext, $allowed_extensions)) {
            $errors[] = "File $file_name has an invalid extension. Only JPG, JPEG, PNG, JFIF, and GIF are allowed.";
            continue;
        }

        // Check file size (5MB max)
        if ($file_size > 5 * 1024 * 1024) {
            $errors[] = "File $file_name is too large. Maximum size is 5MB.";
            continue;
        }

        // Upload file
        if (move_uploaded_file($file_tmp, $upload_path)) {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO accessory_images (accessory_id, image_path) VALUES (?, ?)");
            $stmt->bind_param("is", $accessory_id, $db_path);

            if ($stmt->execute()) {
                $uploaded_files++;

                // Log the activity
                $action_details = "Admin uploaded image for accessory: " . $accessory['name'] . " (ID: " . $accessory_id . ")";
                $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'upload_image', ?, ?)");
                $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
                $log_stmt->execute();
                $log_stmt->close();
            } else {
                $errors[] = "Database error for file $file_name: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $errors[] = "Failed to upload file $file_name.";
        }
    } else {
        $errors[] = "Error code " . $_FILES['accessory_images']['error'][$key] . " for file " . $_FILES['accessory_images']['name'][$key];
    }
}

$conn->close();

// Redirect with appropriate message
if ($uploaded_files > 0) {
    $success_message = $uploaded_files . " image" . ($uploaded_files > 1 ? "s" : "") . " uploaded successfully.";
    header("Location: view-accessory.php?id=$accessory_id&success=" . urlencode($success_message));
} else {
    $error_message = "No images were uploaded. " . implode(" ", $errors);
    header("Location: view-accessory.php?id=$accessory_id&error=" . urlencode($error_message));
}
exit();
