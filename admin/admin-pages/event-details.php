<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Check if event ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$event_id = $_GET['id'];

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

require "../../APIs/connect.php";

// Get event details
$event_query = "SELECT r.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
                FROM reservations r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.id = ?";
$stmt = $conn->prepare($event_query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if event exists
if ($result->num_rows === 0) {
    header("Location: events.php");
    exit();
}

$event = $result->fetch_assoc();
$stmt->close();

// Log admin activity
$action_details = "Viewed event details for event #" . $event_id;
$log_query = "INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'view', ?, ?)";
$log_stmt = $conn->prepare($log_query);
$ip_address = $_SERVER['REMOTE_ADDR'];
$log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
$log_stmt->execute();
$log_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - Naj Events Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
    <link rel="stylesheet" href="../admin-styles/admin-event-details.css">
    <link rel="stylesheet" href="../../styles/styles.css">
</head>

<body class="admin-body">
    <div class="admin-container">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-sidebar-header">
                <h2>Naj Events</h2>
            </div>
            <div class="admin-sidebar-content">
                <ul class="admin-nav">
                    <li class="admin-nav-item">
                        <a href="admin-dashboard.php" class="admin-nav-link">
                            <i class="fas fa-tachometer-alt admin-nav-icon"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="events.php" class="admin-nav-link active">
                            <i class="fas fa-calendar-alt admin-nav-icon"></i>
                            Events Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="accessories.php" class="admin-nav-link">
                            <i class="fas fa-chair admin-nav-icon"></i>
                            Accessories
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="users.php" class="admin-nav-link">
                            <i class="fas fa-users admin-nav-icon"></i>
                            User Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="admins.php" class="admin-nav-link">
                            <i class="fas fa-user-shield admin-nav-icon"></i>
                            Admin Management
                        </a>
                    </li>
                </ul>
            </div>
            <div class="admin-sidebar-footer">
                <div class="admin-user-info">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    </div>
                    <div class="admin-user-details">
                        <div class="admin-user-name"><?php echo $admin_name; ?></div>
                        <div class="admin-user-role"><?php echo ucfirst($admin_role); ?></div>
                    </div>
                </div>
                <a href="admin-logout.php" class="admin-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Admin Main Content -->
        <main class="admin-main" id="adminMain">
            <header class="admin-header">
                <button class="admin-toggle-sidebar" id="toggleSidebar">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1 class="admin-header-title">Event Details</h1>
            </header>

            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-header-left">
                            <a href="events.php" class="admin-btn admin-btn-light">
                                <i class="fas fa-arrow-left"></i> Back to Events
                            </a>
                        </div>
                        <h2 class="admin-card-title">
                            <?php echo ucfirst($event['event_type']); ?> Event #<?php echo $event['id']; ?>
                        </h2>
                        <div class="admin-card-header-right">
                            <?php
                            $status_class = 'warning';
                            $status_text = 'Pending';

                            if (isset($event['status'])) {
                                switch ($event['status']) {
                                    case 'confirmed':
                                        $status_class = 'success';
                                        $status_text = 'Confirmed';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'danger';
                                        $status_text = 'Cancelled';
                                        break;
                                    case 'completed':
                                        $status_class = 'info';
                                        $status_text = 'Completed';
                                        break;
                                    default:
                                        $status_class = 'warning';
                                        $status_text = 'Pending';
                                }
                            }
                            ?>
                            <span class="admin-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-event-details">
                            <div class="admin-event-actions">
                                <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-edit"></i> Edit Event
                                </a>
                                <?php if ($event['status'] === 'pending'): ?>
                                    <button class="admin-btn admin-btn-success" id="confirmEventBtn">
                                        <i class="fas fa-check"></i> Confirm Event
                                    </button>
                                <?php elseif ($event['status'] === 'confirmed'): ?>
                                    <button class="admin-btn admin-btn-info" id="completeEventBtn">
                                        <i class="fas fa-flag-checkered"></i> Mark as Completed
                                    </button>
                                <?php endif; ?>
                                <?php if ($event['status'] !== 'cancelled'): ?>
                                    <button class="admin-btn admin-btn-danger" id="cancelEventBtn">
                                        <i class="fas fa-times"></i> Cancel Event
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="admin-event-actions">
                                <?php if ($event['status'] === 'cancelled' && !empty($event['admin_notes'])): ?>
                                    <div style="margin-top: 5px;">
                                        <strong style="color: red;">Cancellation Reason:</strong><br>
                                        <?php echo htmlspecialchars($event['admin_notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="admin-detail-section">
                                <h3 class="admin-detail-section-title">Client Information</h3>
                                <div class="admin-detail-grid">
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Name</div>
                                        <div class="admin-detail-value"><?php echo $event['name']; ?></div>
                                    </div>
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Email</div>
                                        <div class="admin-detail-value"><?php echo $event['email']; ?></div>
                                    </div>
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Phone</div>
                                        <div class="admin-detail-value"><?php echo $event['phone']; ?></div>
                                    </div>
                                    <?php if (isset($event['user_id']) && !empty($event['user_id'])): ?>
                                        <div class="admin-detail-item">
                                            <div class="admin-detail-label">User Account</div>
                                            <div class="admin-detail-value">
                                                <a href="user-details.php?id=<?php echo $event['user_id']; ?>" class="admin-link">
                                                    View User Profile
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="admin-detail-section">
                                <h3 class="admin-detail-section-title">Event Information</h3>
                                <div class="admin-detail-grid">
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Event Type</div>
                                        <div class="admin-detail-value"><?php echo ucfirst($event['event_type']); ?></div>
                                    </div>
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Event Date</div>
                                        <div class="admin-detail-value"><?php echo date('F d, Y', strtotime($event['event_date'])); ?></div>
                                    </div>
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Number of Guests</div>
                                        <div class="admin-detail-value"><?php echo $event['guests']; ?></div>
                                    </div>
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Location Type</div>
                                        <div class="admin-detail-value"><?php echo ucfirst($event['location_type']); ?></div>
                                    </div>
                                    <?php if (!empty($event['venue'])): ?>
                                        <div class="admin-detail-item">
                                            <div class="admin-detail-label">Venue</div>
                                            <div class="admin-detail-value"><?php echo $event['venue']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($event['budget'])): ?>
                                        <div class="admin-detail-item">
                                            <div class="admin-detail-label">Budget Range</div>
                                            <div class="admin-detail-value"><?php echo $event['budget']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Booking Date</div>
                                        <div class="admin-detail-value"><?php echo date('F d, Y', strtotime($event['created_at'])); ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($event['accessories'])): ?>
                                <div class="admin-detail-section">
                                    <h3 class="admin-detail-section-title">Accessories</h3>
                                    <div class="admin-accessories-list">
                                        <?php
                                        $accessories = explode(', ', $event['accessories']);
                                        foreach ($accessories as $accessory):
                                        ?>
                                            <div class="admin-accessory-item">
                                                <i class="fas fa-chair"></i>
                                                <span><?php echo ucfirst($accessory); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($event['message'])): ?>
                                <div class="admin-detail-section">
                                    <h3 class="admin-detail-section-title">Additional Information</h3>
                                    <div class="admin-detail-message">
                                        <?php echo nl2br($event['message']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Confirm Event Modal -->
    <div id="confirmEventModal" class="admin-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3>Confirm Event</h3>
                <button class="admin-modal-close">&times;</button>
            </div>
            <div class="admin-modal-body">
                <p>Are you sure you want to confirm this event?</p>
                <p>This will notify the client that their event has been approved.</p>
                <form id="confirmEventForm" action="update-event-status.php" method="post">
                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                    <input type="hidden" name="status" value="confirmed">
                    <div class="admin-form-group">
                        <label for="admin_notes">Notes (Optional)</label>
                        <textarea id="admin_notes" name="admin_notes" rows="3" class="admin-form-control"></textarea>
                    </div>
                    <div class="admin-form-group">
                        <button type="submit" class="admin-btn admin-btn-success">Confirm Event</button>
                        <button type="button" class="admin-btn admin-btn-light admin-modal-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Complete Event Modal -->
    <div id="completeEventModal" class="admin-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3>Mark Event as Completed</h3>
                <button class="admin-modal-close">&times;</button>
            </div>
            <div class="admin-modal-body">
                <p>Are you sure you want to mark this event as completed?</p>
                <form id="completeEventForm" action="update-event-status.php" method="post">
                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                    <input type="hidden" name="status" value="completed">
                    <div class="admin-form-group">
                        <label for="completion_notes">Completion Notes (Optional)</label>
                        <textarea id="completion_notes" name="admin_notes" rows="3" class="admin-form-control"></textarea>
                    </div>
                    <div class="admin-form-group">
                        <button type="submit" class="admin-btn admin-btn-info">Mark as Completed</button>
                        <button type="button" class="admin-btn admin-btn-light admin-modal-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Event Modal -->
    <div id="cancelEventModal" class="admin-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3>Cancel Event</h3>
                <button class="admin-modal-close">&times;</button>
            </div>
            <div class="admin-modal-body">
                <p>Are you sure you want to cancel this event?</p>
                <p>This will notify the client that their event has been cancelled.</p>
                <form id="cancelEventForm" action="update-event-status.php" method="post">
                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                    <input type="hidden" name="status" value="cancelled">
                    <div class="admin-form-group">
                        <label for="cancellation_reason">Reason for Cancellation</label>
                        <textarea id="cancellation_reason" name="admin_notes" rows="3" class="admin-form-control" style="resize: vertical;" required></textarea>
                    </div>
                    <div class="admin-form-group">
                        <button type="submit" class="admin-btn admin-btn-danger">Cancel Event</button>
                        <button type="button" class="admin-btn admin-btn-light admin-modal-cancel">Keep Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../admin-scripts/event-details.js"></script>
</body>

</html>