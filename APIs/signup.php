<?php
session_start();

require "connect.php";

// Process signup form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    $errors = array();

    if (strlen($name) < 3) {
        $errors[] = "Name must be at least 3 characters";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errors[] = "Email already exists";
    }

    // Check if phone number already exists
    $stmt2 = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt2->bind_param("s", $phone);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($result2->num_rows > 0) {
        $errors[] = "Phone number already exists";
    }

    $stmt->close();
    $stmt2->close();

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Store the password as plain text
        $plain_password = $password;

        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $name, $email, $plain_password);

        // Execute the statement
        if ($stmt->execute()) {
            // Get the new user ID
            $user_id = $stmt->insert_id;

            // Start a new session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            // Redirect to user dashboard
            header("Location: ../pages/dashboard.php");
            exit();
        } else {
            $errors[] = "Registration failed: " . $stmt->error;
        }

        $stmt->close();
    }

    // If there were errors, store them in session and redirect back to signup page
    if (!empty($errors)) {
        $_SESSION['signup_errors'] = $errors;
        header("Location: ../pages/index.php");
        exit();
    }
}

$conn->close();
