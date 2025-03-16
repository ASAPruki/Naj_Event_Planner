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

// Process form submission for adding a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $address = $_POST['address'];

    // Insert new user
    $insert_query = "INSERT INTO users (name, email, phone, password, address, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("sssss", $name, $email, $phone, $password, $address);

    if ($insert_stmt->execute()) {
        // Log admin activity
        $activity = "Added new user: $name (Email: $email)";
        $log_query = "INSERT INTO admin_activity_log (admin_id, activity, timestamp) VALUES (?, ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("is", $admin_id, $activity);
        $log_stmt->execute();

        // Set success message
        $success_message = "User added successfully!";
    } else {
        // Set error message
        $error_message = "Error adding user: " . $insert_stmt->error;
    }

    $insert_stmt->close();
}

// Initialize variables
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
    <style>
        /* Modal Styles */
        .admin-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            overflow-y: auto;
            padding: 30px 0;
        }

        .admin-modal-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .admin-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: rgba(63, 81, 181, 0.05);
        }

        .admin-modal-title {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--admin-dark);
            margin: 0;
        }

        .admin-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--admin-gray);
            transition: var(--admin-transition);
        }

        .admin-modal-close:hover {
            color: var(--admin-danger);
        }

        .admin-modal-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .admin-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Form Grid */
        .admin-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .admin-form-grid .admin-form-group:last-child {
            grid-column: span 2;
        }

        @media (max-width: 768px) {
            .admin-form-grid {
                grid-template-columns: 1fr;
            }

            .admin-form-grid .admin-form-group:last-child {
                grid-column: span 1;
            }
        }

        /* Alert Messages */
        .admin-alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .admin-alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--admin-success);
            border: 1px solid rgba(76, 175, 80, 0.2);
        }

        .admin-alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--admin-danger);
            border: 1px solid rgba(244, 67, 54, 0.2);
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
                    <li class="admin-nav-item">
                        <a href="settings.php" class="admin-nav-link">
                            <i class="fas fa-cog admin-nav-icon"></i>
                            Settings
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
                <?php if (isset($success_message)): ?>
                    <div class="admin-alert admin-alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="admin-alert admin-alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Search and Add User -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Search Users</h2>
                        <button id="openAddUserModal" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-plus"></i> Add New User
                        </button>
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

    <!-- Add User Modal -->
    <div id="addUserModal" class="admin-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3 class="admin-modal-title">Add New User</h3>
                <button type="button" class="admin-modal-close" id="closeAddUserModal">&times;</button>
            </div>
            <form method="post" action="users.php">
                <div class="admin-modal-body">
                    <div class="admin-form-grid">
                        <div class="admin-form-group">
                            <label for="name">Full Name <span style="color: red;">*</span></label>
                            <input type="text" id="name" name="name" class="admin-form-control" required>
                        </div>
                        <div class="admin-form-group">
                            <label for="email">Email Address <span style="color: red;">*</span></label>
                            <input type="email" id="email" name="email" class="admin-form-control" required>
                        </div>
                        <div class="admin-form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="admin-form-control">
                        </div>
                        <div class="admin-form-group">
                            <label for="password">Password <span style="color: red;">*</span></label>
                            <input type="password" id="password" name="password" class="admin-form-control" required>
                        </div>
                        <div class="admin-form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" class="admin-form-textarea" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="admin-modal-footer">
                    <button type="button" class="admin-btn admin-btn-light" id="cancelAddUser">Cancel</button>
                    <button type="submit" name="add_user" class="admin-btn admin-btn-primary">Add User</button>
                </div>
            </form>
        </div>
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

        // Modal functionality
        const addUserModal = document.getElementById('addUserModal');
        const openAddUserModal = document.getElementById('openAddUserModal');
        const closeAddUserModal = document.getElementById('closeAddUserModal');
        const cancelAddUser = document.getElementById('cancelAddUser');

        // Open modal
        openAddUserModal.addEventListener('click', function() {
            addUserModal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
        });

        // Close modal functions
        function closeModal() {
            addUserModal.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
        }

        closeAddUserModal.addEventListener('click', closeModal);
        cancelAddUser.addEventListener('click', closeModal);

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === addUserModal) {
                closeModal();
            }
        });

        // Prevent closing when clicking inside the modal content
        addUserModal.querySelector('.admin-modal-content').addEventListener('click', function(event) {
            event.stopPropagation();
        });

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.admin-alert');
        if (alerts.length > 0) {
            setTimeout(function() {
                alerts.forEach(function(alert) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 5000);
        }
    </script>
</body>

</html>