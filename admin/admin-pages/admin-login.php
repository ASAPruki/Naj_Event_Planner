<?php
session_start();

// Check if admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: admin-dashboard.php");
    exit();
}

// Initialize variables
$error_message = "";

// Process login form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require "../../APIs/connect.php";

    // Sanitize input data
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // Verify password (plain text comparison)
        if ($password === $admin['password']) {
            // Password is correct, start a new session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];

            // Log the login activity
            $action_details = "Admin logged in";
            $ip_address = $_SERVER['REMOTE_ADDR'];

            $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'login', ?, ?)");
            $log_stmt->bind_param("iss", $admin['id'], $action_details, $ip_address);
            $log_stmt->execute();
            $log_stmt->close();

            // Redirect to admin dashboard
            header("Location: admin-dashboard.php");
            exit();
        } else {
            // Password is incorrect
            $error_message = "Invalid email or password";
        }
    } else {
        // User not found
        $error_message = "Invalid email or password";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Naj Events</title>
    <link rel="stylesheet" href="../../styles/styles.css">
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
</head>

<body>
    <div class="admin-login-container">
        <div class="admin-login-form">
            <div class="admin-login-header">
                <h1>Naj Events</h1>
                <h2>Admin Login</h2>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="security-warning" style="background-color: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem;">
                <strong>Note:</strong> This system uses plain text passwords for demonstration purposes. This is not secure for production environments.
            </div>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>

            <div class="admin-login-footer">
                <a href="../../pages/index.html">Return to Website</a>
            </div>
        </div>
    </div>
</body>

</html>