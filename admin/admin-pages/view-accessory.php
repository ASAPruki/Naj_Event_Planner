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

// Check if there are images for this accessory
// Assuming you have an accessory_images table with accessory_id and image_url columns
$images = [];
$images_stmt = $conn->prepare("SELECT * FROM accessory_images WHERE accessory_id = ? ORDER BY id ASC");
if ($images_stmt) {
    $images_stmt->bind_param("i", $accessory_id);
    $images_stmt->execute();
    $images_result = $images_stmt->get_result();

    while ($image = $images_result->fetch_assoc()) {
        $images[] = $image;
    }

    $images_stmt->close();
}

// Log admin activity
$action_details = "Viewed accessory details: " . $accessory['name'] . " (ID: " . $accessory_id . ")";
$ip_address = $_SERVER['REMOTE_ADDR'];

$log_stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'view_accessory', ?, ?)");
$log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
$log_stmt->execute();
$log_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Accessory - Naj Events Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../styles/styles.css">
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
    <link rel="stylesheet" href="../admin-styles/view-accessory.css">
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
                <h1 class="admin-header-title">Accessory Details</h1>
            </header>

            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-header-left">
                            <a href="accessories.php" class="admin-btn admin-btn-light">
                                <i class="fas fa-arrow-left"></i> Back to Accessories
                            </a>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="accessory-header">
                            <h2 class="accessory-title"><?php echo $accessory['name']; ?></h2>
                            <?php if ($accessory['is_available']): ?>
                                <span class="accessory-status available">Available</span>
                            <?php else: ?>
                                <span class="accessory-status unavailable">Unavailable</span>
                            <?php endif; ?>
                        </div>

                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h3 class="admin-card-title">Accessory Information</h3>
                                <div>
                                    <a href="edit-accessory.php?id=<?php echo $accessory_id; ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            </div>
                            <div class="admin-card-body">
                                <div class="accessory-detail-grid">
                                    <div>
                                        <div class="accessory-detail-label">ID</div>
                                        <div class="accessory-detail-value">#<?php echo $accessory['id']; ?></div>
                                    </div>
                                    <div>
                                        <div class="accessory-detail-label">Category</div>
                                        <div class="accessory-detail-value"><?php echo ucfirst($accessory['category']); ?></div>
                                    </div>
                                    <div>
                                        <div class="accessory-detail-label">Quantity</div>
                                        <div class="accessory-detail-value"><?php echo $accessory['quantity']; ?> units</div>
                                    </div>
                                    <?php if (!empty($accessory['material'])): ?>
                                        <div>
                                            <div class="accessory-detail-label">Material</div>
                                            <div class="accessory-detail-value"><?php echo $accessory['material']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($accessory['color'])): ?>
                                        <div>
                                            <div class="accessory-detail-label">Color</div>
                                            <div class="accessory-detail-value"><?php echo $accessory['color']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($accessory['dimensions'])): ?>
                                        <div>
                                            <div class="accessory-detail-label">Dimensions</div>
                                            <div class="accessory-detail-value"><?php echo $accessory['dimensions']; ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($accessory['weight_capacity'])): ?>
                                        <div>
                                            <div class="accessory-detail-label">Weight Capacity</div>
                                            <div class="accessory-detail-value"><?php echo $accessory['weight_capacity']; ?> lbs</div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($accessory['description'])): ?>
                                    <div style="margin-top: 20px;">
                                        <div class="accessory-detail-label">Description</div>
                                        <div class="accessory-detail-value" style="white-space: pre-line;"><?php echo $accessory['description']; ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="admin-card" style="margin-top: 20px;">
                            <div class="admin-card-header">
                                <h3 class="admin-card-title">Accessory Images</h3>
                                <div>
                                    <button id="showUploadForm" class="admin-btn admin-btn-primary admin-btn-sm">
                                        <i class="fas fa-plus"></i> Add Images
                                    </button>
                                </div>
                            </div>
                            <div class="admin-card-body">
                                <?php if (count($images) > 0): ?>
                                    <div class="accessory-image-gallery">
                                        <?php foreach ($images as $image): ?>
                                            <div class="accessory-image-item">
                                                <img src="../../images/accessories/<?php echo htmlspecialchars($image['image_url']); ?>" alt="<?php echo htmlspecialchars($accessory['name']); ?>">
                                                <div class="accessory-image-overlay">
                                                    <form method="post" action="delete-accessory-image.php" style="display: inline;">
                                                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                                        <input type="hidden" name="accessory_id" value="<?php echo $accessory_id; ?>">
                                                        <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Are you sure you want to delete this image?')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="no-images-message">
                                        <i class="fas fa-image" style="font-size: 3rem; margin-bottom: 15px; color: #adb5bd;"></i>
                                        <p>No images available for this accessory.</p>
                                        <p>Click "Add Images" to upload photos.</p>
                                    </div>
                                <?php endif; ?>

                                <!-- Image Upload Form (Hidden by default) -->
                                <div id="imageUploadForm" class="image-upload-section" style="display: none;">
                                    <h4>Upload Images</h4>
                                    <form action="upload-accessory-image.php" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="accessory_id" value="<?php echo $accessory_id; ?>">
                                        <div class="admin-form-group">
                                            <label for="accessory_images">Select Images (Multiple files allowed)</label>
                                            <input type="file" id="accessory_images" name="accessory_images[]" class="admin-form-control" multiple accept="image/*" required>
                                            <small class="form-text text-muted">Supported formats: JPG, PNG, GIF. Max size: 5MB per image.</small>
                                        </div>
                                        <div class="admin-form-group">
                                            <button type="submit" class="admin-btn admin-btn-primary">Upload Images</button>
                                            <button type="button" id="cancelUpload" class="admin-btn admin-btn-light">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../admin-scripts/view-accessory.js"></script>
</body>

</html>