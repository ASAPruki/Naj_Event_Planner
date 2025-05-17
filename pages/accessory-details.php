<?php
session_start();
require "../APIs/connect.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>Invalid accessory ID.</p>";
    exit;
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM accessories_inventory WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Accessory not found.</p>";
    exit;
}

$accessory = $result->fetch_assoc();

// Fetch associated images
$imageSql = "SELECT image_url FROM accessory_images WHERE accessory_id = ?";
$imageStmt = $conn->prepare($imageSql);
$imageStmt->bind_param("i", $id);
$imageStmt->execute();
$imageResult = $imageStmt->get_result();

$images = [];
while ($row = $imageResult->fetch_assoc()) {
    $images[] = $row['image_url'];
}

$imageStmt->close();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($accessory['name']); ?> | Naj Events</title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="stylesheet" href="../styles/carousel.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <script src="../scripts/script.js"></script>
    <script src="../scripts/carousel.js"></script>
</head>

<body>
    <!-- Navigation Bar -->
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <h1>Naj Events</h1>
                </a>
            </div>
            <nav>
                <div class="menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="accessories.php" class="active">Accessories</a></li>
                    <li><a href="reservation.php">Book an Event</a></li>
                    <li><a href="index.php#about">About Us</a></li>
                    <?php if (isset($_SESSION['user_name'])): ?>
                        <li><a href="dashboard.php">My Profile</a></li>
                    <?php else: ?>
                        <li><a href="#" id="login-button">Login | Sign up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Accessory details display -->
    <section class="accessories-hero">
        <div class="accessories-hero-content">
            <h2><?php echo htmlspecialchars($accessory['name']); ?></h2>
            <p><?php echo htmlspecialchars($accessory['description']); ?></p>
        </div>
    </section>

    <!-- Back to Accessories button -->
    <div class="container" style="margin-top: 30px;">
        <a href="accessories.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Accessories
        </a>
    </div>

    <section class="accessories-categories" style="margin-bottom: 100px;">
        <div class="section-description">
            <h3>Accessory Details</h3>
            <p>Here are the full details for this item:</p>
        </div>

        <div class="accessory-card">
            <div class="accessory-image">
                <div class="carousel">
                    <?php foreach ($images as $index => $img): ?>
                        <div class="carousel-slide<?php echo $index === 0 ? ' active' : ''; ?>">
                            <img src="../admin/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($accessory['name']); ?>">
                        </div>
                    <?php endforeach; ?>
                    <button class="carousel-prev">&#10094;</button>
                    <button class="carousel-next">&#10095;</button>
                </div>
            </div>
            <div class="accessory-content">
                <h3><?php echo htmlspecialchars($accessory['name']); ?></h3>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($accessory['category']); ?></p>
                <p><strong>Material:</strong> <?php echo htmlspecialchars($accessory['material']); ?></p>
                <p><strong>Color:</strong> <?php echo htmlspecialchars($accessory['color']); ?></p>
                <p><strong>Dimensions:</strong>
                    <?php
                    if (!empty($accessory['dimensions'])) {
                        echo htmlspecialchars($accessory['dimensions']);
                    } else {
                        echo "Not set";
                    }
                    ?>
                </p>
                <p><strong>Weight Capacity:</strong>
                    <?php
                    if (!empty($accessory['weight_capacity'])) {
                        echo htmlspecialchars($accessory['weight_capacity']);
                    } else {
                        echo "Not set";
                    }
                    ?>
                </p>
                <p><strong>In Stock:</strong> <?php echo htmlspecialchars($accessory['quantity']); ?></p>
                <p><strong>Available:</strong>
                    <?php echo $accessory['is_available'] ? "Available" : "Not Available"; ?>
                </p>
                <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($accessory['description'])); ?></p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <h2>Naj Events</h2>
                    <p>Creating unforgettable moments</p>
                </div>
                <div class="footer-info">
                    <div class="footer-contact">
                        <h3>Contact Us</h3>
                        <p><strong>Phone:</strong> +961 71 615 159</p>
                        <p><strong>Email:</strong> najahshafei5@gmail.com</p>
                    </div>
                    <div class="footer-hours">
                        <h3>Business Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                        <p>Saturday: 10:00 AM - 4:00 PM</p>
                        <p>Sunday: Closed</p>
                    </div>
                    <div class="footer-social">
                        <h3>Follow Us</h3>
                        <div style="display: flex;">
                            <i class="fa-brands fa-instagram" style="font-size:24px; margin-right: 7px;"></i>
                            <a href="https://www.instagram.com/naj__wedding_planner" class="social-link instagram">Instagram</a>
                        </div>
                        <div style="display: flex;">
                            <img src="../admin/uploads/general/tiktok-white-icon.png" alt="TikTok"
                                style="width: 24px; height: 24px; margin-right: 4px; margin-left: -1px;">
                            <a href="https://www.tiktok.com/@naj_wedding_planner" class="social-link tiktok">Tiktok</a>
                        </div>
                        <div style="display: flex;">
                            <i class="fa-solid fa-location-dot" style="font-size:24px; margin-right: 7px; margin-left: 1px"></i>
                            <a href="https://maps.google.com" class="location-link">Office Location</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Naj Events. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Login/Signup Modal -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-tabs">
                <button class="tab-button active" data-tab="login">Login</button>
                <button class="tab-button" data-tab="signup">Sign Up</button>
            </div>
            <div id="login-tab" class="tab-content active">
                <form id="login-form" action="../APIs/login.php" method="post">
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <input type="email" id="login-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                    <div class="form-footer">
                        <a href="#" class="forgot-password">Forgot Password?</a>
                    </div>
                </form>
            </div>
            <div id="signup-tab" class="tab-content">
                <form id="signup-form" action="../APIs/signup.php" method="post">
                    <div class="form-group">
                        <label for="signup-name">Full Name</label>
                        <input type="text" id="signup-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="signup-email">Email</label>
                        <input type="email" id="signup-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="signup-password">Password</label>
                        <input type="password" id="signup-password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="signup-confirm">Confirm Password</label>
                        <input type="password" id="signup-confirm" name="confirm_password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Sign Up</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>