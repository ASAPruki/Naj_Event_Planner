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
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

require "../APIs/connect.php";

// Get user's upcoming events
$upcoming_events_query = "SELECT * FROM reservations WHERE email = ? AND event_date >= CURDATE() AND status != 'cancelled' AND status != 'completed' ORDER BY event_date ASC";
$stmt = $conn->prepare($upcoming_events_query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$upcoming_events_result = $stmt->get_result();
$upcoming_events = $upcoming_events_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user's past events (events with past dates or completed status)
$past_events_query = "SELECT * FROM reservations WHERE email = ? AND (event_date < CURDATE() OR status = 'completed') ORDER BY event_date DESC";
$stmt = $conn->prepare($past_events_query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$past_events_result = $stmt->get_result();
$past_events = $past_events_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user's pending events (events that are in draft or review status)
$pending_events_query = "SELECT * FROM reservations WHERE email = ? AND status = 'pending' AND event_date >= CURDATE() ORDER BY created_at DESC";
$stmt = $conn->prepare($pending_events_query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$pending_events_result = $stmt->get_result();
$pending_events = $pending_events_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Naj Events</title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="../styles/notification-styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <script src="../scripts/dashboard.js"></script>
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

    <!-- Dashboard Section -->
    <section class="dashboard-section">
        <div class="container">
            <div class="dashboard-container">
                <!-- Dashboard Sidebar -->
                <div class="dashboard-sidebar">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <h3><?php echo $user_name; ?></h3>
                            <p><?php echo $user_email; ?></p>
                        </div>
                    </div>
                    <ul class="dashboard-nav">
                        <li><a href="#overview" class="active" data-tab="overview">Dashboard Overview</a></li>
                        <li><a href="#upcoming-events" data-tab="upcoming-events">Upcoming Events</a></li>
                        <li><a href="#pending-events" data-tab="pending-events">Pending Events</a></li>
                        <li><a href="#past-events" data-tab="past-events">Past Events</a></li>
                        <li><a href="#profile" data-tab="profile">My Profile</a></li>
                        <li><a href="../APIs/logout.php" id="logout">Logout</a></li>
                    </ul>
                </div>

                <!-- Dashboard Content -->
                <div class="dashboard-content">
                    <div id="sidebar-toggle" class="sidebar-toggle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <!-- Overview Tab -->
                    <div id="overview" class="dashboard-tab active">
                        <h2 class="dashboard-title">Dashboard Overview</h2>

                        <div class="dashboard-stats">
                            <div class="stat-card">
                                <div class="stat-icon upcoming-icon"></div>
                                <div class="stat-info">
                                    <h3>Upcoming Events</h3>
                                    <p class="stat-number"><?php echo count($upcoming_events); ?></p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon pending-icon"></div>
                                <div class="stat-info">
                                    <h3>Pending Events</h3>
                                    <p class="stat-number"><?php echo count($pending_events); ?></p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon past-icon"></div>
                                <div class="stat-info">
                                    <h3>Past Events</h3>
                                    <p class="stat-number"><?php echo count($past_events); ?></p>
                                </div>
                            </div>
                        </div>

                        <?php if (count($upcoming_events) > 0): ?>
                            <div class="dashboard-section">
                                <h3 class="section-heading">Next Upcoming Event</h3>
                                <div class="event-card highlight">
                                    <div class="event-date">
                                        <span class="day"><?php echo date('d', strtotime($upcoming_events[0]['event_date'])); ?></span>
                                        <span class="month"><?php echo date('M', strtotime($upcoming_events[0]['event_date'])); ?></span>
                                    </div>
                                    <div class="event-details">
                                        <h4><?php echo ucfirst($upcoming_events[0]['event_type']); ?> Event</h4>
                                        <p><strong>Guests:</strong> <?php echo $upcoming_events[0]['guests']; ?></p>
                                        <p><strong>Location:</strong> <?php echo ucfirst($upcoming_events[0]['location_type']); ?></p>
                                        <?php if (!empty($upcoming_events[0]['venue'])): ?>
                                            <p><strong>Venue:</strong> <?php echo $upcoming_events[0]['venue']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="event-actions">
                                        <a href="event-details.php?id=<?php echo $upcoming_events[0]['id']; ?>" class="btn btn-secondary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (count($pending_events) > 0): ?>
                            <div class="dashboard-section">
                                <h3 class="section-heading">Recent Pending Event</h3>
                                <div class="event-card">
                                    <div class="event-date pending">
                                        <span class="status">Pending</span>
                                    </div>
                                    <div class="event-details">
                                        <h4><?php echo ucfirst($pending_events[0]['event_type']); ?> Event</h4>
                                        <p><strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($pending_events[0]['created_at'])); ?></p>
                                        <p><strong>Guests:</strong> <?php echo $pending_events[0]['guests']; ?></p>
                                        <p><strong>Location:</strong> <?php echo ucfirst($pending_events[0]['location_type']); ?></p>
                                    </div>
                                    <div class="event-actions">
                                        <a href="event-details.php?id=<?php echo $pending_events[0]['id']; ?>" class="btn btn-secondary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="dashboard-section">
                            <h3 class="section-heading">Quick Actions</h3>
                            <div class="quick-actions">
                                <a href="reservation.php" class="action-card">
                                    <div class="action-icon book-icon"></div>
                                    <h4>Book New Event</h4>
                                </a>
                                <a href="accessories.php" class="action-card">
                                    <div class="action-icon accessories-icon"></div>
                                    <h4>Browse Accessories</h4>
                                </a>
                                <a href="#profile" class="action-card tab-link" data-tab="profile">
                                    <div class="action-icon profile-icon"></div>
                                    <h4>Update Profile</h4>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Events Tab -->
                    <div id="upcoming-events" class="dashboard-tab">
                        <h2 class="dashboard-title">Upcoming Events</h2>

                        <?php if (count($upcoming_events) > 0): ?>
                            <div class="events-list">
                                <?php foreach ($upcoming_events as $event): ?>
                                    <div class="event-card">
                                        <div class="event-date">
                                            <span class="day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                            <span class="month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                                        </div>
                                        <div class="event-details">
                                            <h4><?php echo ucfirst($event['event_type']); ?> Event</h4>
                                            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($event['event_date'])); ?></p>
                                            <p><strong>Guests:</strong> <?php echo $event['guests']; ?></p>
                                            <p><strong>Location:</strong> <?php echo ucfirst($event['location_type']); ?></p>
                                            <?php if (!empty($event['venue'])): ?>
                                                <p><strong>Venue:</strong> <?php echo $event['venue']; ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="event-actions">
                                            <a href="event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-secondary">View Details</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon"></div>
                                <h3>No Upcoming Events</h3>
                                <p>You don't have any upcoming events scheduled. Ready to plan your next event?</p>
                                <a href="reservation.php" class="btn btn-primary">Book an Event</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pending Events Tab -->
                    <div id="pending-events" class="dashboard-tab">
                        <h2 class="dashboard-title">Pending Events</h2>

                        <?php if (count($pending_events) > 0): ?>
                            <div class="events-list">
                                <?php foreach ($pending_events as $event): ?>
                                    <div class="event-card">
                                        <div class="event-date pending">
                                            <span class="status">Pending</span>
                                        </div>
                                        <div class="event-details">
                                            <h4><?php echo ucfirst($event['event_type']); ?> Event</h4>
                                            <p><strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($event['created_at'])); ?></p>
                                            <p><strong>Planned Date:</strong> <?php echo date('F d, Y', strtotime($event['event_date'])); ?></p>
                                            <p><strong>Guests:</strong> <?php echo $event['guests']; ?></p>
                                            <p><strong>Location:</strong> <?php echo ucfirst($event['location_type']); ?></p>
                                        </div>
                                        <div class="event-actions">
                                            <a href="event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-secondary">View Details</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon"></div>
                                <h3>No Pending Events</h3>
                                <p>You don't have any events pending approval or in draft status.</p>
                                <a href="reservation.php" class="btn btn-primary">Book an Event</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Past Events Tab -->
                    <div id="past-events" class="dashboard-tab">
                        <h2 class="dashboard-title">Past Events</h2>

                        <?php if (count($past_events) > 0): ?>
                            <div class="events-list">
                                <?php foreach ($past_events as $event): ?>
                                    <div class="event-card past">
                                        <div class="event-date">
                                            <span class="day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                            <span class="month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                                        </div>
                                        <div class="event-details">
                                            <h4><?php echo ucfirst($event['event_type']); ?> Event</h4>
                                            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($event['event_date'])); ?></p>
                                            <p><strong>Guests:</strong> <?php echo $event['guests']; ?></p>
                                            <p><strong>Location:</strong> <?php echo ucfirst($event['location_type']); ?></p>
                                            <?php if (!empty($event['venue'])): ?>
                                                <p><strong>Venue:</strong> <?php echo $event['venue']; ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="event-actions">
                                            <a href="event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-secondary">View Details</a>
                                            <button class="btn btn-outline book-again" data-event-type="<?php echo $event['event_type']; ?>">Book Again</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon"></div>
                                <h3>No Past Events</h3>
                                <p>You don't have any past events with us yet.</p>
                                <a href="reservation.php" class="btn btn-primary">Book Your First Event</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Profile Tab -->
                    <div id="profile" class="dashboard-tab">
                        <h2 class="dashboard-title">My Profile</h2>

                        <div class="profile-container">
                            <div class="profile-header">
                                <div class="profile-avatar">
                                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                </div>
                                <div class="profile-info">
                                    <h3><?php echo $user_name; ?></h3>
                                    <p><?php echo $user_email; ?></p>
                                    <p class="member-since">Member since <?php echo date('F Y'); ?></p>
                                </div>
                            </div>

                            <div class="profile-form">
                                <form id="profile-update-form" action="../APIs/update-profile.php" method="post">
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" id="name" name="name" value="<?php echo $user_name; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" id="email" name="email" value="<?php echo $user_email; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <input type="text" id="address" name="address" rows="3" placeholder="Enter your address"></input>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Update Profile</button>
                                    </div>
                                </form>
                            </div>

                            <div class="password-form">
                                <h3>Change Password</h3>
                                <form id="password-update-form" action="../APIs/update-password.php" method="post">
                                    <div class="form-group">
                                        <label for="current-password">Current Password</label>
                                        <input type="password" id="current-password" name="current_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="new-password">New Password</label>
                                        <input type="password" id="new-password" name="new_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm-password">Confirm New Password</label>
                                        <input type="password" id="confirm-password" name="confirm_password" required>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-secondary">Change Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
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
                        <div style="display: flex;">
                            <i class="fa-brands fa-instagram" style="font-size:24px; margin-right: 7px;"></i>
                            <a href="https://www.instagram.com/naj__wedding_planner" class="social-link instagram">Instagram</a>
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