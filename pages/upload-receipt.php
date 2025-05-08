<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php#login");
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['record_id']) || !isset($_POST['payment_type'])) {
    $_SESSION['upload_error'] = "Invalid request.";
    header("Location: dashboard.php");
    exit();
}

$record_id = $_POST['record_id'];
$payment_type = $_POST['payment_type'];
$user_id = $_SESSION['user_id'];

require "../APIs/connect.php";

// Verify that the financial record belongs to the user
$verify_query = "SELECT f.id, r.user_id FROM financial_records f 
                JOIN reservations r ON f.reservation_id = r.id 
                WHERE f.id = ? AND r.user_id = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("ii", $record_id, $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    $_SESSION['upload_error'] = "You don't have permission to upload a receipt for this record.";
    header("Location: dashboard.php");
    exit();
}

$verify_stmt->close();

// Check if file was uploaded without errors
if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jfif', 'application/pdf'];
    $file_type = $_FILES['receipt']['type'];

    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['upload_error'] = "Only JPG, PNG, GIF, and PDF files are allowed.";
        header("Location: dashboard.php");
        exit();
    }

    $max_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES['receipt']['size'] > $max_size) {
        $_SESSION['upload_error'] = "File size must be less than 5MB.";
        header("Location: dashboard.php");
        exit();
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = '../admin/uploads/receipts/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate a unique filename
    $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid('receipt_') . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;

    // Move the uploaded file
    if (move_uploaded_file($_FILES['receipt']['tmp_name'], $upload_path)) {
        // Update the financial record
        if ($payment_type === 'deposit') {
            $update_query = "UPDATE financial_records SET deposit_receipt = ? WHERE id = ?";
        } else {
            $update_query = "UPDATE financial_records SET full_payment_receipt = ? WHERE id = ?";
        }

        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_filename, $record_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Get the reservation ID for redirection
        $reservation_query = "SELECT reservation_id FROM financial_records WHERE id = ?";
        $reservation_stmt = $conn->prepare($reservation_query);
        $reservation_stmt->bind_param("i", $record_id);
        $reservation_stmt->execute();
        $reservation_result = $reservation_stmt->get_result();
        $reservation_id = $reservation_result->fetch_assoc()['reservation_id'];
        $reservation_stmt->close();

        $_SESSION['upload_success'] = "Receipt uploaded successfully. It will be reviewed by our team.";
        header("Location: event-details.php?id=" . $reservation_id);
        exit();
    } else {
        $_SESSION['upload_error'] = "Failed to upload file. Please try again.";
        header("Location: dashboard.php");
        exit();
    }
} else {
    $_SESSION['upload_error'] = "Please select a file to upload.";
    header("Location: dashboard.php");
    exit();
}

$conn->close();
