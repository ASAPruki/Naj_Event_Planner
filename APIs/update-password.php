<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../pages/index.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (strlen($new_password) < 6) {
        $_SESSION['password_error'] = "New password must be at least 6 characters";
        header("Location: ../dashboard.php");
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['password_error'] = "New passwords do not match";
        header("Location: ../pages/dashboard.php");
        exit();
    }

    require "connect.php";

    // Get current password from database
    $password_query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($password_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $hashed_password, $user_id);

            if ($stmt->execute()) {
                $_SESSION['password_success'] = "Password updated successfully";
            } else {
                $_SESSION['password_error'] = "Error updating password: " . $stmt->error;
            }
        } else {
            $_SESSION['password_error'] = "Current password is incorrect";
        }
    } else {
        $_SESSION['password_error'] = "User not found";
    }

    $stmt->close();
    $conn->close();

    // Redirect back to dashboard
    header("Location: ../pages/dashboard.php");
    exit();
}
