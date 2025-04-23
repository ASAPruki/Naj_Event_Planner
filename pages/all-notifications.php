<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: index.php#login");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

require "../APIs/connect.php";

// Get all user notifications
$query = "SELECT n.*, r.event_type, r.event_date 
          FROM notifications n 
          LEFT JOIN reservations r ON n.event_id = r.id 
          WHERE n.user_id = ? 
          ORDER BY n.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications - Naj Events</title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="stylesheet" href="../styles/notification-styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script src="../scripts/script.js"></script>
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
                    <li><a href="dashboard.php">My Account</a></li>
                    <?php include 'notification-sidebar.php'; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- All Notifications Section -->
    <section class="all-notifications-section">
        <div class="container">
            <h2 class="section-title" style="margin-top: 130px;">All Notifications</h2>

            <div class="notifications-container" style="min-height: 300px;">
                <?php if (count($notifications) > 0): ?>
                    <div class="notification-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>" data-id="<?php echo $notification['id']; ?>">
                                <div class="notification-icon <?php echo $notification['type']; ?>">
                                    <?php if ($notification['type'] == 'success'): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php elseif ($notification['type'] == 'danger'): ?>
                                        <i class="fas fa-times-circle"></i>
                                    <?php elseif ($notification['type'] == 'info'): ?>
                                        <i class="fas fa-info-circle"></i>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-circle"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-details">
                                    <p class="notification-message"><?php echo $notification['message']; ?></p>
                                    <p class="notification-time"><?php echo date('F d, Y - g:i a', strtotime($notification['created_at'])); ?></p>
                                </div>
                                <?php if (!$notification['is_read']): ?>
                                    <div class="notification-status"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="notification-actions">
                        <button id="markAllRead" class="btn btn-secondary" style="margin-right: 30px;">Mark all as read</button>
                        <a href="dashboard.php" class="btn btn-primary" style="margin-left: 30px;">Back to Dashboard</a>
                    </div>
                <?php else: ?>
                    <div class="no-notifications">
                        <div class="empty-icon">
                            <i class="fas fa-bell-slash"></i>
                        </div>
                        <p>You don't have any notifications yet.</p>
                        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

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
                        <p><strong>Phone:</strong> (123) 456-7890</p>
                        <p><strong>Email:</strong> info@najevents.com</p>
                    </div>
                    <div class="footer-hours">
                        <h3>Business Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                        <p>Saturday: 10:00 AM - 4:00 PM</p>
                        <p>Sunday: Closed</p>
                    </div>
                    <div class="footer-social">
                        <h3>Follow Us</h3>
                        <a href="https://instagram.com/najevents" class="social-link instagram">Instagram</a>
                        <a href="https://maps.google.com" class="location-link">Office Location</a>
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