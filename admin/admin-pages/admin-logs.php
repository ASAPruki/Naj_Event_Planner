<?php
session_start();
require '../../APIs/connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

// Check if user is a super admin
if ($_SESSION['admin_role'] !== 'super_admin') {
    header('Location: admin-dashboard.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, role FROM admins WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
if ($admin_result->num_rows > 0) {
    $admin_data = $admin_result->fetch_assoc();
    $admin_name = $admin_data['name'];
    $admin_role = $admin_data['role'];
} else {
    $admin_name = "Admin";
    $admin_role = "Unknown";
}


// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Filtering
$filter_admin = isset($_GET['admin']) ? $_GET['admin'] : '';
$filter_action = isset($_GET['action']) ? $_GET['action'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Build query
$query = "SELECT al.*, a.name as admin_name 
          FROM admin_activity_log al 
          LEFT JOIN admins a ON al.admin_id = a.id 
          WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM admin_activity_log WHERE 1=1";
$params = [];

if (!empty($filter_admin)) {
    $query .= " AND al.admin_id = ?";
    $count_query .= " AND admin_id = ?";
    $params[] = $filter_admin;
}

if (!empty($filter_action)) {
    $query .= " AND al.action_type = ?";
    $count_query .= " AND action_type = ?";
    $params[] = $filter_action;
}


if (!empty($filter_date)) {
    $query .= " AND DATE(al.created_at) = ?";
    $count_query .= " AND DATE(created_at) = ?";
    $params[] = $filter_date;
}

$query .= " ORDER BY al.created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $types = str_repeat('s', count($params) - 2) . 'ii';
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get total records for pagination
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_params = array_slice($params, 0, -2);
    if (!empty($count_params)) {
        $types = str_repeat('s', count($count_params));
        $count_stmt->bind_param($types, ...$count_params);
    }
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get all admins for filter dropdown
$admins_query = "SELECT id, name FROM admins ORDER BY name";
$admins_result = $conn->query($admins_query);

// Get unique actions for filter dropdown
$actions_query = "SELECT DISTINCT action_type FROM admin_activity_log ORDER BY action_type";
$actions_result = $conn->query($actions_query);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin ACtivities Logs - Naj Events Admin</title>
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
    <link rel="stylesheet" href="../../styles/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .action-delete_accessory,
        .action-delete_admin {
            background-color: #ffdddd;
            color: #c00;
        }

        .action-update,
        .action-update_accessory,
        .action-update_admin,
        .action-update_status,
        .action-user_update {
            background-color: #e0f7fa;
            color: #00796b;
        }

        .action-add_admin,
        .action-add_user,
        .action-add_accessory {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .action-view {
            background-color: rgb(245, 236, 189);
            color: rgb(109, 125, 46);
        }

        .action-block {
            background-color: #f8d7da;
            color: #842029;
        }

        .action-unblock {
            background-color: #d1fae5;
            color: #065f46;
        }

        .action-login {
            background-color: #dbeafe;
            color: #1e40af;
        }
    </style>

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
                        <a href="admins.php" class="admin-nav-link">
                            <i class="fas fa-user-shield admin-nav-icon"></i>
                            Admin Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="admin-logs.php" class="admin-nav-link active">
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
                <!-- Filters and Info -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Activity Logs</h2>
                        <div>
                            <span class="admin-badge primary">Total Records: <?php echo $total_records; ?></span>
                        </div>
                    </div>

                    <div class="admin-card-body">
                        <form method="GET" action="" class="admin-filters">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <label for="admin">Admin</label>
                                    <select name="admin" id="admin" class="admin-form-select">
                                        <option value="">All Admins</option>
                                        <?php while ($admin = $admins_result->fetch_assoc()): ?>
                                            <option value="<?php echo $admin['id']; ?>" <?php echo $filter_admin == $admin['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($admin['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div style="flex: 1; min-width: 200px;">
                                    <label for="action">Action</label>
                                    <select name="action" id="action" class="admin-form-select">
                                        <option value="">All Actions</option>
                                        <?php while ($action = $actions_result->fetch_assoc()): ?>
                                            <option value="<?php echo $action['action_type']; ?>" <?php echo $filter_action == $action['action_type'] ? 'selected' : ''; ?>>
                                                <?php echo ucfirst(htmlspecialchars($action['action_type'])); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div style="flex: 1; min-width: 200px;">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" id="date" class="admin-form-control" value="<?php echo $filter_date; ?>">
                                </div>

                                <div style="display: flex; align-items: flex-end;">
                                    <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
                                    <a href="admin-logs.php" class="admin-btn admin-btn-light" style="margin-left: 10px;">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Logs Table</h2>
                    </div>
                    <div class="admin-card-body">
                        <?php if ($result->num_rows > 0): ?>
                            <div class="admin-table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Admin</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                            <th>IP Address</th>
                                            <th>Timestamp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($log = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $log['id']; ?></td>
                                                <td><?php echo htmlspecialchars($log['admin_name'] ?? 'Unknown'); ?></td>
                                                <td>
                                                    <span class="admin-badge <?php echo 'action-' . strtolower($log['action_type']); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($log['action_type'])); ?>
                                                    </span>
                                                </td>
                                                <td class="details-cell" title="<?php echo htmlspecialchars($log['action_details']); ?>">
                                                    <?php echo htmlspecialchars($log['action_details']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div style="padding: 20px; text-align: center;">
                                <p>No activity logs found.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="admin-card-footer">
                            <div class="admin-pagination">
                                <?php if ($page > 1): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $page - 1; ?>&admin=<?php echo $filter_admin; ?>&action=<?php echo $filter_action; ?>&date=<?php echo $filter_date; ?>" class="admin-pagination-link">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if ($page > 1): ?> <!-- Skips 10 pages to the left -->
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo max($page - 10, 1); ?>&admin=<?php echo $filter_admin; ?>&action=<?php echo $filter_action; ?>&date=<?php echo $filter_date; ?>" class="admin-pagination-link">
                                            <i class="fas fa-angles-left"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>


                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $i; ?>&admin=<?php echo $filter_admin; ?>&action=<?php echo $filter_action; ?>&date=<?php echo $filter_date; ?>" class="admin-pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </div>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $page + 1; ?>&admin=<?php echo $filter_admin; ?>&action=<?php echo $filter_action; ?>&date=<?php echo $filter_date; ?>" class="admin-pagination-link">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if ($page < $total_pages): ?> <!-- Skips 10 pages to the right -->
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo min($page + 10, $total_pages); ?>&admin=<?php echo $filter_admin; ?>&action=<?php echo $filter_action; ?>&date=<?php echo $filter_date; ?>" class="admin-pagination-link">
                                            <i class="fas fa-angles-right"></i>
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