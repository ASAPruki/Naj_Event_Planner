<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = $_GET['id'];

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

// Get user's event count
$events_query = "SELECT 
                  COUNT(*) as total_events,
                  SUM(CASE WHEN status = 'pending' OR status IS NULL THEN 1 ELSE 0 END) as pending_events,
                  SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_events,
                  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_events,
                  SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_events
                FROM reservations 
                WHERE user_id = ? OR email = ?";
$stmt = $conn->prepare($events_query);
$stmt->bind_param("is", $user_id, $user['email']);
$stmt->execute();
$events_result = $stmt->get_result();
$events_stats = $events_result->fetch_assoc();
$stmt->close();

// Log admin activity
$action_details = "Viewed user details for user #" . $user_id;
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
    <title>User Details - Naj Events Admin</title>
    <link rel="stylesheet" href="../../styles/styles.css">
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
    <link rel="stylesheet" href="../admin-styles/user-details.css">
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
                <h1 class="admin-header-title">User Details</h1>
            </header>

            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-header-left">
                            <a href="users.php" class="admin-btn admin-btn-light">
                                <i class="fas fa-arrow-left"></i> Back to Users
                            </a>
                        </div>
                        <h2 class="admin-card-title">
                            User Profile: <?php echo $user['name']; ?>
                        </h2>
                        <div class="admin-card-header-right">
                            <a href="edit-user.php?id=<?php echo $user_id; ?>" class="admin-btn admin-btn-primary">
                                <i class="fas fa-edit"></i> Edit User
                            </a>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="user-profile-container">
                            <div class="user-profile-header">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </div>
                                <div class="user-basic-info">
                                    <h3><?php echo $user['name']; ?></h3>
                                    <p class="user-id">User ID: #<?php echo $user['id']; ?></p>
                                    <p class="user-since">Member since <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                                </div>
                            </div>

                            <div class="user-stats">
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $events_stats['total_events'] ?? 0; ?></div>
                                    <div class="stat-label">Total Events</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $events_stats['pending_events'] ?? 0; ?></div>
                                    <div class="stat-label">Pending</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $events_stats['confirmed_events'] ?? 0; ?></div>
                                    <div class="stat-label">Confirmed</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $events_stats['completed_events'] ?? 0; ?></div>
                                    <div class="stat-label">Completed</div>
                                </div>
                            </div>

                            <div class="user-details-section">
                                <h3 class="section-title">Contact Information</h3>
                                <div class="user-details-grid">
                                    <div class="detail-item">
                                        <div class="detail-label">Email Address</div>
                                        <div class="detail-value">
                                            <i class="fas fa-envelope"></i>
                                            <?php echo $user['email']; ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Phone Number</div>
                                        <div class="detail-value">
                                            <i class="fas fa-phone"></i>
                                            <?php echo !empty($user['phone']) ? $user['phone'] : 'Not provided'; ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Address</div>
                                        <div class="detail-value">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo !empty($user['address']) ? $user['address'] : 'Not provided'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="user-details-section">
                                <h3 class="section-title">Account Information</h3>
                                <div class="user-details-grid">
                                    <div class="detail-item">
                                        <div class="detail-label">Account Created</div>
                                        <div class="detail-value">
                                            <i class="fas fa-calendar-plus"></i>
                                            <?php echo date('F d, Y \a\t h:i A', strtotime($user['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Last Updated</div>
                                        <div class="detail-value">
                                            <i class="fas fa-clock"></i>
                                            <?php echo isset($user['updated_at']) ? date('F d, Y \a\t h:i A', strtotime($user['updated_at'])) : 'Never updated'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="user-actions">
                                <a href="edit-user.php?id=<?php echo $user_id; ?>" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-edit"></i> Edit User
                                </a>
                                <a href="user-events.php?user_id=<?php echo $user_id; ?>" class="admin-btn admin-btn-info">
                                    <i class="fas fa-calendar-alt"></i> View User's Events
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>