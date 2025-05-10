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

// Check if admin ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admins.php");
    exit();
}

$edit_admin_id = (int)$_GET['id'];

require "../../APIs/connect.php";

// Initialize variables
$success_message = "";
$error_message = "";
$admin_data = null;

// Get admin data
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $edit_admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admins.php");
    exit();
}

$admin_data = $result->fetch_assoc();
$stmt->close();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($_POST['phone']);
    $role = $_POST['role'];
    $change_password = isset($_POST['change_password']) && $_POST['change_password'] == '1';
    $new_password = $_POST['new_password'] ?? '';

    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($role)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif ($change_password && strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters.";
    } elseif (!preg_match("/^\d{2}\s?\d{3}\s?\d{3}$/", $phone)) { //Check if the number is in this lebanese numbers format
        $error_message = "Please enter a valid phone number.";
    } else {
        // Check if email already exists (excluding current admin)
        $check_stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $edit_admin_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "Email already exists.";
        } else {
            // Update admin data
            if ($change_password) {
                $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, phone = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $email, $phone, $role, $new_password, $edit_admin_id);
            } else {
                $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $email, $phone, $role, $edit_admin_id);
            }

            if ($stmt->execute()) {
                $success_message = "Admin updated successfully!";

                // Update admin data for display
                $admin_data['name'] = $name;
                $admin_data['email'] = $email;
                $admin_data['phone'] = $phone;
                $admin_data['role'] = $role;

                // Log the activity
                $action_details = "Admin updated admin: $name";
                $ip_address = $_SERVER['REMOTE_ADDR'];

                $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'update_admin', ?, ?)");
                $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
                $log_stmt->execute();
                $log_stmt->close();
            } else {
                $error_message = "Error updating admin: " . $stmt->error;
            }

            $stmt->close();
        }

        $check_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin - Naj Events Admin</title>
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
                        <a href="users.php" class="admin-nav-link">
                            <i class="fas fa-users admin-nav-icon"></i>
                            User Management
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="admins.php" class="admin-nav-link active">
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
                <h1 class="admin-header-title">Edit Admin</h1>
            </header>

            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Edit Admin Details</h2>
                        <a href="admins.php" class="admin-btn admin-btn-light admin-btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Admins
                        </a>
                    </div>
                    <div class="admin-card-body">
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

                        <form id="edit-admin-form" method="post" action="edit-admin.php?id=<?php echo $edit_admin_id; ?>">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div class="admin-form-group">
                                    <label for="name">Full Name*</label>
                                    <input type="text" id="name" name="name" class="admin-form-control" value="<?php echo htmlspecialchars($admin_data['name']); ?>" required>
                                </div>
                                <div class="admin-form-group">
                                    <label for="email">Email*</label>
                                    <input type="email" id="email" name="email" class="admin-form-control" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                                </div>
                                <div class="admin-form-group">
                                    <label for="phone">Phone Number*</label>
                                    <input type="tel" id="phone" name="phone" class="admin-form-control" value="<?php echo htmlspecialchars($admin_data['phone']); ?>" required>
                                </div>
                                <div class="admin-form-group">
                                    <label for="role">Role*</label>
                                    <select id="role" name="role" class="admin-form-select" required>
                                        <option value="super_admin" <?php echo $admin_data['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                        <option value="admin" <?php echo $admin_data['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>

                            <div class="admin-form-group" style="margin-top: 20px;">
                                <div class="admin-form-check">
                                    <input type="checkbox" id="change_password" name="change_password" value="1" class="admin-form-check-input">
                                    <label for="change_password" class="admin-form-check-label">Change Password</label>
                                </div>
                            </div>

                            <div id="password_section" style="display: none;">
                                <div class="admin-form-group">
                                    <label for="new_password">New Password*</label>
                                    <input type="text" id="new_password" name="new_password" class="admin-form-control">
                                    <small style="color: #6c757d; margin-top: 5px; display: block;">Password must be at least 6 characters.</small>
                                </div>
                            </div>

                            <div class="admin-form-group" style="margin-top: 30px;">
                                <button type="submit" class="admin-btn admin-btn-primary">Update Admin</button>
                                <a href="admins.php" class="admin-btn admin-btn-light" style="margin-left: 10px;">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="admin-card" style="margin-top: 20px;">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Admin Information</h2>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div>
                                <p><strong>Admin ID:</strong> #<?php echo $admin_data['id']; ?></p>
                                <p><strong>Created:</strong> <?php echo date('F d, Y', strtotime($admin_data['created_at'])); ?></p>
                            </div>
                            <div>
                                <p><strong>Last Updated:</strong>
                                    <?php
                                    echo isset($admin_data['updated_at']) && $admin_data['updated_at'] !== null
                                        ? date('F d, Y', strtotime($admin_data['updated_at']))
                                        : 'Never';
                                    ?>
                                </p>
                                <p>
                                    <strong>Status:</strong>
                                    <span class="admin-badge success">Active</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

<script src="../admin-scripts/edit-admin.js"></script>

</html>