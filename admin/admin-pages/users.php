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
$success_message = "";
$error_message = "";

// Process form submission for adding a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(preg_replace('/\s+/', '', $_POST['phone']));
    $password = $_POST['password'];
    $address = htmlspecialchars($_POST['address']);

    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        $error_message = "Name, email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (strlen($_POST['password']) < 6) {
        $error_message = "Password must be at least 6 characters.";
    } elseif (!preg_match("/^\d{2}\s?\d{3}\s?\d{3}$/", $phone)) { //Check if the number is in this lebanese numbers format
        $error_message = "Please enter a valid phone number.";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "Email already exists.";
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $name, $email, $phone, $password, $address);

            if ($stmt->execute()) {
                $success_message = "User added successfully!";

                // Log the activity
                $action_details = "Admin added new user: $name";
                $ip_address = $_SERVER['REMOTE_ADDR'];

                $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'add_user', ?, ?)");
                $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
                $log_stmt->execute();
                $log_stmt->close();
            } else {
                $error_message = "Error adding user: " . $stmt->error;
            }

            $stmt->close();
        }

        $check_stmt->close();
    }
}

// Initialize variables for user listing
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query based on search
$query = "SELECT * FROM users WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM users WHERE 1=1";

if (!empty($search)) {
    $search_term = "%$search%";
    $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $count_query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";

    // Prepare and execute count query with search parameters
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $count_stmt->execute();
} else {
    // Execute count query without parameters
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute();
}

$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);
$count_stmt->close();

// Add order by and limit
$query .= " ORDER BY created_at DESC LIMIT ?, ?";

// Prepare and execute main query
$stmt = $conn->prepare($query);
if (!empty($search)) {
    // With search parameters
    $stmt->bind_param("sssii", $search_term, $search_term, $search_term, $offset, $per_page);
} else {
    // Without search parameters, only limit
    $stmt->bind_param("ii", $offset, $per_page);
}

$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Naj Events Admin</title>
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
                <h1 class="admin-header-title">User Management</h1>
            </header>

            <div class="admin-content">
                <?php if (!empty($success_message)): ?>
                    <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Add New User Form -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Add New User</h2>
                        <button id="toggleAddForm" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-plus"></i> Show Form
                        </button>
                    </div>
                    <div class="admin-card-body" id="addUserForm" style="display: none;">
                        <div class="security-warning" style="background-color: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem;">
                            <strong>Note:</strong> Passwords are NOT securely hashed before storage for security purposes.
                        </div>
                        <form id="add-new-user-form" method="post" action="users.php">
                            <input type="hidden" name="action" value="add">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div class="admin-form-group">
                                    <label for="name">Full Name*</label>
                                    <input type="text" id="name" name="name" class="admin-form-control" required>
                                </div>
                                <div class="admin-form-group">
                                    <label for="email">Email*</label>
                                    <input type="email" id="email" name="email" class="admin-form-control" required>
                                </div>
                                <div class="admin-form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="admin-form-control">
                                </div>
                                <div class="admin-form-group">
                                    <label for="password">Password*</label>
                                    <input type="password" id="password" name="password" class="admin-form-control" required>
                                </div>
                                <div class="admin-form-group" style="grid-column: 1 / -1;">
                                    <label for="address">Address</label>
                                    <textarea id="address" name="address" class="admin-form-textarea" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="admin-form-group" style="margin-top: 20px;">
                                <button type="submit" class="admin-btn admin-btn-primary">Add User</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Search Users -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Search Users</h2>
                    </div>
                    <div class="admin-card-body">
                        <form method="get" action="users.php" class="admin-filters">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <label for="search">Search</label>
                                    <input type="text" id="search" name="search" class="admin-form-control" placeholder="Search by name, email or phone" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div style="display: flex; align-items: flex-end;">
                                    <button type="submit" class="admin-btn admin-btn-primary">Search</button>
                                    <a href="users.php" class="admin-btn admin-btn-light" style="margin-left: 10px;">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Users List</h2>
                        <div>
                            <span class="admin-badge primary"><?php echo $total_records; ?> Total Users</span>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($users) > 0): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>#<?php echo $user['id']; ?></td>
                                                <td><?php echo $user['name']; ?></td>
                                                <td><?php echo $user['email']; ?></td>
                                                <td><?php echo isset($user['phone']) ? $user['phone'] : '-'; ?></td>
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
                                            <td colspan="6" style="text-align: center;">No users found</td>
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
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="admin-pagination-link">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="admin-pagination-link <?php echo $i === $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </div>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="admin-pagination-link">
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
</body>

<script src="../admin-scripts/users.js"></script>

</html>