<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

require "../../APIs/connect.php";

// Get dashboard statistics
// Total users
$users_query = "SELECT COUNT(*) as total_users FROM users";
$users_result = $conn->query($users_query);
$total_users = $users_result->fetch_assoc()['total_users'];

// Total events
$events_query = "SELECT COUNT(*) as total_events FROM reservations";
$events_result = $conn->query($events_query);
$total_events = $events_result->fetch_assoc()['total_events'];

// Pending events
$pending_query = "SELECT COUNT(*) as pending_events FROM reservations WHERE status = 'pending'";
$pending_result = $conn->query($pending_query);
$pending_events = $pending_result->fetch_assoc()['pending_events'];

// Upcoming events
$upcoming_query = "SELECT COUNT(*) as upcoming_events FROM reservations WHERE event_date >= CURDATE() AND (status = 'confirmed' OR status IS NULL)";
$upcoming_result = $conn->query($upcoming_query);
$upcoming_events = $upcoming_result->fetch_assoc()['upcoming_events'];

// Recent events
$recent_events_query = "SELECT r.*, u.name as user_name FROM reservations r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 5";
$recent_events_result = $conn->query($recent_events_query);
$recent_events = [];
while ($row = $recent_events_result->fetch_assoc()) {
    $recent_events[] = $row;
}

// Recent users
$recent_users_query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
$recent_users_result = $conn->query($recent_users_query);
$recent_users = [];
while ($row = $recent_users_result->fetch_assoc()) {
    $recent_users[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Naj Events</title>
    <link rel="stylesheet" href="../../styles/styles.css">
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                        <a href="admin-dashboard.php" class="admin-nav-link active">
                            <i class="fas fa-tachometer-alt admin-nav-icon"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="events.php" class="admin-nav-link">
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
                        <a href="user-reviews.php" class="admin-nav-link">
                            <i class="fas fa-star admin-nav-icon"></i>
                            User Reviews
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="financials.php" class="admin-nav-link">
                            <i class="fas fa-dollar-sign admin-nav-icon" style="font-size: 1.3rem;"></i>
                            Financials
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="admins.php" class="admin-nav-link">
                            <i class="fas fa-user-shield admin-nav-icon"></i>
                            Admin Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="admin-logs.php" class="admin-nav-link">
                            <i class="fas fa-file-alt admin-nav-icon"></i>
                            Admin Activity Logs
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
                <h1 class="admin-header-title">Dashboard</h1>
            </header>

            <div class="admin-content">
                <!-- Dashboard Stats -->
                <div class="admin-stats">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="admin-stat-info">
                            <div class="admin-stat-value"><?php echo $total_users; ?></div>
                            <div class="admin-stat-label">Total Users</div>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon success">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="admin-stat-info">
                            <div class="admin-stat-value"><?php echo $total_events; ?></div>
                            <div class="admin-stat-label">Total Events</div>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="admin-stat-info">
                            <div class="admin-stat-value"><?php echo $pending_events; ?></div>
                            <div class="admin-stat-label">Pending Events</div>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon info">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="admin-stat-info">
                            <div class="admin-stat-value"><?php echo $upcoming_events; ?></div>
                            <div class="admin-stat-label">Upcoming Events</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Events -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Recent Events</h2>
                        <a href="events.php" class="admin-btn admin-btn-primary admin-btn-sm">View All</a>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Event Type</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_events) > 0): ?>
                                        <?php foreach ($recent_events as $event): ?>
                                            <tr>
                                                <td>#<?php echo $event['id']; ?></td>
                                                <td><?php echo $event['name']; ?></td>
                                                <td><?php echo ucfirst($event['event_type']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
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
                                                    } else {
                                                        $status_class = 'warning';
                                                    }
                                                    ?>
                                                    <span class="admin-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                </td>
                                                <td>
                                                    <div class="admin-table-actions">
                                                        <a href="event-details.php?id=<?php echo $event['id']; ?>" class="admin-btn admin-btn-info admin-btn-sm">View</a>
                                                        <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="admin-btn admin-btn-primary admin-btn-sm">Edit</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No events found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Recent Users</h2>
                        <a href="users.php" class="admin-btn admin-btn-primary admin-btn-sm">View All</a>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_users) > 0): ?>
                                        <?php foreach ($recent_users as $user): ?>
                                            <tr>
                                                <td>#<?php echo $user['id']; ?></td>
                                                <td><?php echo $user['name']; ?></td>
                                                <td><?php echo $user['email']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <div class="admin-table-actions">
                                                        <a href="user-details.php?id=<?php echo $user['id']; ?>" class="admin-btn admin-btn-info admin-btn-sm">View</a>
                                                        <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="admin-btn admin-btn-primary admin-btn-sm">Edit</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No users found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar on mobile
        const toggleSidebar = document.getElementById('toggleSidebar');
        const adminSidebar = document.getElementById('adminSidebar');
        const adminMain = document.getElementById('adminMain');

        if (toggleSidebar) {
            toggleSidebar.addEventListener('click', function() {
                adminSidebar.classList.toggle('active');
                adminMain.classList.toggle('sidebar-active');
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const isClickInsideSidebar = adminSidebar.contains(event.target);
            const isClickInsideToggle = toggleSidebar.contains(event.target);

            if (window.innerWidth <= 992 && !isClickInsideSidebar && !isClickInsideToggle && adminSidebar.classList.contains('active')) {
                adminSidebar.classList.remove('active');
                adminMain.classList.remove('sidebar-active');
            }
        });
    </script>
</body>

</html>