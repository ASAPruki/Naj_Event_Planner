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
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Process form submission for adding new accessory
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
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
    if (empty($name) || empty($category) || $quantity < 0) {
        $error_message = "Please fill in all required fields with valid values.";
    } else {
        // Insert new accessory
        $stmt = $conn->prepare("INSERT INTO accessories_inventory (name, category, description, material, color, dimensions, weight_capacity, quantity, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssiid", $name, $category, $description, $material, $color, $dimensions, $weight_capacity, $quantity, $is_available);

        if ($stmt->execute()) {
            $success_message = "Accessory added successfully!";

            // Log the activity
            $action_details = "Admin added new accessory: $name";
            $ip_address = $_SERVER['REMOTE_ADDR'];

            $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'add_accessory', ?, ?)");
            $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
            $log_stmt->execute();
            $log_stmt->close();
        } else {
            $error_message = "Error adding accessory: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Process form submission for deleting accessory
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['accessory_id']) || empty($_POST['accessory_id'])) {
        die("Error: Accessory ID is missing!");
    }

    $accessory_id = (int)$_POST['accessory_id'];

    // Get accessory name for admin logging
    $name_stmt = $conn->prepare("SELECT name FROM accessories_inventory WHERE id = ?");
    $name_stmt->bind_param("i", $accessory_id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    $accessory_data = $name_result->fetch_assoc();
    $name_stmt->close();

    $accessory_name = $accessory_data ? $accessory_data['name'] : "Unknown";

    // Delete accessory
    $stmt = $conn->prepare("DELETE FROM accessories_inventory WHERE id = ?");
    $stmt->bind_param("i", $accessory_id);

    if ($stmt->execute()) {
        $success_message = "Accessory deleted successfully!";

        // Log the activity
        $action_details = "Admin deleted accessory: $accessory_name";
        $ip_address = $_SERVER['REMOTE_ADDR'];

        $log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'delete_accessory', ?, ?)");
        $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
        $log_stmt->execute();
        $log_stmt->close();
    } else {
        $error_message = "Error deleting accessory: " . $stmt->error;
    }

    $stmt->close();
}

// Build query based on filters
$query = "SELECT * FROM accessories_inventory WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM accessories_inventory WHERE 1=1";

// Add filter conditions
$conditions = [];
$params = [];
$types = "";

if (!empty($filter_category)) {
    $conditions[] = "category = ?";
    $params[] = $filter_category;
    $types .= "s";
}

if (!empty($search)) {
    $search_term = "%$search%";
    $conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

// Add conditions to queries
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
    $count_query .= " AND " . implode(" AND ", $conditions);
}

// Add order by
$query .= " ORDER BY name ASC";

// Prepare and execute count query
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);
$count_stmt->close();

// Add limit to main query
$query .= " LIMIT ?, ?";
$limit_params = [$offset, $per_page];
$limit_types = "ii";

// Prepare and execute main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    // Combine parameters and types
    $all_params = array_merge($params, $limit_params);
    $all_types = $types . $limit_types;
    $stmt->bind_param($all_types, ...$all_params);
} else {
    // Only limit parameters
    $stmt->bind_param($limit_types, ...$limit_params);
}

$stmt->execute();
$result = $stmt->get_result();
$accessories = [];
while ($row = $result->fetch_assoc()) {
    $accessories[] = $row;
}
$stmt->close();

