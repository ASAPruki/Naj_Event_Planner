<?php

require "connect.php";

// Process form data if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($_POST['phone']);
    $event_type = htmlspecialchars($_POST['event_type']);
    $event_date = htmlspecialchars($_POST['event_date']);
    $guests = (int)$_POST['guests'];
    $location_type = htmlspecialchars($_POST['location_type']);
    $venue = isset($_POST['venue']) ? htmlspecialchars($_POST['venue']) : '';
    $budget = isset($_POST['budget']) ? htmlspecialchars($_POST['budget']) : '';
    $message = isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '';

    // Handle accessories array
    $accessories = isset($_POST['accessories']) ? $_POST['accessories'] : array();
    $accessories_string = implode(", ", $accessories);

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO reservations (name, email, phone, event_type, event_date, guests, location_type, venue, accessories, budget, message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param("sssssisisss", $name, $email, $phone, $event_type, $event_date, $guests, $location_type, $venue, $accessories_string, $budget, $message);

    // Execute the statement
    if ($stmt->execute()) {
        // Send confirmation email
        $to = $email;
        $subject = "Reservation Confirmation - Naj Events";
        $email_message = "Dear $name,\n\nThank you for your reservation with Naj Events. We have received your request for a $event_type event on $event_date.\n\nOur team will contact you shortly to discuss your event details.\n\nBest regards,\nNaj Events Team";
        $headers = "From: info@najevents.com";

        mail($to, $subject, $email_message, $headers);

        // Redirect to thank you page
        header("Location: ../pages/thank-you.html");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
