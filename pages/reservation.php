<?php
session_start();

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
$user_email = $logged_in ? $_SESSION['user_email'] : '';
$user_name = $logged_in ? (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '') : '';
$user_phone = $logged_in ? (isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : '') : '';

// If not logged in, redirect to login page
if (!$logged_in) {
    // Store the current page as the intended destination after login
    $_SESSION['redirect_after_login'] = '../pages/reservation.php';
    header("Location: index.html#login");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Event - Naj Events</title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="stylesheet" href="../styles/reservation.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">

    <script src="../scripts/script.js"></script>
    <script src="../scripts/reservation.js"></script>
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
                    <li><a href="index.html">Home</a></li>
                    <li><a href="accessories.html">Accessories</a></li>
                    <li><a href="reservation.php" class="active">Book an Event</a></li>
                    <li><a href="index.html#about">About Us</a></li>
                    <?php if ($logged_in): ?>
                        <li><a href="dashboard.php">My Account</a></li>
                    <?php else: ?>
                        <li><a href="#" id="login-button">Login / Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Reservation Form Section -->
    <section class="reservation-section">
        <div class="container">
            <h2 class="section-title">Book Your Event</h2>
            <div class="reservation-container">
                <div class="reservation-info">
                    <h3>Let's Plan Your Perfect Event</h3>
                    <p>Fill out the form with your event details, and our team will get back to you within 24 hours to discuss your requirements in detail.</p>
                    <div class="contact-info">
                        <div class="contact-item">
                            <span class="icon phone-icon"></span>
                            <p>(123) 456-7890</p>
                        </div>
                        <div class="contact-item">
                            <span class="icon email-icon"></span>
                            <p>info@najevents.com</p>
                        </div>
                        <div class="contact-item">
                            <span class="icon location-icon"></span>
                            <p>123 Event Street, City, Country</p>
                        </div>
                    </div>
                </div>
                <div class="reservation-form">
                    <form id="event-form" action="../APIs/process-reservation.php" method="post">
                        <div class="form-group">
                            <label for="name">Full Name*</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email*</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" readonly required>
                                <p class="field-note">This field is automatically filled with your account email and cannot be changed.</p>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number*</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_phone); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="event-type">Event Type*</label>
                            <select id="event-type" name="event_type" required>
                                <option value="">Select Event Type</option>
                                <option value="wedding">Wedding</option>
                                <option value="birthday">Birthday Party</option>
                                <option value="proposal">Proposal</option>
                                <option value="corporate">Corporate Event</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="event-date">Event Date*</label>
                                <input type="date" id="event-date" name="event_date" required>
                            </div>
                            <div class="form-group">
                                <label for="guests">Number of Guests*</label>
                                <input type="number" id="guests" name="guests" min="1" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Event Location*</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="location_type" value="indoor" required>
                                    Indoor
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="location_type" value="outdoor" required>
                                    Outdoor
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="location_type" value="both" required>
                                    Both
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="venue">Venue Address (if already selected)</label>
                            <input type="text" id="venue" name="venue">
                        </div>
                        <div class="form-group">
                            <label for="accessories">Accessories Needed</label>
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="accessories[]" value="tables">
                                    Tables
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="accessories[]" value="chairs">
                                    Chairs
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="accessories[]" value="decorations">
                                    Decorations
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="accessories[]" value="lighting">
                                    Lighting
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="accessories[]" value="sound">
                                    Sound System
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="accessories[]" value="catering">
                                    Catering Equipment
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="accessories[]" value="photography">
                                    Photography
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="budget">Estimated Budget (USD)</label>
                            <select id="budget" name="budget">
                                <option value="">Select Budget Range</option>
                                <option value="1000-3000">$1,000 - $3,000</option>
                                <option value="3000-5000">$3,000 - $5,000</option>
                                <option value="5000-10000">$5,000 - $10,000</option>
                                <option value="10000-20000">$10,000 - $20,000</option>
                                <option value="20000+">$20,000+</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="message">Additional Information</label>
                            <textarea id="message" name="message" rows="5"></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Submit Reservation</button>
                        </div>
                    </form>
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
</body>

</html>