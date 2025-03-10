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
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $review = isset($_POST['review']) ? htmlspecialchars($_POST['review']) : '';

    // Validate input
    $errors = array();

    if ($event_id <= 0) {
        $errors[] = "Invalid event ID";
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = "Rating must be between 1 and 5";
    }

    if (empty($review)) {
        $errors[] = "Review text is required";
    }

    // If no errors, proceed with submitting the review
    if (empty($errors)) {

        require "../APIs/connect.php";

        // Verify that the event belongs to the user and is in the past
        $event_query = "SELECT id FROM reservations WHERE id = ? AND email = ? AND event_date < CURDATE()";
        $stmt = $conn->prepare($event_query);
        $stmt->bind_param("is", $event_id, $user_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Event not found, doesn't belong to user, or is not in the past
            $_SESSION['review_error'] = "Event not found, access denied, or event has not occurred yet";
            header("Location: dashboard.php");
            exit();
        }

        $stmt->close();

        // Check if a review already exists for this reservation
        $check_query = "SELECT id FROM reviews WHERE reservation_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Review already exists, update it
            $review_id = $result->fetch_assoc()['id'];
            $update_query = "UPDATE reviews SET rating = ?, review_text = ?, created_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("isi", $rating, $review, $review_id);

            if ($stmt->execute()) {
                $_SESSION['review_success'] = "Your review has been updated successfully.";
            } else {
                $_SESSION['review_error'] = "Error updating review: " . $stmt->error;
            }
        } else {
            // Insert new review
            $insert_query = "INSERT INTO reviews (reservation_id, user_id, rating, review_text, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iiis", $event_id, $user_id, $rating, $review);

            if ($stmt->execute()) {
                $_SESSION['review_success'] = "Your review has been submitted successfully. Thank you for your feedback!";
            } else {
                $_SESSION['review_error'] = "Error submitting review: " . $stmt->error;
            }
        }

        $stmt->close();
        $conn->close();

        // Redirect back to event details page
        header("Location: event-details.php?id=" . $event_id);
        exit();
    } else {
        // Store errors in session and redirect back
        $_SESSION['review_errors'] = $errors;
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
    <title>Submitting Review - Naj Events</title>
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
            <h2>Submitting your review...</h2>
            <p>Please wait while we process your feedback.</p>
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