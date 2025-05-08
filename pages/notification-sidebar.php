<?php
// This file will be included in pages where the user is logged in

// Function to get unread notifications count
function getUnreadNotificationsCount($conn, $user_id)
{
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'];
}

// Function to get user notifications
function getUserNotifications($conn, $user_id, $limit = 10)
{
    $query = "SELECT n.*, r.event_type, r.event_date 
              FROM notifications n 
              LEFT JOIN reservations r ON n.event_id = r.id 
              WHERE n.user_id = ? 
              ORDER BY n.created_at DESC 
              LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    return $notifications;
}

// Get unread notifications count
$unread_count = 0;
$notifications = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Database connection parameters
    $host = "localhost";
    $username = "root";
    $password = "99Vm6tBhw";
    $database = "najevents_db";

    // Create connection
    $notif_conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if (!$conn->connect_error) {
        $unread_count = getUnreadNotificationsCount($notif_conn, $user_id);
        $notifications = getUserNotifications($notif_conn, $user_id);
        $notif_conn->close();
    }
}
?>

<script src="../scripts/notification.js"></script>
<script src="../scripts/script.js"></script>

<!-- Notification Bell in Header -->
<div class="notification-bell" id="notificationBell">
    <i class="fas fa-bell"></i>
    <?php if ($unread_count > 0): ?>
        <span class="notification-badge"><?php echo $unread_count; ?></span>
    <?php endif; ?>
</div>

<!-- Notification Sidebar -->
<div class="notification-sidebar" id="notificationSidebar">
    <div class="notification-header">
        <h3>Notifications</h3>
        <button class="close-notifications" id="closeNotifications">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="notification-content">
        <?php if (count($notifications) > 0): ?>
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
                        <p class="notification-time"><?php echo date('M d, g:i a', strtotime($notification['created_at'])); ?></p>
                    </div>
                    <?php if (!$notification['is_read']): ?>
                        <div class="notification-status"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="notification-actions">
                <button id="markAllRead" class="btn-mark-all">Mark all as read</button>
                <a href="all-notifications.php" class="view-all-link">View all notifications</a>
            </div>
        <?php else: ?>
            <div class="no-notifications">
                <div class="empty-icon">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <p>You don't have any notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Notification Overlay -->
<div class="notification-overlay" id="notificationOverlay"></div>