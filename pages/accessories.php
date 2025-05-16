<?php
session_start();

require "../APIs/connect.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Accessories - Naj Events</title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="stylesheet" href="../styles/accessories.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <script src="../scripts/script.js"></script>
    <script src="../scripts/accessories.js"></script>
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
                        <li><a href="#" id="login-button">Login / Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Accessories Hero Section -->
    <section class="accessories-hero">
        <div class="container">
            <div class="accessories-hero-content">
                <h2>Event Accessories</h2>
                <p>Discover our premium collection of event accessories to make your special occasion truly memorable.
                </p>
            </div>
        </div>
    </section>

    <!-- Accessories Categories -->
    <section class="accessories-categories">
        <div class="container">
            <h2 class="section-title">Browse by Category</h2>
            <div class="category-tabs">
                <button class="category-tab active" data-category="all">All</button>
                <button class="category-tab" data-category="chairs">Chairs</button>
                <button class="category-tab" data-category="tables">Tables</button>
                <button class="category-tab" data-category="lighting">Lighting</button>
                <button class="category-tab" data-category="decoration">Decoration</button>
                <button class="category-tab" data-category="sound">Sound Equipment</button>
                <button class="category-tab" data-category="catering">Catering Equipment</button>
                <button class="category-tab" data-category="other">Other</button>
            </div>

            <div class="accessories-grid">
                <?php
                $sql = "SELECT ai.*, (
                    SELECT image_url
                    FROM accessory_images 
                    WHERE accessory_id = ai.id 
                    ORDER BY id ASC 
                    LIMIT 1
                ) AS first_image 
                FROM accessories_inventory ai";

                $result = mysqli_query($conn, $sql);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $name = htmlspecialchars($row['name']);
                        $description = htmlspecialchars($row['description']);
                        $category = htmlspecialchars($row['category']);

                        // Get image filename or fallback
                        $image_filename = $row['first_image'] ?? 'no-image.png';

                        // Use correct relative path to the images folder
                        $image_path = "../admin/" . htmlspecialchars($image_filename);

                        echo "
                        <div class='accessory-card' data-category='$category'>
                            <a href='accessory-details.php?id={$row['id']}' class='accessory-card-link'>
                                <div class='accessory-image'>
                                    <img src='$image_path' alt='$name' style='object-fit: fit;'>
                                </div>
                                <div class='accessory-content'>
                                    <h3>$name</h3>
                                    <p>$description</p>
                                </div>
                            </a>
                        </div>";
                    }
                } else {
                    echo "<p>No accessories found.</p>";
                }
                ?>
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