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

// Check if accessory ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: accessories.php");
    exit();
}

$accessory_id = $_GET['id'];

require "../../APIs/connect.php";

// Initialize variables
$success_message = "";
$error_message = "";
$accessory = null;

// Get accessory data
$stmt = $conn->prepare("SELECT * FROM accessories_inventory WHERE id = ?");
$stmt->bind_param("i", $accessory_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $accessory = $result->fetch_assoc();
} else {
    header("Location: accessories.php");
    exit();
}

$stmt->close();

// Process form if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = htmlspecialchars($_POST['name']);
    $category = htmlspecialchars($_POST['category']);
    $description = htmlspecialchars($_POST['description']);
    $material = htmlspecialchars($_POST['material']);
    $color = htmlspecialchars($_POST['color']);
    $dimensions = htmlspecialchars($_POST['dimensions']);
    $weight_capacity = !empty($_POST['weight_capacity']) ? (int)$_POST['weight_capacity'] : null;
    $quantity = (int)$_POST['quantity'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    // Validate input
    $errors = array();

    if (empty($name)) {
        $errors[] = "Accessory name is required";
    }

    if (empty($category)) {
        $errors[] = "Category is required";
    }

    if ($quantity < 0) {
        $errors[] = "Quantity cannot be negative";
    }

    // If no errors, update accessory
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE accessories_inventory SET name = ?, category = ?, description = ?, material = ?, color = ?, dimensions = ?, weight_capacity = ?, quantity = ?, is_available = ? WHERE id = ?");
        $stmt->bind_param("ssssssiiii", $name, $category, $description, $material, $color, $dimensions, $weight_capacity, $quantity, $is_available, $accessory_id);

        if ($stmt->execute()) {
            $success_message = "Accessory updated successfully";

            // Log the activity
            $action_details = "Admin updated accessory: $name (ID: $accessory_id)";
            $ip_address = $_SERVER['REMOTE_ADDR'];

            $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'update_accessory', ?, ?)");
            $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
            $log_stmt->execute();
            $log_stmt->close();

            // Close the update statement before creating a new one
            $stmt->close();

            // Refresh accessory data
            $stmt = $conn->prepare("SELECT * FROM accessories_inventory WHERE id = ?");
            $stmt->bind_param("i", $accessory_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $accessory = $result->fetch_assoc();
            $stmt->close();
        } else {
            $error_message = "Error updating accessory: " . $stmt->error;
            $stmt->close(); // Close the statement in the error case
        }
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
    <title>Edit Accessory - Naj Events Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../styles/styles.css">
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
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
                        <a href="accessories.php" class="admin-nav-link active">
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
                <h1 class="admin-header-title">Edit Accessory</h1>
            </header>

            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Edit Accessory: <?php echo $accessory['name']; ?></h2>
                        <a href="accessories.php" class="admin-btn admin-btn-light admin-btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Accessories
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

                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $accessory_id); ?>">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div class="admin-form-group">
                                    <label for="name">Accessory Name*</label>
                                    <input type="text" id="name" name="name" class="admin-form-control" value="<?php echo $accessory['name']; ?>" required>
                                </div>
                                <div class="admin-form-group">
                                    <label for="category">Category*</label>
                                    <select id="category" name="category" class="admin-form-select" required>
                                        <option value="">Select Category</option>
                                        <option value="chairs" <?php echo $accessory['category'] === 'chairs' ? 'selected' : ''; ?>>Chairs</option>
                                        <option value="tables" <?php echo $accessory['category'] === 'tables' ? 'selected' : ''; ?>>Tables</option>
                                        <option value="lighting" <?php echo $accessory['category'] === 'lighting' ? 'selected' : ''; ?>>Lighting</option>
                                        <option value="decoration" <?php echo $accessory['category'] === 'decoration' ? 'selected' : ''; ?>>Decoration</option>
                                        <option value="furniture" <?php echo $accessory['category'] === 'furniture' ? 'selected' : ''; ?>>Furniture</option>
                                        <option value="sound" <?php echo $accessory['category'] === 'sound' ? 'selected' : ''; ?>>Sound Equipment</option>
                                        <option value="catering" <?php echo $accessory['category'] === 'catering' ? 'selected' : ''; ?>>Catering Equipment</option>
                                        <option value="other" <?php echo $accessory['category'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="admin-form-group">
                                    <label for="material">Material</label>
                                    <input type="text" id="material" name="material" class="admin-form-control" value="<?php echo $accessory['material']; ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label for="color">Color</label>
                                    <input type="text" id="color" name="color" class="admin-form-control" value="<?php echo $accessory['color']; ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label for="dimensions">Dimensions</label>
                                    <input type="text" id="dimensions" name="dimensions" class="admin-form-control" placeholder="e.g., 24\" x 36\"" value="<?php echo $accessory['dimensions']; ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label for="weight_capacity">Weight Capacity (kgs)</label>
                                    <input type="number" id="weight_capacity" name="weight_capacity" class="admin-form-control" value="<?php echo $accessory['weight_capacity']; ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label for="quantity">Quantity*</label>
                                    <input type="number" id="quantity" name="quantity" class="admin-form-control" min="0" value="<?php echo $accessory['quantity']; ?>" required>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="admin-form-textarea" rows="3"><?php echo $accessory['description']; ?></textarea>
                            </div>
                            <div class="admin-form-check" style="margin-top: 15px;">
                                <input type="checkbox" id="is_available" name="is_available" class="admin-form-check-input" <?php echo $accessory['is_available'] ? 'checked' : ''; ?>>
                                <label for="is_available" class="admin-form-check-label">Available for Rental</label>
                            </div>
                            <div class="admin-form-group" style="margin-top: 20px;">
                                <button type="submit" class="admin-btn admin-btn-primary">Update Accessory</button>
                            </div>
                        </form>
                    </div>
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