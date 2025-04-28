<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naj Events - Professional Event Planning</title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <script src="../scripts/script.js"></script>

</head>

<body>
    <!-- Navigation Bar -->
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.html">
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
                    <li><a href="index.html" class="active">Home</a></li>
                    <li><a href="accessories.php">Accessories</a></li>
                    <li><a href="reservation.php">Book an Event</a></li>
                    <li><a href="#about">About Us</a></li>
                    <?php if (isset($_SESSION['user_name'])): ?>
                        <li><a href="dashboard.php">My Profile</a></li>
                    <?php else: ?>
                        <li><a href="#" id="login-button">Login / Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Creating Unforgettable Moments</h2>
                <p>Professional event planning services for weddings, birthdays, proposals, and more.</p>
                <a href="reservation.php" class="btn btn-primary">Plan Your Event</a>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services">
        <div class="container">
            <h2 class="section-title">Our Services</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon wedding-icon"></div>
                    <h3>Weddings</h3>
                    <p>Make your special day perfect with our comprehensive wedding planning services.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon birthday-icon"></div>
                    <h3>Birthdays</h3>
                    <p>Celebrate another year with a memorable birthday party for all ages.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon proposal-icon"></div>
                    <h3>Proposals</h3>
                    <p>Create the perfect moment to pop the question with our proposal planning.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon corporate-icon"></div>
                    <h3>Corporate Events</h3>
                    <p>Impress your clients and team with professionally organized corporate events.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2 class="section-title">About Naj Events</h2>
                    <p>Naj Events is a premier event planning company dedicated to creating memorable experiences for
                        all occasions. With years of experience in the industry, our team of professional planners works
                        tirelessly to ensure every detail of your event is perfect.</p>
                    <p>We believe that each event should reflect the unique personality and style of our clients.
                        Whether you're planning a wedding, birthday party, corporate event, or a special proposal, we're
                        here to bring your vision to life.</p>
                    <p>Our commitment to excellence, attention to detail, and personalized service sets us apart in the
                        event planning industry.</p>
                </div>
                <div class="about-image">
                    <img src="images/about-image.jpg" alt="Naj Events Team">
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <h2 class="section-title">What Our Clients Say</h2>
            <div class="testimonial-slider">
                <div class="testimonial-slide">
                    <p>"Naj Events made our wedding day absolutely perfect! Every detail was taken care of, allowing us
                        to enjoy our special day without worry."</p>
                    <div class="client-info">
                        <h4>Sarah & Michael</h4>
                        <p>Wedding, June 2023</p>
                    </div>
                </div>
                <div class="testimonial-slide">
                    <p>"The birthday party they organized for my daughter was magical. The decorations, entertainment,
                        and coordination were all flawless."</p>
                    <div class="client-info">
                        <h4>Jennifer L.</h4>
                        <p>Birthday Party, March 2023</p>
                    </div>
                </div>
                <div class="testimonial-slide">
                    <p>"Our company retreat was a huge success thanks to Naj Events. Professional, detail-oriented, and
                        a pleasure to work with!"</p>
                    <div class="client-info">
                        <h4>Robert T.</h4>
                        <p>Corporate Event, September 2023</p>
                    </div>
                </div>
            </div>
            <div class="testimonial-dots">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
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