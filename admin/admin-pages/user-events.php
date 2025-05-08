<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header("Location: users.php");
    exit();
}

$user_id = $_GET['user_id'];

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

require "../../APIs/connect.php";

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows === 0) {
    header("Location: users.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Set up pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Set up filtering
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date_range']) ? $_GET['date_range'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query conditions
$conditions = ["(user_id = ? OR email = ?)"];
$params = [$user_id, $user['email']];
$types = "is";

if (!empty($status_filter)) {
    $conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'today':
            $conditions[] = "DATE(event_date) = CURDATE()";
            break;
        case 'tomorrow':
            $conditions[] = "DATE(event_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'this_week':
            $conditions[] = "YEARWEEK(event_date, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'next_week':
            $conditions[] = "YEARWEEK(event_date, 1) = YEARWEEK(DATE_ADD(CURDATE(), INTERVAL 1 WEEK), 1)";
            break;
        case 'this_month':
            $conditions[] = "MONTH(event_date) = MONTH(CURDATE()) AND YEAR(event_date) = YEAR(CURDATE())";
            break;
        case 'next_month':
            $conditions[] = "MONTH(event_date) = MONTH(DATE_ADD(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(event_date) = YEAR(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))";
            break;
        case 'past':
            $conditions[] = "event_date < CURDATE()";
            break;
        case 'future':
            $conditions[] = "event_date >= CURDATE()";
            break;
    }
}

if (!empty($search)) {
    $search_term = "%$search%";
    $conditions[] = "(name LIKE ? OR event_type LIKE ? OR venue LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

// Combine conditions
$where_clause = implode(" AND ", $conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM reservations WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();

// Get events for this page
$events_query = "SELECT r.* 
                FROM reservations r
                WHERE $where_clause
                ORDER BY r.event_date DESC, r.created_at DESC
                LIMIT ?, ?";
$events_stmt = $conn->prepare($events_query);
$events_stmt->bind_param($types . "ii", ...[...$params, $offset, $limit]);
$events_stmt->execute();
$events_result = $events_stmt->get_result();
$events = [];
while ($row = $events_result->fetch_assoc()) {
    $events[] = $row;
}
$events_stmt->close();

// Get event status counts
$status_counts_query = "SELECT 
                        status,
                        COUNT(*) as count
                    FROM reservations 
                    WHERE user_id = ? OR email = ?
                    GROUP BY status";
$status_stmt = $conn->prepare($status_counts_query);
$status_stmt->bind_param("is", $user_id, $user['email']);
$status_stmt->execute();
$status_result = $status_stmt->get_result();
$status_counts = [];
while ($row = $status_result->fetch_assoc()) {
    $status_counts[$row['status'] ?: 'pending'] = $row['count'];
}
$status_stmt->close();

// Log admin activity
$action_details = "Viewed events for user #" . $user_id;
$log_query = "INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'view', ?, ?)";
$log_stmt = $conn->prepare($log_query);
$ip_address = $_SERVER['REMOTE_ADDR'];
$log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
$log_stmt->execute();
$log_stmt->close();

$conn->close();

// Helper function to get status badge class
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'confirmed':
            return 'badge-success';
        case 'pending':
        case '':
        case null:
            return 'badge-warning';
        case 'cancelled':
            return 'badge-danger';
        case 'completed':
            return 'badge-info';
        default:
            return 'badge-secondary';
    }
}

// Helper function to format currency
function formatCurrency($amount)
{
    return '$' . number_format($amount, 2);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Events - Naj Events Admin</title>
    <link rel="stylesheet" href="../../styles/styles.css">
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
    <link rel="stylesheet" href="../admin-styles/user-events.css">
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
                        <a href="admin-dashboard.php" class="admin-nav-link">
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
                        <a href="users.php" class="admin-nav-link active">
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
                <h1 class="admin-header-title">User Events</h1>
            </header>

            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-header-left">
                            <a href="user-details.php?id=<?php echo $user_id; ?>" class="admin-btn admin-btn-light">
                                <i class="fas fa-arrow-left"></i> Back to User Details
                            </a>
                        </div>
                        <h2 class="admin-card-title">
                            Events for <?php echo $user['name']; ?>
                        </h2>
                        <div class="admin-card-header-right">
                            <a href="events.php" class="admin-btn admin-btn-primary">
                                <i class="fas fa-calendar-alt"></i> All Events
                            </a>
                        </div>
                    </div>

                    <div class="admin-card-body">
                        <!-- User Info Summary -->
                        <div class="user-summary">
                            <div class="user-summary-avatar">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <div class="user-summary-info">
                                <h3><?php echo $user['name']; ?></h3>
                                <p><i class="fas fa-envelope"></i> <?php echo $user['email']; ?></p>
                                <p><i class="fas fa-phone"></i> <?php echo !empty($user['phone']) ? $user['phone'] : 'Not provided'; ?></p>
                            </div>
                        </div>

                        <!-- Status Filters -->
                        <div class="status-filter-tabs">
                            <a href="?user_id=<?php echo $user_id; ?>" class="status-tab <?php echo empty($status_filter) ? 'active' : ''; ?>">
                                All
                                <span class="status-count"><?php echo $total_records; ?></span>
                            </a>
                            <a href="?user_id=<?php echo $user_id; ?>&status=pending" class="status-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                                Pending
                                <span class="status-count"><?php echo $status_counts['pending'] ?? 0; ?></span>
                            </a>
                            <a href="?user_id=<?php echo $user_id; ?>&status=confirmed" class="status-tab <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">
                                Confirmed
                                <span class="status-count"><?php echo $status_counts['confirmed'] ?? 0; ?></span>
                            </a>
                            <a href="?user_id=<?php echo $user_id; ?>&status=completed" class="status-tab <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                                Completed
                                <span class="status-count"><?php echo $status_counts['completed'] ?? 0; ?></span>
                            </a>
                            <a href="?user_id=<?php echo $user_id; ?>&status=cancelled" class="status-tab <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                                Cancelled
                                <span class="status-count"><?php echo $status_counts['cancelled'] ?? 0; ?></span>
                            </a>
                        </div>

                        <!-- Search and Filter -->
                        <div class="admin-filters">
                            <form action="" method="get" class="admin-filter-form">
                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                <?php if (!empty($status_filter)): ?>
                                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                                <?php endif; ?>

                                <div class="admin-filter-group">
                                    <div class="admin-search-input">
                                        <input type="text" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="admin-search-btn">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>

                                    <select name="date_range" class="admin-select" onchange="this.form.submit()">
                                        <option value="" <?php echo empty($date_filter) ? 'selected' : ''; ?>>All Dates</option>
                                        <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                        <option value="tomorrow" <?php echo $date_filter === 'tomorrow' ? 'selected' : ''; ?>>Tomorrow</option>
                                        <option value="this_week" <?php echo $date_filter === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                                        <option value="next_week" <?php echo $date_filter === 'next_week' ? 'selected' : ''; ?>>Next Week</option>
                                        <option value="this_month" <?php echo $date_filter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                        <option value="next_month" <?php echo $date_filter === 'next_month' ? 'selected' : ''; ?>>Next Month</option>
                                        <option value="past" <?php echo $date_filter === 'past' ? 'selected' : ''; ?>>Past Events</option>
                                        <option value="future" <?php echo $date_filter === 'future' ? 'selected' : ''; ?>>Future Events</option>
                                    </select>

                                    <?php if (!empty($search) || !empty($date_filter)): ?>
                                        <a href="?user_id=<?php echo $user_id; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="admin-btn admin-btn-light">
                                            <i class="fas fa-times"></i> Clear Filters
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <?php if (empty($events)): ?>
                            <div class="admin-empty-state">
                                <div class="admin-empty-state-icon">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                                <h3>No Events Found</h3>
                                <p>This user has no events matching your current filters.</p>
                                <?php if (!empty($search) || !empty($date_filter) || !empty($status_filter)): ?>
                                    <a href="?user_id=<?php echo $user_id; ?>" class="admin-btn admin-btn-primary">
                                        <i class="fas fa-sync"></i> Clear All Filters
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Events List -->
                            <div class="events-list">
                                <?php foreach ($events as $event): ?>
                                    <div class="event-card">
                                        <div class="event-card-header">
                                            <div class="event-date">
                                                <div class="event-date-day"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                                                <div class="event-date-month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                                            </div>
                                            <div class="event-title">
                                                <h3><?php echo htmlspecialchars($event['event_type']); ?></h3>
                                                <p class="event-type"><?php echo htmlspecialchars($event['event_type']); ?></p>
                                            </div>
                                            <div class="event-status">
                                                <span class="status-badge <?php echo getStatusBadgeClass($event['status']); ?>">
                                                    <?php echo ucfirst($event['status'] ?: 'Pending'); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="event-card-body">
                                            <div class="event-details">
                                                <div class="event-detail">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span><?php echo htmlspecialchars($event['venue']); ?></span>
                                                </div>
                                                <div class="event-detail">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?php echo $event['event_date']; ?></span>
                                                </div>
                                                <div class="event-detail">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?php echo $event['event_time']; ?></span>
                                                </div>
                                                <div class="event-detail">
                                                    <i class="fas fa-users"></i>
                                                    <span><?php echo htmlspecialchars($event['guests']); ?> Guests</span>
                                                </div>
                                            </div>
                                            <div class="event-actions">
                                                <a href="event-details.php?id=<?php echo $event['id']; ?>" class="admin-btn admin-btn-sm admin-btn-primary">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="admin-pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?user_id=<?php echo $user_id; ?>&page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($date_filter) ? '&date_range=' . $date_filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="admin-pagination-btn">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    <?php endif; ?>

                                    <div class="admin-pagination-pages">
                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $start_page + 4);
                                        if ($end_page - $start_page < 4 && $start_page > 1) {
                                            $start_page = max(1, $end_page - 4);
                                        }

                                        for ($i = $start_page; $i <= $end_page; $i++):
                                        ?>
                                            <a href="?user_id=<?php echo $user_id; ?>&page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($date_filter) ? '&date_range=' . $date_filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="admin-pagination-page <?php echo $i === $page ? 'active' : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                    </div>

                                    <?php if ($page < $total_pages): ?>
                                        <a href="?user_id=<?php echo $user_id; ?>&page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($date_filter) ? '&date_range=' . $date_filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="admin-pagination-btn">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="admin-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3>Update Event Status</h3>
                <button class="admin-modal-close">&times;</button>
            </div>
            <div class="admin-modal-body">
                <form id="statusForm" action="update-event-status.php" method="post">
                    <input type="hidden" name="event_id" id="event_id">
                    <input type="hidden" name="redirect_url" value="user-events.php?user_id=<?php echo $user_id; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($date_filter) ? '&date_range=' . $date_filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($page) ? '&page=' . $page : ''; ?>">

                    <div class="admin-form-group">
                        <label for="event_status">Status</label>
                        <select id="event_status" name="status" class="admin-form-control">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="admin-form-group">
                        <label for="status_notes">Notes (Optional)</label>
                        <textarea id="status_notes" name="notes" rows="3" class="admin-form-control"></textarea>
                    </div>

                    <div class="admin-form-group">
                        <label>
                            <input type="checkbox" name="notify_user" value="1" checked>
                            Notify user about this status change
                        </label>
                    </div>

                    <div class="admin-form-group">
                        <button type="submit" class="admin-btn admin-btn-primary">Update Status</button>
                        <button type="button" class="admin-btn admin-btn-light admin-modal-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../admin-scripts/user-events.js"></script>

</body>

</html>