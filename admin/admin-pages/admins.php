<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin-login.php");
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

require "../../APIs/connect.php";

// Initialize variables
$success_message = "";
$error_message = "";
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Process form submission for adding new admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(preg_replace('/\s+/', '', $_POST['phone'])); // Gets rid of the spaces in the phone number
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validate form inputs
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($role)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters.";
    } elseif (!preg_match("/^\d{2}\s?\d{3}\s?\d{3}$/", $phone)) { //Check if the number is in this lebanese numbers format
        $error_message = "Please enter a valid phone number.";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "Email already exists.";
        } else {
            // Insert new admin
            $stmt = $conn->prepare("INSERT INTO admins (name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $name, $email, $phone, $password, $role);

            if ($stmt->execute()) {
                $success_message = "Admin added successfully!";

                // Log the activity
                $action_details = "Admin added new admin: $name";
                $ip_address = $_SERVER['REMOTE_ADDR'];

                $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'add_admin', ?, ?)");
                $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
                $log_stmt->execute();
                $log_stmt->close();
            } else {
                $error_message = "Error adding admin: " . $stmt->error;
            }

            $stmt->close();
        }

        $check_stmt->close();
    }
}

// Process form submission for deleting admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['admin_id']) || empty($_POST['admin_id'])) {
        die("Error: Admin ID is missing!");
    }

    $admin_to_delete = (int)$_POST['admin_id'];

    // Prevent deleting self
    if ($admin_to_delete === $admin_id) {
        $error_message = "You cannot delete your own account.";
    } else {
        // Get admin name for logging
        $name_stmt = $conn->prepare("SELECT name FROM admins WHERE id = ?");
        $name_stmt->bind_param("i", $admin_to_delete);
        $name_stmt->execute();
        $name_result = $name_stmt->get_result();
        $admin_data = $name_result->fetch_assoc();
        $name_stmt->close();

        $admin_name_to_delete = $admin_data ? $admin_data['name'] : "Unknown";

        // Delete admin
        $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin_to_delete);

        if ($stmt->execute()) {
            $success_message = "Admin deleted successfully!";

            // Log the activity
            $action_details = "Admin deleted admin: $admin_name_to_delete";
            $ip_address = $_SERVER['REMOTE_ADDR'];

            $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'delete_admin', ?, ?)");
            $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
            $log_stmt->execute();
            $log_stmt->close();
        } else {
            $error_message = "Error deleting admin: " . $stmt->error;
        }

        $stmt->close();
    }
}


// Build query based on search
$query = "SELECT * FROM admins WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM admins WHERE 1=1";

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
$admins = [];
while ($row = $result->fetch_assoc()) {
    $admins[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Naj Events Admin</title>
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
                <h1 class="admin-header-title">Admin Management</h1>
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

                <!-- Add New Admin Form -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Add New Admin</h2>
                        <button id="toggleAddForm" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-plus"></i> Show Form
                        </button>
                    </div>
                    <div class="admin-card-body" id="addAdminForm" style="display: none;">
                        <form method="post" action="admins.php" onsubmit="return validatePhone();">
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
                                    <label for="phone">Phone Number*</label>
                                    <input type="tel" id="phone" name="phone" class="admin-form-control" required>
                                </div>
                                <div class="admin-form-group">
                                    <label for="password">Password*</label>
                                    <input type="text" id="password" name="password" class="admin-form-control" required>
                                </div>
                                <div class="admin-form-group">
                                    <label for="role">Role*</label>
                                    <select id="role" name="role" class="admin-form-select" required>
                                        <option value="">Select Role</option>
                                        <option value="super_admin">Super Admin</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="admin-form-group" style="margin-top: 20px;">
                                <button type="submit" class="admin-btn admin-btn-primary">Add Admin</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Search Admins -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Search Admins</h2>
                    </div>
                    <div class="admin-card-body">
                        <form method="get" action="admins.php" class="admin-filters">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <label for="search">Search</label>
                                    <input type="text" id="search" name="search" class="admin-form-control" placeholder="Search by name, email or phone" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div style="display: flex; align-items: flex-end;">
                                    <button type="submit" class="admin-btn admin-btn-primary">Search</button>
                                    <a href="admins.php" class="admin-btn admin-btn-light" style="margin-left: 10px;">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Admins Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Admins List</h2>
                        <div>
                            <span class="admin-badge primary"><?php echo $total_records; ?> Total Admins</span>
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
                                        <th>Role</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($admins) > 0): ?>
                                        <?php foreach ($admins as $admin_user): ?>
                                            <tr>
                                                <td>#<?php echo $admin_user['id']; ?></td>
                                                <td><?php echo $admin_user['name']; ?></td>
                                                <td><?php echo $admin_user['email']; ?></td>
                                                <td><?php echo $admin_user['phone']; ?></td>
                                                <td>
                                                    <?php
                                                    $role_class = '';
                                                    switch ($admin_user['role']) {
                                                        case 'super_admin':
                                                            $role_class = 'primary';
                                                            break;
                                                        case 'admin':
                                                            $role_class = 'success';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="admin-badge <?php echo $role_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $admin_user['role'])); ?></span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($admin_user['created_at'])); ?></td>
                                                <td>
                                                    <div class="admin-table-actions">
                                                        <a href="edit-admin.php?id=<?php echo $admin_user['id']; ?>" class="admin-btn admin-btn-primary admin-btn-sm">Edit</a>
                                                        <?php if ($admin_user['id'] !== $admin_id): ?>
                                                            <button type="button" class="admin-btn admin-btn-danger admin-btn-sm delete-admin" data-id="<?php echo $admin_user['id']; ?>" data-name="<?php echo $admin_user['name']; ?>">Delete</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center;">No admins found</td>
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

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 5px;">
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete the admin: <span id="deleteAdminName"></span>?</p>
            <form method="post" action="admins.php" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="admin_id" id="deleteAdminId">
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" id="cancelDelete" class="admin-btn admin-btn-light">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../admin-scripts/admins.js"></script>
</body>

</html>