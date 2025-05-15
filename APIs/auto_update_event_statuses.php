<?php

/**
 * Auto Update Event Statuses
 * 
 * This file automatically updates event statuses based on specific conditions
 * It should be included in frequently accessed pages like dashboards
 */

// Don't run this script if it was already run in the last hour (to prevent excessive database operations)
$last_run_file = __DIR__ . '/last_status_update.txt';
$should_run = true;

if (file_exists($last_run_file)) {
    $last_run_time = file_get_contents($last_run_file);
    // Only run if the last execution was more than 1 hour ago
    if (time() - $last_run_time < 3600) {
        $should_run = false;
    }
}

if ($should_run) {
    require "connect.php";

    // Get today's date (to include all past events)
    $today = date('Y-m-d');

    // Start transaction for better data integrity
    $conn1->begin_transaction();

    try {
        // 1. Update events to "Missed" status
        // Case 1: Events that are still "pending" and past their date
        $missed_query1 = "UPDATE reservations r
                         LEFT JOIN financial_records f ON r.id = f.reservation_id
                         SET r.status = 'missed',
                             r.updated_at = NOW()
                         WHERE r.status = 'pending'
                         AND r.event_date < ?
                         AND (f.deposit_receipt IS NULL OR f.id IS NULL)";

        $stmt1 = $conn1->prepare($missed_query1);
        $stmt1->bind_param("s", $today);
        $stmt1->execute();
        $missed_count1 = $stmt1->affected_rows;
        $stmt1->close();

        // Case 2: Events that are "confirmed" but no deposit receipt and past their date
        $missed_query2 = "UPDATE reservations r
                         LEFT JOIN financial_records f ON r.id = f.reservation_id
                         SET r.status = 'missed',
                             r.updated_at = NOW()
                         WHERE r.status = 'confirmed'
                         AND r.event_date < ?
                         AND (f.deposit_receipt IS NULL OR f.id IS NULL)";

        $stmt2 = $conn1->prepare($missed_query2);
        $stmt2->bind_param("s", $today);
        $stmt2->execute();
        $missed_count2 = $stmt2->affected_rows;
        $stmt2->close();

        // 2. Update events to "Completed" status
        // Events that are "confirmed", have deposit receipt, and past their date
        $completed_query = "UPDATE reservations r
                           JOIN financial_records f ON r.id = f.reservation_id
                           SET r.status = 'completed',
                               r.updated_at = NOW()
                           WHERE r.status = 'confirmed'
                           AND r.event_date < ?
                           AND f.deposit_receipt IS NOT NULL";

        $stmt3 = $conn1->prepare($completed_query);
        $stmt3->bind_param("s", $today);
        $stmt3->execute();
        $completed_count = $stmt3->affected_rows;
        $stmt3->close();

        // Commit the transaction
        $conn1->commit();

        // Log the results
        $total_updated = $missed_count1 + $missed_count2 + $completed_count;
        $log_message = date('Y-m-d H:i:s') . " - Updated $missed_count1 + $missed_count2 events to 'missed' and $completed_count events to 'completed'.\n";

        // Create a log file if it doesn't exist
        $log_file = __DIR__ . '/event_status_updates.log';
        file_put_contents($log_file, $log_message, FILE_APPEND);

        // Update the last run time
        file_put_contents($last_run_file, time());
    } catch (Exception $e) {
        // Rollback in case of error
        $conn1->rollback();
        error_log("Error updating event statuses: " . $e->getMessage());
    }

    $conn1->close();
}
