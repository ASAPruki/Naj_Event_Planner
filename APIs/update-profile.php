<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../pages/index.html");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
    $address = isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '';

    // Validate input
    if (strlen($name) < 3) {
        $_SESSION['profile_error'] = "Name must be at least 3 characters";
        header("Location: ../pages/dashboard.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['profile_error'] = "Invalid email format";
        header("Location: ../pages/dashboard.php");
        exit();
    }

    require "connect.php";

    // Check if email already exists for another user
    $check_email_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['profile_error'] = "Email already in use by another account";
        header("Location: ../pages/dashboard.php");
        exit();
    }

    $stmt->close();

    // Update user profile
    $update_query = "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $name, $email, $phone, $address, $user_id);

    if ($stmt->execute()) {
        // Update session variables
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        $_SESSION['profile_success'] = "Profile updated successfully";
    } else {
        $_SESSION['profile_error'] = "Error updating profile: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    // Redirect back to dashboard
    header("Location: ../pages/dashboard.php");
    exit();
}
