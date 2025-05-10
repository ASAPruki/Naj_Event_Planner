<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: index.php#login");
    exit();
}

// Check if event ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$event_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

require "../APIs/connect.php";

// Get event details
$event_query = "SELECT * FROM reservations WHERE id = ? AND (user_id = ? OR email = ?)";
$stmt = $conn->prepare($event_query);
$stmt->bind_param("iis", $event_id, $user_id, $user_email);
$stmt->execute();
$result = $stmt->get_result();

// Check if event exists and belongs to the user
if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$event = $result->fetch_assoc();
$stmt->close();

// Mark related notification as read if coming from notification click
if (isset($_GET['notification_id']) && is_numeric($_GET['notification_id'])) {
    $notification_id = $_GET['notification_id'];
    $update_query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $notification_id, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Get payment information
$payment_query = "SELECT * FROM financial_records WHERE reservation_id = ?";
$payment_stmt = $conn->prepare($payment_query);
$payment_stmt->bind_param("i", $event_id);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();

if ($payment_result->num_rows > 0) {
    $payment = $payment_result->fetch_assoc();
} else {
    // Create a new financial record with zero values
    $insert_query = "INSERT INTO financial_records (reservation_id, full_price, deposit_amount) VALUES (?, 0, 0)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("i", $event_id);
    $insert_stmt->execute();
    $payment_id = $conn->insert_id;
    $insert_stmt->close();

    // Get the newly created record
    $payment_stmt->execute();
    $payment_result = $payment_stmt->get_result();
    $payment = $payment_result->fetch_assoc();
}
$payment_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - Naj Events</title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="../styles/event-details.css">
    <link rel="stylesheet" href="../styles/notification-styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <script src="../scripts/event-details.js"></script>
    <script src="../scripts/notification.js"></script>
</head>

<body>
    <!-- Navigation Bar -->
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <h1>Naj Events</h1>
                </a>
            </div>
            <nav>
                <div class="menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="accessories.php">Accessories</a></li>
                    <li><a href="reservation.php">Book an Event</a></li>
                    <li><a href="index.php#about">About Us</a></li>
                    <li><a href="dashboard.php" class="active">My Account</a></li>
                    <?php include 'notification-sidebar.php'; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Event Details Section -->
    <section class="event-details-section">
        <div class="container">
            <div class="event-details-container">
                <div class="event-details-header">
                    <div class="back-link">
                        <a href="dashboard.php"><span class="back-icon"></span> Back to Dashboard</a>
                    </div>
                    <h2 class="event-title"><?php echo ucfirst($event['event_type']); ?> Event Details</h2>
                    <div class="event-status">
                        <?php
                        // Get current date at the start of the day
                        $today = date('Y-m-d');
                        $event_date = date('Y-m-d', strtotime($event['event_date']));
                        if ($event_date < $today): ?>
                            <span class="status-badge past">Past Event</span>
                        <?php elseif (isset($event['status']) && $event['status'] === 'pending'): ?>
                            <span class="status-badge pending">Pending Approval</span>
                        <?php elseif ($event_date == $today): ?>
                            <span class="status-badge today">Today's Event</span>
                        <?php else: ?>
                            <span class="status-badge upcoming">Upcoming Event</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="event-details-content">
                    <div class="event-info-card">
                        <h3>Event Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Event Type</span>
                                <span class="info-value"><?php echo ucfirst($event['event_type']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Event Date</span>
                                <span class="info-value"><?php echo date('F d, Y', strtotime($event['event_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Event Time</span>
                                <span class="info-value"><?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Number of Guests</span>
                                <span class="info-value"><?php echo $event['guests']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Location Type</span>
                                <span class="info-value"><?php echo ucfirst($event['location_type']); ?></span>
                            </div>
                            <?php if (!empty($event['venue'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Venue</span>
                                    <span class="info-value"><?php echo $event['venue']; ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($event['budget'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Budget Range</span>
                                    <span class="info-value"><?php echo $event['budget']; ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <span class="info-label">Booking Date</span>
                                <span class="info-value"><?php echo date('F d, Y', strtotime($event['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($event['accessories'])): ?>
                        <div class="event-info-card">
                            <h3>Accessories</h3>
                            <div class="accessories-list">
                                <?php
                                $accessories = explode(', ', $event['accessories']);
                                foreach ($accessories as $accessory):
                                ?>
                                    <div class="accessory-item">
                                        <span class="accessory-icon"></span>
                                        <span class="accessory-name"><?php echo ucfirst($accessory); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($event['message'])): ?>
                        <div class="event-info-card">
                            <h3>Additional Information</h3>
                            <div class="message-content">
                                <?php echo nl2br($event['message']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="event-info-card">
                        <h3>Payment Information</h3>
                        <div class="payment-details">
                            <?php if ($payment['full_price'] > 0): ?>
                                <div class="payment-item">
                                    <span class="payment-label">Full Price:</span>
                                    <span class="payment-value">$<?php echo number_format($payment['full_price'], 2); ?></span>
                                </div>
                                <div class="payment-item">
                                    <span class="payment-label">Deposit Amount:</span>
                                    <span class="payment-value">$<?php echo number_format($payment['deposit_amount'], 2); ?>
                                        (<?php echo round(($payment['deposit_amount'] / $payment['full_price']) * 100); ?>%)
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="payment-item">
                                    <span class="payment-label">Pricing:</span>
                                    <span class="payment-value">
                                        <span class="status-badge info">Pricing will be set by the admin</span>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="payment-item">
                                <span class="payment-label">Deposit Status:</span>
                                <span class="payment-value">
                                    <?php if ($payment['deposit_paid']): ?>
                                        <span class="status-badge success">Paid</span>
                                    <?php elseif ($payment['deposit_receipt']): ?>
                                        <span class="status-badge pending">Pending Approval</span>
                                    <?php else: ?>
                                        <span class="status-badge danger">Unpaid</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="payment-item">
                                <span class="payment-label">Full Payment Status:</span>
                                <span class="payment-value">
                                    <?php if ($payment['full_amount_paid']): ?>
                                        <span class="status-badge success">Paid</span>
                                    <?php elseif ($payment['full_payment_receipt']): ?>
                                        <span class="status-badge pending">Pending Approval</span>
                                    <?php else: ?>
                                        <span class="status-badge danger">Unpaid</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <?php
                        // Get notifications for this user
                        $notification_query = "
                        SELECT id, message, type 
                        FROM notifications 
                        WHERE user_id = ? 
                        AND type IN ('deposit_declined', 'full_payment_declined', 'deposit_accepted', 'full_payment_accepted') 
                        AND is_read = 0 
                        ORDER BY created_at DESC
                        ";

                        $notification_stmt = $conn->prepare($notification_query);
                        $notification_stmt->bind_param("i", $user_id);
                        $notification_stmt->execute();
                        $result = $notification_stmt->get_result();

                        // Render notifications
                        if ($result->num_rows > 0) {
                            while ($notification = $result->fetch_assoc()) {
                                // Optionally add icon/color based on type
                                $typeClass = 'info';
                                if (strpos($notification['type'], 'declined') !== false) {
                                    $typeClass = 'danger';
                                } elseif (strpos($notification['type'], 'accepted') !== false) {
                                    $typeClass = 'success';
                                }
                            }
                        }

                        $notification_stmt->close();
                        $conn->close();
                        ?>


                        <?php if ($event['status'] === 'confirmed' && $payment['full_price'] > 0): ?>
                            <?php if (!$payment['deposit_paid'] && !$payment['deposit_receipt']): ?>
                                <div class="payment-upload-section">
                                    <h4>Upload Deposit Receipt</h4>
                                    <form action="upload-receipt.php" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="record_id" value="<?php echo $payment['id']; ?>">
                                        <input type="hidden" name="payment_type" value="deposit">
                                        <div class="form-group">
                                            <input type="file" name="receipt" required accept="image/*">
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">Upload Receipt</button>
                                        </div>
                                    </form>
                                </div>
                            <?php elseif ($payment['deposit_paid'] && !$payment['full_amount_paid'] && !$payment['full_payment_receipt']): ?>
                                <div class="payment-upload-section">
                                    <h4>Upload Full Payment Receipt</h4>
                                    <form action="upload-receipt.php" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="record_id" value="<?php echo $payment['id']; ?>">
                                        <input type="hidden" name="payment_type" value="full">
                                        <div class="form-group">
                                            <input type="file" name="receipt" required accept="image/*">
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">Upload Receipt</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($event['status'] === 'confirmed' && $payment['full_price'] <= 0): ?>
                            <div class="alert alert-info">
                                <strong>Note:</strong> The admin will set the pricing for your event. Once pricing is set, you will be able to upload payment receipts.
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$payment['full_amount_paid']):; ?>
                        <!-- Can't cancel an event after paying the fullprice -->
                        <div class="event-actions-card">
                            <h3>Actions</h3>
                            <div class="action-buttons">
                                <?php
                                // Get current date at the start of the day
                                $today = date('Y-m-d');
                                $event_date = date('Y-m-d', strtotime($event['event_date']));
                                if ($event_date >= $today): // Includes today's events
                                ?>
                                    <!-- ONLY ALLOW USER TO ALTER AN EVENT IF IT'S PENDING (NOT CONFIRMED YET) -->
                                    <?php if (!isset($event['status']) || $event['status'] === 'pending'): ?>
                                        <a href="edit-event.php?id=<?php echo $event_id; ?>" class="btn btn-primary">Edit Event</a>
                                    <?php endif; ?>
                                    <button class="btn btn-outline" id="cancel-event">Cancel Event</button>
                                <?php else: ?> <!-- the review and book similar event only shows if the status of the event is approved or accpeted -->
                                    <button class="btn btn-primary book-again" data-event-type="<?php echo $event['event_type']; ?>">Book Similar Event</button>
                                    <button class="btn btn-secondary" id="leave-review">Leave a Review</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Cancel Event Modal -->
    <div id="cancel-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Cancel Event</h2>
            <p>Are you sure you want to cancel this event? This action cannot be undone.</p>
            <form id="cancel-form" action="cancel-event.php" method="post">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                <div class="form-group">
                    <label for="cancel-reason">Reason for Cancellation</label>
                    <input id="cancel-reason" name="reason" required></input>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-outline">Confirm Cancellation</button>
                    <button type="button" class="btn btn-secondary close-modal">Keep Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="review-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Leave a Review</h2>
            <form id="review-form" action="submit-review.php" method="post">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                <div class="form-group">
                    <label for="rating">Rating</label>
                    <div class="rating-stars">
                        <input type="radio" id="star5" name="rating" value="5" required>
                        <label for="star5"></label>
                        <input type="radio" id="star4" name="rating" value="4">
                        <label for="star4"></label>
                        <input type="radio" id="star3" name="rating" value="3">
                        <label for="star3"></label>
                        <input type="radio" id="star2" name="rating" value="2">
                        <label for="star2"></label>
                        <input type="radio" id="star1" name="rating" value="1">
                        <label for="star1"></label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="review-text">Your Review</label>
                    <textarea id="review-text" name="review" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <h2>Naj Events</h2>
                    <p>Creating unforgettable moments</p>
                </div>
                <div class="footer-info">
                    <div class="footer-contact">
                        <h3>Contact Us</h3>
                        <p><strong>Phone:</strong> +961 71 615 159</p>
                        <p><strong>Email:</strong> najahshafei5@gmail.com</p>
                    </div>
                    <div class="footer-hours">
                        <h3>Business Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                        <p>Saturday: 10:00 AM - 4:00 PM</p>
                        <p>Sunday: Closed</p>
                    </div>
                    <div class="footer-social">
                        <h3>Follow Us</h3>
                        <div style="display: flex;">
                            <i class="fa-brands fa-instagram" style="font-size:24px; margin-right: 7px;"></i>
                            <a href="https://www.instagram.com/naj__wedding_planner" class="social-link instagram">Instagram</a>
                        </div>
                        <div style="display: flex;">
                            <img src="../images/general/tiktok-white-icon.png" alt="TikTok"
                                style="width: 24px; height: 24px; margin-right: 4px; margin-left: -1px;">
                            <a href="https://www.tiktok.com/@naj_wedding_planner" class="social-link tiktok">Tiktok</a>
                        </div>
                        <div style="display: flex;">
                            <i class="fa-solid fa-location-dot" style="font-size:24px; margin-right: 7px; margin-left: 1px"></i>
                            <a href="https://maps.google.com" class="location-link">Office Location</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Naj Events. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>

</html>