<?php
session_start();
require '../../APIs/connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

// Fetch reviews
$sql = "
    SELECT r.id, u.name AS user_name, r.rating, r.review_text, r.created_at
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
";
$reviews_result = $conn->query($sql);

// Fetch users for filter dropdown
$users_query = "SELECT id, name FROM users ORDER BY name";
$users_result = $conn->query($users_query);

// Filter variables
$filter_user = isset($_GET['user']) ? $_GET['user'] : '';
$filter_rating = isset($_GET['rating']) ? $_GET['rating'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Fetch reviews with filters
$sql = "
    SELECT r.id, u.name AS user_name, r.rating, r.review_text, r.created_at
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE 1=1
";

$params = []; // Initialize params array

if (!empty($filter_user)) {
    $sql .= " AND r.user_id = ?";
    $params[] = (int)$filter_user; // Ensure this is an integer
}

if (!empty($filter_rating)) {
    $sql .= " AND r.rating = ?";
    $params[] = (int)$filter_rating; // Ensure this is an integer
}

if (!empty($filter_date)) {
    // Ensure the date is formatted correctly
    $sql .= " AND DATE(r.created_at) = ?";
    $params[] = $filter_date; // This should be in 'YYYY-MM-DD' format
}

$sql .= " ORDER BY r.created_at DESC";
$reviews_result = $conn->prepare($sql);
if ($reviews_result === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error)); // Debugging line
}

if (!empty($params)) {
    // Determine the types for binding
    $types = '';
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i'; // Integer
        } elseif (is_string($param)) {
            $types .= 's'; // String
        }
    }

    // Debugging output
    error_log("Types: " . $types);
    error_log("Params: " . json_encode($params));

    // Ensure the number of types matches the number of params
    if (strlen($types) !== count($params)) {
        die('Mismatch between types and params count.'); // Debugging line
    }

    $reviews_result->bind_param($types, ...$params);
}

$reviews_result->execute();
$reviews_result = $reviews_result->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Reviews - Naj Events Admin</title>
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
    <link rel="stylesheet" href="../../styles/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="admin-body">
    <div class="admin-container">
        <!-- Sidebar -->
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
                        <a href="user-reviews.php" class="admin-nav-link active">
                            <i class="fas fa-star admin-nav-icon"></i>
                            User Reviews
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="admins.php" class="admin-nav-link">
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
                    <div class="admin-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
                    <div class="admin-user-details">
                        <div class="admin-user-name"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div class="admin-user-role"><?php echo htmlspecialchars(ucfirst($admin_role)); ?></div>
                    </div>
                </div>
                <a href="admin-logout.php" class="admin-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main" id="adminMain">
            <header class="admin-header">
                <button class="admin-toggle-sidebar" id="toggleSidebar">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1 class="admin-header-title">User Reviews</h1>
            </header>

            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Filter Reviews</h2>
                    </div>
                    <div class="admin-card-body">
                        <form method="GET" action="" class="admin-filters">
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <label for="user">User </label>
                                    <select name="user" id="user" class="admin-form-select">
                                        <option value="">All Users</option>
                                        <?php while ($user = $users_result->fetch_assoc()): ?>
                                            <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div style="flex: 1; min-width: 200px;">
                                    <label for="rating">Rating</label>
                                    <select name="rating" id="rating" class="admin-form-select">
                                        <option value="">All Ratings</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $filter_rating == $i ? 'selected' : ''; ?>>
                                                <?php echo $i; ?>/5
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div style="flex: 1; min-width: 200px;">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" id="date" class="admin-form-control" value="<?php echo $filter_date; ?>">
                                </div>

                                <div style="display: flex; align-items: flex-end;">
                                    <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
                                    <a href="user-reviews.php" class="admin-btn admin-btn-light" style="margin-left: 10px;">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">All Reviews</h2>
                        <span class="admin-badge info">Total: <?php echo $reviews_result->num_rows; ?></span>
                    </div>
                    <div class="admin-card-body">
                        <?php if ($reviews_result->num_rows > 0): ?>
                            <div class="admin-table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Rating</th>
                                            <th>Review</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $reviews_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                                <td><?php echo intval($row['rating']); ?>/5</td>
                                                <td title="<?php echo htmlspecialchars($row['review_text']); ?>">
                                                    <?php echo mb_strimwidth(htmlspecialchars($row['review_text']), 0, 50, '…'); ?>
                                                </td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p style="text-align:center; padding:20px;">No reviews found.</p>
                        <?php endif; ?>
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