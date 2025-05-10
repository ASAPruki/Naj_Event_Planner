<?php
require "connect.php";

// Get yesterday's date (to include all past events)
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Update all events that were scheduled for yesterday or earlier and are still in confirmed status
$update_query = "UPDATE reservations 
                SET status = 'completed' 
                WHERE DATE(event_date) <= ? 
                AND status = 'confirmed'";

$stmt = $conn->prepare($update_query);
$stmt->bind_param("s", $yesterday);
$stmt->execute();

$affected_rows = $stmt->affected_rows;
$stmt->close();
$conn->close();

// Note that this file doesn't work without windows task scheduler, since it automatically triggers at 12AM