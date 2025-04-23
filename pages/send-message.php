<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: index.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $subject = isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '';
    $message = isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '';

    // Validate input
    $errors = array();

    if ($event_id <= 0) {
        $errors[] = "Invalid event ID";
    }

    if (empty($subject)) {
        $errors[] = "Subject is required";
    }

    if (empty($message)) {
        $errors[] = "Message is required";
    }

    // If no errors, proceed with sending the message
    if (empty($errors)) {

        require "../APIs/connect.php";

        // Verify that the event belongs to the user
        $event_query = "SELECT id FROM reservations WHERE id = ? AND email = ?";
        $stmt = $conn->prepare($event_query);
        $stmt->bind_param("is", $event_id, $user_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Event not found or doesn't belong to user
            $_SESSION['message_error'] = "Event not found or access denied";
            header("Location: dashboard.php");
            exit();
        }

        $stmt->close();

        // Insert message into database
        $insert_query = "INSERT INTO messages (reservation_id, user_id, subject, message, is_from_user, created_at) VALUES (?, ?, ?, ?, 1, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiss", $event_id, $user_id, $subject, $message);

        if ($stmt->execute()) {
            // Message sent successfully
            $_SESSION['message_success'] = "Your message has been sent successfully. Our team will respond shortly.";
        } else {
            // Error sending message
            $_SESSION['message_error'] = "Error sending message: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();

        // Redirect back to event details page
        header("Location: event-details.php?id=" . $event_id);
        exit();
    } else {
        // Store errors in session and redirect back
        $_SESSION['message_errors'] = $errors;
        header("Location: event-details.php?id=" . $event_id);
        exit();
    }
} else {
    // If not a POST request, redirect to dashboard
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sending Message - Naj Events</title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="../styles/processing.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
</head>

<body>
    <!-- Processing Page (shown only briefly during processing) -->
    <div class="processing-container">
        <div class="processing-content">
            <div class="processing-spinner"></div>
            <h2>Sending your message...</h2>
            <p>Please wait while we process your request.</p>
        </div>
    </div>

    <script>
        // Redirect to event details page after a short delay (in case the PHP redirect doesn't work)
        setTimeout(function() {
            window.location.href = "dashboard.php";
        }, 3000);
    </script>
</body>

</html>