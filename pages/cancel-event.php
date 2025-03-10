<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: index.html");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $reason = isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : '';

    // Validate input
    $errors = array();

    if ($event_id <= 0) {
        $errors[] = "Invalid event ID";
    }

    if (empty($reason)) {
        $errors[] = "Cancellation reason is required";
    }

    // If no errors, proceed with cancellation
    if (empty($errors)) {

        require "../APIs/connect.php";

        // Verify that the event belongs to the user and is not in the past
        $event_query = "SELECT id, event_date FROM reservations WHERE id = ? AND email = ? AND event_date >= CURDATE()";
        $stmt = $conn->prepare($event_query);
        $stmt->bind_param("is", $event_id, $user_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Event not found, doesn't belong to user, or is in the past
            $_SESSION['cancel_error'] = "Event not found, access denied, or event date has already passed";
            header("Location: dashboard.php");
            exit();
        }

        $stmt->close();

        // First, store the cancellation reason in the messages table
        $message_query = "INSERT INTO messages (reservation_id, user_id, subject, message, is_from_user, created_at) 
                         VALUES (?, ?, 'Event Cancellation', ?, 1, NOW())";
        $stmt = $conn->prepare($message_query);
        $cancellation_subject = "Event Cancellation";
        $stmt->bind_param("iis", $event_id, $user_id, $reason);
        $stmt->execute();
        $stmt->close();

        // Now delete the event from the database
        $delete_query = "DELETE FROM reservations WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $event_id);

        if ($stmt->execute()) {
            // Event deleted successfully
            $_SESSION['cancel_success'] = "Your event has been deleted successfully.";
        } else {
            // Error deleting event
            $_SESSION['cancel_error'] = "Error deleting event: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();

        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        // Store errors in session and redirect back
        $_SESSION['cancel_errors'] = $errors;
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
    <title>Cancelling Event - Naj Events</title>
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
            <h2>Cancelling your event...</h2>
            <p>Please wait while we process your request.</p>
        </div>
    </div>

    <script>
        // Redirect to dashboard after a short delay (in case the PHP redirect doesn't work)
        setTimeout(function() {
            window.location.href = "dashboard.php";
        }, 3000);
    </script>
</body>

</html>