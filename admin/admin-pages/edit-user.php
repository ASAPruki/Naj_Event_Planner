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

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = $_GET['id'];

require "../../APIs/connect.php";

// Initialize variables
$success_message = "";
$error_message = "";
$user = null;

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    header("Location: users.php");
    exit();
}

$stmt->close();

// Process form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = isset($_POST['phone']) ? htmlspecialchars(preg_replace('/\s+/', '', $_POST['phone'])) : '';
    $address = isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';

    // Validate input
    $errors = array();

    if (strlen($name) < 3) {
        $errors[] = "Name must be at least 3 characters";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (!preg_match("/^\d{2}\s?\d{3}\s?\d{3}$/", $phone)) {
        $error[] = "Please enter a valid phone number.";
    }

    // Check if email already exists for another user
    if ($email !== $user['email']) {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $errors[] = "Email already in use by another user";
        }

        $check_stmt->close();
    }

    // If no errors, update user
    if (empty($errors)) {
        // Prepare SQL statement based on whether a new password is provided
        if (!empty($new_password)) {
            $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?");
            $update_stmt->bind_param("sssssi", $name, $email, $phone, $address, $new_password, $user_id);
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $update_stmt->bind_param("ssssi", $name, $email, $phone, $address, $user_id);
        }

        if ($update_stmt->execute()) {
            $success_message = "User updated successfully";

            // Log the activity
            $action_details = "Admin updated user #$user_id";
            $ip_address = $_SERVER['REMOTE_ADDR'];

            $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'user_update', ?, ?)");
            $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
            $log_stmt->execute();
            $log_stmt->close();

            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        } else {
            $error_message = "Error updating user: " . $update_stmt->error;
        }

        $update_stmt->close();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Naj Events Admin</title>
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
                <h1 class="admin-header-title">Edit User</h1>
            </header>

            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Edit User: <?php echo $user['name']; ?></h2>
                        <a href="users.php" class="admin-btn admin-btn-light admin-btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Users
                        </a>
                    </div>
                    <div class="admin-card-body">
                        <?php if (!empty($success_message)): ?>
                            <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error_message)): ?>
                            <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <div class="security-warning" style="background-color: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem;">
                            <strong>Note:</strong> This system uses plain text passwords for demonstration purposes. This is not secure for production environments.
                        </div>

                        <form id="edit-user-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $user_id); ?>">
                            <div class="admin-form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" class="admin-form-control" value="<?php echo $user['name']; ?>" required>
                            </div>
                            <div class="admin-form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="admin-form-control" value="<?php echo $user['email']; ?>" required>
                            </div>
                            <div class="admin-form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="admin-form-control" value="<?php echo isset($user['phone']) ? $user['phone'] : ''; ?>" required>
                            </div>
                            <div class="admin-form-group">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" class="admin-form-textarea"><?php echo isset($user['address']) ? $user['address'] : ''; ?></input>
                            </div>
                            <div class="admin-form-group">
                                <label for="new_password">New Password (leave blank to keep current)</label>
                                <input type="text" id="new_password" name="new_password" class="admin-form-control">
                                <small class="form-text text-muted">Current password: <?php echo $user['password']; ?></small>
                            </div>
                            <div class="admin-form-group">
                                <button type="submit" class="admin-btn admin-btn-primary">Update User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../admin-scripts/edit-user.js"></script>
</body>

</html>