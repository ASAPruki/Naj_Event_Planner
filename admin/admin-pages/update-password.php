<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Get admin information
$admin_id = $_SESSION['admin_id'];

// Initialize variables
$success_message = "";
$error_message = "";

// Process form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require "../../APIs/connect.php";

    // Get form data
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All fields are required";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters";
    } else {
        // Get current admin data
        $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            // Verify current password (plain text comparison)
            if ($current_password === $admin['password']) {
                // Update password
                $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_password, $admin_id);

                if ($update_stmt->execute()) {
                    $success_message = "Password updated successfully";

                    // Log the activity
                    $action_details = "Admin updated their password";
                    $ip_address = $_SERVER['REMOTE_ADDR'];

                    $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'password_update', ?, ?)");
                    $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
                    $log_stmt->execute();
                    $log_stmt->close();
                } else {
                    $error_message = "Error updating password: " . $update_stmt->error;
                }

                $update_stmt->close();
            } else {
                $error_message = "Current password is incorrect";
            }
        } else {
            $error_message = "Admin not found";
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password - Naj Events Admin</title>
    <link rel="stylesheet" href="../../styles/styles.css">
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="admin-body">
    <div class="admin-container">
        <!-- Admin Sidebar (Include your sidebar code here) -->

        <!-- Admin Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <button class="admin-toggle-sidebar">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1 class="admin-header-title">Update Password</h1>
            </header>

            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Change Your Password</h2>
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

                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="admin-form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="admin-form-control" required>
                            </div>
                            <div class="admin-form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="admin-form-control" required>
                            </div>
                            <div class="admin-form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="admin-form-control" required>
                            </div>
                            <div class="admin-form-group">
                                <button type="submit" class="admin-btn admin-btn-primary">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>