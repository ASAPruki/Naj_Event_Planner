<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    // Redirect to login page if not logged in
    header("Location: ../pages/index.htnl");
    exit();
}

require "../APIs/connect.php";

// Process form data if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the logged-in user's email
    $user_email = $_SESSION['user_email'];

    // Get the submitted email
    $submitted_email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Verify that the submitted email matches the logged-in user's email
    if ($submitted_email !== $user_email) {
        // Email mismatch - this shouldn't happen unless someone is trying to manipulate the form
        echo "<div style='text-align: center; padding: 50px;'>";
        echo "<h2>Error</h2>";
        echo "<p>The email address used for this reservation does not match your account email. Please use your account email for reservations.</p>";
        echo "<a href='../pages/reservation.php' class='btn'>Go Back</a>";
        echo "</div>";
        exit();
    }

    // Sanitize input data
    $name = htmlspecialchars($_POST['name']);
    $email = $user_email; // Use the session email to ensure it's correct
    $phone = htmlspecialchars($_POST['phone']);
    $event_type = htmlspecialchars($_POST['event_type']);
    $event_date = htmlspecialchars($_POST['event_date']);
    $event_time = htmlspecialchars($_POST['event_time']);
    $guests = (int)$_POST['guests'];
    $location_type = htmlspecialchars($_POST['location_type']);
    $venue = isset($_POST['venue']) && trim($_POST['venue']) !== '' ? htmlspecialchars($_POST['venue']) : 'Not set';
    $budget = isset($_POST['budget']) ? htmlspecialchars($_POST['budget']) : '';
    $message = isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '';

    // Handle accessories array
    $accessories = isset($_POST['accessories']) ? $_POST['accessories'] : array();
    $accessories_string = implode(", ", $accessories);

    // Get user ID from session
    $user_id = $_SESSION['user_id'];

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO reservations (user_id, name, email, phone, event_type, event_date, event_time, guests, location_type, venue, accessories, budget, message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issssssisssss", $user_id, $name, $email, $phone, $event_type, $event_date, $event_time, $guests, $location_type, $venue, $accessories_string, $budget, $message);

    // Execute the statement
    if ($stmt->execute()) {
        // Send confirmation email
        $to = $email;
        $subject = "Reservation Confirmation - Naj Events";
        $email_message = "Dear $name,

Thank you for your reservation with Naj Events. We have received your request for a $event_type event on $event_date at $event_time.

Our team will contact you shortly to discuss your event details.

Best regards,
Naj Events Team";
        $headers = "From: info@najevents.com";

        mail($to, $subject, $email_message, $headers);

        // Redirect to thank you page
        header("Location: ../pages/thank-you.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