// Get categories for filter dropdown
$categories_query = "SELECT DISTINCT category FROM accessories_inventory ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accessories Management - Naj Events Admin</title>
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
                <a href="../../APIs/logout.php" class="admin-logout">
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
                <h1 class="admin-header-title">Accessories Management</h1>
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

                <!-- Add New Accessory Form -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Add New Accessory</h2>
                        <button id="toggleAddForm" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-plus"></i> Show Form
                        </button>
                    </div>
                    <div class="admin-card-body" id="addAccessoryForm" style="display: none;">
                        <form method="post" action="accessories.php">
                            <input type="hidden" name="action" value="add">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div class="admin-form-group">
                                    <label for="name">Accessory Name*</label>
                                    <input type="text" id="name" name="name" class="admin-form-control" required>
                                </div>
                                <div class="admin-form-group">
                                    <label for="category">Category*</label>
                                    <select id="category" name="category" class="admin-form-select" required>
                                        <option value="">Select Category</option>
                                        <option value="chairs">Chairs</option>
                                        <option value="tables">Tables</option>
                                        <option value="lighting">Lighting</option>
                                        <option value="decoration">Decoration</option>
                                        <option value="furniture">Furniture</option>
                                        <option value="sound">Sound Equipment</option>
                                        <option value="catering">Catering Equipment</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="admin-form-group">
                                    <label for="material">Material</label>
                                    <input type="text" id="material" name="material" class="admin-form-control">
                                </div>
                                <div class="admin-form-group">
                                    <label for="color">Color</label>
                                    <input type="text" id="color" name="color" class="admin-form-control">
                                </div>
                                <div class="admin-form-group">
                                    <label for="dimensions">Dimensions</label>
                                    <input type="text" id="dimensions" name="dimensions" class="admin-form-control" placeholder="e.g., 24\" x 36\"">
                                </div>
                                <div class="admin-form-group">
                                    <label for="weight_capacity">Weight Capacity (kgs)</label>
                                    <input type="number" id="weight_capacity" name="weight_capacity" class="admin-form-control">
                                </div>
                                <div class="admin-form-group">
                                    <label for="quantity">Quantity*</label>
                                    <input type="number" id="quantity" name="quantity" class="admin-form-control" min="0" required>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="admin-form-textarea" rows="3"></textarea>
                            </div>
                            <div class="admin-form-check" style="margin-top: 15px;">
                                <input type="checkbox" id="is_available" name="is_available" class="admin-form-check-input" checked>
                                <label for="is_available" class="admin-form-check-label">Available for Rental</label>
                            </div>
                            <div class="admin-form-group" style="margin-top: 20px;">
                                <button type="submit" class="admin-btn admin-btn-primary">Add Accessory</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Filter Accessories</h2>
                    </div>
                    <div class="admin-card-body">
                        <form method="get" action="accessories.php" class="admin-filters">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <label for="category">Category</label>
                                    <select id="category" name="category" class="admin-form-select">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category; ?>" <?php echo $filter_category === $category ? 'selected' : ''; ?>><?php echo ucfirst($category); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="flex: 2; min-width: 200px;">
                                    <label for="search">Search</label>
                                    <input type="text" id="search" name="search" class="admin-form-control" placeholder="Search by name or description" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div style="display: flex; align-items: flex-end;">
                                    <button type="submit" class="admin-btn admin-btn-primary">Apply Filters</button>
                                    <a href="accessories.php" class="admin-btn admin-btn-light" style="margin-left: 10px;">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Accessories Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Accessories Inventory</h2>
                        <div>
                            <span class="admin-badge primary"><?php echo $total_records; ?> Total Items</span>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($accessories) > 0): ?>
                                        <?php foreach ($accessories as $accessory): ?>
                                            <tr>
                                                <td>#<?php echo $accessory['id']; ?></td>
                                                <td>
                                                    <?php echo $accessory['name']; ?><br>
                                                    <small><?php echo $accessory['description']; ?></small>
                                                </td>
                                                <td><?php echo ucfirst($accessory['category']); ?></td>
                                                <td><?php echo $accessory['quantity']; ?></td>
                                                <td>
                                                    <?php if ($accessory['is_available']): ?>
                                                        <span class="admin-badge success">Available</span>
                                                    <?php else: ?>
                                                        <span class="admin-badge danger">Unavailable</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="admin-table-actions">
                                                        <a href="edit-accessory.php?id=<?php echo $accessory['id']; ?>" class="admin-btn admin-btn-primary admin-btn-sm">Edit</a>
                                                        <button type="button" class="admin-btn admin-btn-danger admin-btn-sm delete-accessory" data-id="<?php echo $accessory['id']; ?>" data-name="<?php echo $accessory['name']; ?>">Delete</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center;">No accessories found</td>
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
                                        <a href="?page=<?php echo $page - 1; ?>&category=<?php echo $filter_category; ?>&search=<?php echo urlencode($search); ?>" class="admin-pagination-link">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $i; ?>&category=<?php echo $filter_category; ?>&search=<?php echo urlencode($search); ?>" class="admin-pagination-link <?php echo $i === $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </div>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $page + 1; ?>&category=<?php echo $filter_category; ?>&search=<?php echo urlencode($search); ?>" class="admin-pagination-link">
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
            <p>Are you sure you want to delete the accessory: <span id="deleteAccessoryName"></span>?</p>
            <form method="post" action="accessories.php" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="accessory_id" id="deleteAccessoryId">
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" id="cancelDelete" class="admin-btn admin-btn-light">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-danger">Delete</button>
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

        // Toggle add accessory form
        const toggleAddForm = document.getElementById('toggleAddForm');
        const addAccessoryForm = document.getElementById('addAccessoryForm');

        if (toggleAddForm && addAccessoryForm) {
            toggleAddForm.addEventListener('click', function() {
                if (addAccessoryForm.style.display === 'none') {
                    addAccessoryForm.style.display = 'block';
                    toggleAddForm.innerHTML = '<i class="fas fa-minus"></i> Hide Form';
                } else {
                    addAccessoryForm.style.display = 'none';
                    toggleAddForm.innerHTML = '<i class="fas fa-plus"></i> Show Form';
                }
            });
        }

        // Delete confirmation modal
        const deleteButtons = document.querySelectorAll('.delete-accessory');
        const deleteModal = document.getElementById('deleteModal');
        const deleteAccessoryName = document.getElementById('deleteAccessoryName');
        const deleteAccessoryId = document.getElementById('deleteAccessoryId');
        const cancelDelete = document.getElementById('cancelDelete');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');

                deleteAccessoryId.value = id;
                deleteAccessoryName.textContent = name;
                deleteModal.style.display = 'block';
            });
        });

        if (cancelDelete) {
            cancelDelete.addEventListener('click', function() {
                deleteModal.style.display = 'none';
            });
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
        });
    </script>
</body>

</html>