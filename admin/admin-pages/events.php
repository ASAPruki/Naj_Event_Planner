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

// Initialize variables
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query based on filters
$query = "SELECT r.*, u.name as user_name FROM reservations r LEFT JOIN users u ON r.user_id = u.id WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM reservations WHERE 1=1";

// Add filter conditions
$conditions = [];
$params = [];
$types = "";

if (!empty($filter_status)) {
    $conditions[] = "r.status = ?";
    $count_conditions[] = "status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($filter_type)) {
    $conditions[] = "r.event_type = ?";
    $count_conditions[] = "event_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if (!empty($search)) {
    $search_term = "%$search%";
    $conditions[] = "(r.name LIKE ? OR r.email LIKE ? OR r.phone LIKE ?)";
    $count_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

// Add conditions to queries
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
    $count_query .= " AND " . implode(" AND ", $count_conditions);
}

// Add order by
$query .= " ORDER BY r.created_at DESC";

// Prepare and execute count query
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);
$count_stmt->close();

// Add limit to main query
$query .= " LIMIT ?, ?";
$limit_params = [$offset, $per_page];
$limit_types = "ii";

// Prepare and execute main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    // Combine parameters and types
    $all_params = array_merge($params, $limit_params);
    $all_types = $types . $limit_types;
    $stmt->bind_param($all_types, ...$all_params);
} else {
    // Only limit parameters
    $stmt->bind_param($limit_types, ...$limit_params);
}

$stmt->execute();
$result = $stmt->get_result();
$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();

// Get event types for filter dropdown
$types_query = "SELECT DISTINCT event_type FROM reservations ORDER BY event_type";
$types_result = $conn->query($types_query);
$event_types = [];
while ($row = $types_result->fetch_assoc()) {
    $event_types[] = $row['event_type'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Management - Naj Events Admin</title>
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
                <h1 class="admin-header-title">Events Management</h1>
            </header>

            <div class="admin-content">
                <!-- Filters and Search -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Filter Events</h2>
                    </div>
                    <div class="admin-card-body">
                        <form method="get" action="events.php" class="admin-filters">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" class="admin-form-select">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>
                                <div style="flex: 1; min-width: 200px;">
                                    <label for="type">Event Type</label>
                                    <select id="type" name="type" class="admin-form-select">
                                        <option value="">All Types</option>
                                        <?php foreach ($event_types as $type): ?>
                                            <option value="<?php echo $type; ?>" <?php echo $filter_type === $type ? 'selected' : ''; ?>><?php echo ucfirst($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="flex: 2; min-width: 200px;">
                                    <label for="search">Search</label>
                                    <input type="text" id="search" name="search" class="admin-form-control" placeholder="Search by name, email or phone" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div style="display: flex; align-items: flex-end;">
                                    <button type="submit" class="admin-btn admin-btn-primary">Apply Filters</button>
                                    <a href="events.php" class="admin-btn admin-btn-light" style="margin-left: 10px;">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Events Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Events List</h2>
                        <div>
                            <span class="admin-badge primary"><?php echo $total_records; ?> Total Events</span>
                        </div>
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
                                        <th>Guests</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($events) > 0): ?>
                                        <?php foreach ($events as $event): ?>
                                            <tr>
                                                <td>#<?php echo $event['id']; ?></td>
                                                <td>
                                                    <?php echo $event['name']; ?><br>
                                                    <small><?php echo $event['email']; ?></small>
                                                </td>
                                                <td><?php echo ucfirst($event['event_type']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                                <td><?php echo $event['guests']; ?></td>
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
                                                            case 'missed':
                                                                $status_class = 'danger';
                                                                $status_text = 'Missed';
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
                                                <td><?php echo date('M d, Y', strtotime($event['created_at'])); ?></td>
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
                                            <td colspan="8" style="text-align: center;">No events found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <div class="admin-card-footer">
                            <div class="admin-pagination">
                                <?php if ($page > 1): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $filter_status; ?>&type=<?php echo $filter_type; ?>&search=<?php echo urlencode($search); ?>" class="admin-pagination-link">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&type=<?php echo $filter_type; ?>&search=<?php echo urlencode($search); ?>" class="admin-pagination-link <?php echo $i === $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </div>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $filter_status; ?>&type=<?php echo $filter_type; ?>&search=<?php echo urlencode($search); ?>" class="admin-pagination-link">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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