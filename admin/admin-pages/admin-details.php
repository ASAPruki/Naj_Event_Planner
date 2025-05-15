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

// Check if the logged-in admin is a super_admin
if ($admin_role !== 'super_admin') {
    header("Location: admin-dashboard.php");
    exit();
}

// Check if admin ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admins.php");
    exit();
}

$view_admin_id = (int)$_GET['id'];

require "../../APIs/connect.php";

// Get admin data
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $view_admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admins.php");
    exit();
}

$admin_data = $result->fetch_assoc();
$stmt->close();

// Log the activity
$action_details = "Viewed admin profile: " . $admin_data['name'];
$ip_address = $_SERVER['REMOTE_ADDR'];

$log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'view_admin', ?, ?)");
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
    <title>View Admin - Naj Events Admin</title>
    <link rel="stylesheet" href="../../styles/styles.css">
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../admin-styles/admin-details.css">
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
                        <a href="financials.php" class="admin-nav-link">
                            <i class="fas fa-dollar-sign admin-nav-icon" style="font-size: 1.3rem;"></i>
                            Financials
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="admins.php" class="admin-nav-link active">
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
                <h1 class="admin-header-title">View Admin</h1>
            </header>

            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Admin Profile</h2>
                        <div>
                            <a href="admins.php" class="admin-btn admin-btn-light admin-btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to Admins
                            </a>
                            <a href="edit-admin.php?id=<?php echo $view_admin_id; ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                                <i class="fas fa-edit"></i> Edit Admin
                            </a>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-profile-header">
                            <div class="admin-profile-avatar">
                                <?php echo strtoupper(substr($admin_data['name'], 0, 1)); ?>
                            </div>
                            <div class="admin-profile-info">
                                <h2><?php echo htmlspecialchars($admin_data['name']); ?></h2>
                                <span class="admin-profile-role <?php echo $admin_data['role']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $admin_data['role'])); ?>
                                </span>
                                <p>Admin ID: #<?php echo $admin_data['id']; ?></p>
                            </div>
                        </div>

                        <div class="admin-detail-grid">
                            <div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Email Address</div>
                                    <div class="admin-detail-value">
                                        <i class="fas fa-envelope" style="color: #6b7280; margin-right: 5px;"></i>
                                        <?php echo htmlspecialchars($admin_data['email']); ?>
                                    </div>
                                </div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Phone Number</div>
                                    <div class="admin-detail-value">
                                        <i class="fas fa-phone" style="color: #6b7280; margin-right: 5px;"></i>
                                        <?php echo htmlspecialchars($admin_data['phone']); ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Account Created</div>
                                    <div class="admin-detail-value">
                                        <i class="fas fa-calendar-plus" style="color: #6b7280; margin-right: 5px;"></i>
                                        <?php echo date('F d, Y', strtotime($admin_data['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Last Updated</div>
                                    <div class="admin-detail-value">
                                        <i class="fas fa-clock" style="color: #6b7280; margin-right: 5px;"></i>
                                        <?php
                                        echo isset($admin_data['updated_at']) && $admin_data['updated_at'] !== null
                                            ? date('F d, Y', strtotime($admin_data['updated_at']))
                                            : 'Never';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../admin-scripts/admin-details.js"></script>
</body>

</html>