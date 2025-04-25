<?php
session_start();

require "connect.php";

// Process login form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id, name, email, password, phone, blocked FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Check if user is blocked
        if ($user['blocked']) {
            $error_message = "Your account has been blocked. Please contact support.";
        } elseif ($password === $user['password']) {
            // User is not blocked and password is correct
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_phone'] = isset($user['phone']) ? $user['phone'] : '';

            // Check if there's a redirect URL stored in the session
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
                exit();
            } else {
                // Redirect to user dashboard
                header("Location: ../pages/dashboard.php");
                exit();
            }
        } else {
            // Password is incorrect
            $error_message = "Invalid email or password";
        }
    } else {
        // User not found
        $error_message = "Invalid email or password";
    }

    $stmt->close();
}

$conn->close();

// Redirect back to login with error
if (isset($error_message)) {
    $_SESSION['login_error'] = $error_message;
    header("Location: ../pages/index.php");
    exit();
}
