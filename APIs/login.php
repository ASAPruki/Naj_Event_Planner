<?php
session_start();

require "connect.php";

// Process login form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password (plain text comparison)
        if ($password === $user['password']) {
            // Password is correct, start a new session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            // Redirect to user dashboard
            header("Location: ../pages/dashboard.php");
            exit();
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

// If there was an error, redirect back to the login page with error message
if (isset($error_message)) {
    $_SESSION['login_error'] = $error_message;
    header("Location: ../pages/index.html");
    exit();
}
