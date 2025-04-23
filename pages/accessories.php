<?php
session_start();
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
                <button class="category-tab" data-category="wedding">Wedding</button>
                <button class="category-tab" data-category="birthday">Birthday</button>
                <button class="category-tab" data-category="corporate">Corporate</button>
                <button class="category-tab" data-category="decoration">Decoration</button>
                <button class="category-tab" data-category="furniture">Furniture</button>
            </div>

            <div class="accessories-grid">
                <!-- Wedding Accessories -->
                <div class="accessory-card" data-category="wedding furniture">
                    <div class="accessory-image">
                        <img src="images/wedding-chairs.jpg" alt="Wedding Chairs">
                    </div>
                    <div class="accessory-content">
                        <h3>Elegant Wedding Chairs</h3>
                        <p>Beautiful white chairs perfect for wedding ceremonies and receptions.</p>
                        <div class="accessory-meta">
                            <span class="accessory-price">$5 per chair</span>
                            <a href="reservation.php" class="btn btn-secondary">Reserve</a>
                        </div>
                    </div>
                </div>

                <div class="accessory-card" data-category="wedding decoration">
                    <div class="accessory-image">
                        <img src="images/wedding-arch.jpg" alt="Wedding Arch">
                    </div>
                    <div class="accessory-content">
                        <h3>Floral Wedding Arch</h3>
                        <p>Stunning floral arch for wedding ceremonies and photo opportunities.</p>
                        <div class="accessory-meta">
                            <span class="accessory-price">$300 per event</span>
                            <a href="reservation.php" class="btn btn-secondary">Reserve</a>
                        </div>
                    </div>
                </div>

                <!-- Birthday Accessories -->
                <div class="accessory-card" data-category="birthday decoration">
                    <div class="accessory-image">
                        <img src="images/birthday-balloons.jpg" alt="Birthday Balloons">
                    </div>
                    <div class="accessory-content">
                        <h3>Premium Balloon Arrangements</h3>
                        <p>Colorful balloon arrangements to brighten up any birthday celebration.</p>
                        <div class="accessory-meta">
                            <span class="accessory-price">$100 per set</span>
                            <a href="reservation.php" class="btn btn-secondary">Reserve</a>
                        </div>
                    </div>
                </div>

                <div class="accessory-card" data-category="birthday">
                    <div class="accessory-image">
                        <img src="images/birthday-cake-stand.jpg" alt="Cake Stand">
                    </div>
                    <div class="accessory-content">
                        <h3>Luxury Cake Stand</h3>
                        <p>Elegant cake stand to showcase your birthday cake in style.</p>
                        <div class="accessory-meta">
                            <span class="accessory-price">$50 per event</span>
                            <a href="reservation.php" class="btn btn-secondary">Reserve</a>
                        </div>
                    </div>
                </div>

                <!-- Corporate Accessories -->
                <div class="accessory-card" data-category="corporate furniture">
                    <div class="accessory-image">
                        <img src="images/corporate-tables.jpg" alt="Conference Tables">
                    </div>
                    <div class="accessory-content">
                        <h3>Conference Tables</h3>
                        <p>Professional tables for corporate events, meetings, and conferences.</p>
                        <div class="accessory-meta">
                            <span class="accessory-price">$30 per table</span>
                            <a href="reservation.php" class="btn btn-secondary">Reserve</a>
                        </div>
                    </div>
                </div>

                <div class="accessory-card" data-category="corporate">
                    <div class="accessory-image">
                        <img src="images/corporate-podium.jpg" alt="Podium">
                    </div>
                    <div class="accessory-content">
                        <h3>Professional Podium</h3>
                        <p>Sleek podium for speeches and presentations at corporate events.</p>
                        <div class="accessory-meta">
                            <span class="accessory-price">$75 per event</span>
                            <a href="reservation.php" class="btn btn-secondary">Reserve</a>
                        </div>
                    </div>
                </div>

                <!-- Decoration Items -->
                <div class="accessory-card" data-category="decoration">
                    <div class="accessory-image">
                        <img src="images/string-lights.jpg" alt="String Lights">
                    </div>
                    <div class="accessory-content">
                        <h3>Fairy String Lights</h3>
                        <p>Beautiful string lights to create a magical atmosphere at any event.</p>
                        <div class="accessory-meta">
                            <span class="accessory-price">$50 per set</span>
                            <a href="reservation.php" class="btn btn-secondary">Reserve</a>
                        </div>
                    </div>
                </div>

                <div class="accessory-card" data-category="decoration">
                    <div class="accessory-image">
                        <img src="images/flower-centerpieces.jpg" alt="Flower Centerpieces">
                    </div>
                    <div class="accessory-content">
                        <h3>Flower Centerpieces</h3>
                        <p>Elegant floral centerpieces to adorn tables at any special event.</p>
                        <div class="accessory-meta">
                            <span class="accessory-price">$40 per piece</span>
                            <a href="reservation.php" class="btn btn-secondary">Reserve</a>
                        </div>
                    </div>
                </div>

                <!-- Furniture Items -->
                <div class="accessory-card" data-category="furniture">
                    <div class="accessory-image">
                        <img src="images/cocktail-tables.jpg" alt="Cocktail Tables">
                    </div>
                    <div class="accessory-content">
                        <h3>Cocktail Tables</h3>
                        <p>Stylish cocktail tables perfect for receptions and networking events.</p>
                        <div class="accessory-meta">
                            <span class="accessory-price">$25 per table</span>
                            <a href="reservation.php" class="btn btn-secondary">Reserve</a>
                        </div>
                    </div>
                </div>

                <div class="accessory-card" data-category="furniture">
                    <div class="accessory-image">
                        <img src="images/lounge-furniture.jpg" alt="Lounge Furniture">
                    </div>
                    <div class="accessory-content">
                        <h3>Lounge Furniture Set</h3>
                        <p>Comfortable and stylish lounge furniture for a relaxed event atmosphere.</p>
                        <div class="accessory-meta">
                            <span class="accessory-price">$200 per set</span>
                            <a href="reservation.php" class="btn btn-secondary">Reserve</a>
                        </div>
                    </div>
                </div>
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
                        <p><strong>Phone:</strong> (123) 456-7890</p>
                        <p><strong>Email:</strong> info@najevents.com</p>
                    </div>
                    <div class="footer-hours">
                        <h3>Business Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                        <p>Saturday: 10:00 AM - 4:00 PM</p>
                        <p>Sunday: Closed</p>
                    </div>
                    <div class="footer-social">
                        <h3>Follow Us</h3>
                        <a href="https://instagram.com/najevents" class="social-link instagram">Instagram</a>
                        <a href="https://maps.google.com" class="location-link">Office Location</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Naj Events. All rights reserved.</p>
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