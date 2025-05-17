<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: index.php#login");
    exit();
}

// Check if event ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$event_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

require "../APIs/connect.php";

// Get event details
$event_query = "SELECT * FROM reservations WHERE id = ? AND (user_id = ? OR email = ?)";
$stmt = $conn->prepare($event_query);
$stmt->bind_param("iis", $event_id, $user_id, $user_email);
$stmt->execute();
$result = $stmt->get_result();

// Check if event exists and belongs to the user
if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$event = $result->fetch_assoc();
$stmt->close();

// Check if event status is pending
if ($event['status'] !== 'pending') {
    // Redirect to event details page with error message
    $_SESSION['edit_error'] = "This event cannot be edited because its status is " . ucfirst($event['status']) . ".";
    header("Location: event-details.php?id=" . $event_id);
    exit();
}

// Get available accessories
$accessories_query = "SELECT * FROM accessories_inventory WHERE is_available = 1 ORDER BY name ASC";
$accessories_result = $conn->query($accessories_query);
$accessories = [];
if ($accessories_result && $accessories_result->num_rows > 0) {
    while ($row = $accessories_result->fetch_assoc()) {
        $accessories[] = $row;
    }
}

// Process form submission
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $event_type = $_POST['event_type'];
    $event_date = $_POST['event_date'];
    $event_time = isset($_POST['event_time']) ? $_POST['event_time'] : '';
    $guests = $_POST['guests'];
    $location_type = $_POST['location_type'];
    $venue = isset($_POST['venue']) ? $_POST['venue'] : '';
    $budget = isset($_POST['budget']) && $_POST['budget'] !== '' ? $_POST['budget'] : NULL;
    $message = isset($_POST['message']) && trim($_POST['message']) !== '' ? $_POST['message'] : NULL;

    // Handle accessories array
    $selected_accessories = isset($_POST['accessories']) ? $_POST['accessories'] : array();
    $accessories_string = implode(", ", $selected_accessories);

    // Update the event in the database
    $update_stmt = $conn->prepare("UPDATE reservations SET name = ?, phone = ?, event_type = ?, event_date = ?, event_time = ?, guests = ?, location_type = ?, venue = ?, accessories = ?, budget = ?, message = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $update_stmt->bind_param("sssssisssiisi", $name, $phone, $event_type, $event_date, $event_time, $guests, $location_type, $venue, $accessories_string, $budget, $message, $event_id, $user_id);


    if ($update_stmt->execute()) {
        $success_message = "Your event has been updated successfully.";

        // Refresh event data
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        $stmt->close();
    } else {
        $error_message = "Error updating event: " . $update_stmt->error;
    }

    $update_stmt->close();
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Naj Events</title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="stylesheet" href="../styles/reservation.css">
    <link rel="stylesheet" href="../styles/edit-event.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <!-- Navigation Bar -->
    <header>
        <div class="container">
            <div class="logo">
                <h1>Naj Events</h1>
            </div>
            <nav>
                <div class="menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="accessories.html">Accessories</a></li>
                    <li><a href="reservation.php">Book an Event</a></li>
                    <li><a href="index.php#about">About Us</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">My Account</a></li>
                    <?php else: ?>
                        <li><a href="#" id="login-button">Login | Sign up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Edit Event Form Section -->
    <section class="reservation-section">
        <div class="container">
            <h2 class="section-title">Edit Your Event</h2>
            <div class="reservation-container">
                <div class="reservation-info">
                    <h3>Update Your Event Details</h3>
                    <p>You can modify your event details below as long as the status remains pending.</p>
                    <div class="event-status">
                        <strong>Status:</strong> <?php echo ucfirst($event['status']); ?>
                    </div>
                    <div class="back-link">
                        <a href="event-details.php?id=<?php echo $event_id; ?>">← Back to Event Details</a>
                    </div>
                    <?php if (!empty($success_message)): ?>
                        <div class="success-message">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="error-message">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="reservation-form">
                    <form id="event-form" action="edit-event.php?id=<?php echo $event_id; ?>" method="post">
                        <div class="form-group">
                            <label for="name">Full Name*</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($event['name']); ?>" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email*</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($event['email']); ?>" readonly required>
                                <p class="field-note">This field is automatically filled with your account email and cannot be changed.</p>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number*</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($event['phone']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="event-type">Event Type*</label>
                            <select id="event-type" name="event_type" required>
                                <option value="">Select Event Type</option>
                                <option value="wedding" <?php echo ($event['event_type'] == 'wedding') ? 'selected' : ''; ?>>Wedding</option>
                                <option value="birthday" <?php echo ($event['event_type'] == 'birthday') ? 'selected' : ''; ?>>Birthday Party</option>
                                <option value="proposal" <?php echo ($event['event_type'] == 'proposal') ? 'selected' : ''; ?>>Proposal</option>
                                <option value="corporate" <?php echo ($event['event_type'] == 'corporate') ? 'selected' : ''; ?>>Corporate Event</option>
                                <option value="katb kteb" <?php echo ($event['event_type'] == 'katb kteb') ? 'selected' : ''; ?>>Katb Kteb</option>
                                <option value="other" <?php echo ($event['event_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="event-date">Event Date*</label>
                                <?php
                                // Format the date for the input field (YYYY-MM-DD)
                                $date = $event['event_date'];
                                ?>
                                <input type="date" id="event-date" name="event_date" value="<?php echo $date; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="event-time">Event Time</label>
                                <select id="event-time" name="event_time" required>
                                    <option value="">Select time</option>
                                    <option value="12:00:00">12:00 PM</option>
                                    <option value="12:30:00">12:30 PM</option>
                                    <option value="13:00:00">1:00 PM</option>
                                    <option value="13:30:00">1:30 PM</option>
                                    <option value="14:00:00">2:00 PM</option>
                                    <option value="14:30:00">2:30 PM</option>
                                    <option value="15:00:00">3:00 PM</option>
                                    <option value="15:30:00">3:30 PM</option>
                                    <option value="16:00:00">4:00 PM</option>
                                    <option value="16:30:00">4:30 PM</option>
                                    <option value="17:00:00">5:00 PM</option>
                                    <option value="17:30:00">5:30 PM</option>
                                    <option value="18:00:00">6:00 PM</option>
                                    <option value="18:30:00">6:30 PM</option>
                                    <option value="19:00:00">7:00 PM</option>
                                    <option value="19:30:00">7:30 PM</option>
                                    <option value="20:00:00">8:00 PM</option>
                                    <option value="20:30:00">8:30 PM</option>
                                    <option value="21:00:00">9:00 PM</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="guests">Number of Guests*</label>
                            <input type="number" id="guests" name="guests" min="1" value="<?php echo $event['guests']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Event Location*</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="location_type" value="indoor" <?php echo ($event['location_type'] == 'indoor') ? 'checked' : ''; ?> required>
                                    Indoor
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="location_type" value="outdoor" <?php echo ($event['location_type'] == 'outdoor') ? 'checked' : ''; ?> required>
                                    Outdoor
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="location_type" value="both" <?php echo ($event['location_type'] == 'both') ? 'checked' : ''; ?> required>
                                    Both
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="venue">Venue Address (if already selected)</label>
                            <input type="text" id="venue" name="venue" value="<?php echo htmlspecialchars($event['venue']); ?>">
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
                                <option value="under $1000" <?php echo $event['budget'] === 'under-$1000' ? 'selected' : ''; ?>>Under $1000</option>
                                <option value="$1,000 - $2,000" <?php echo ($event['budget'] === '$1,000 - $2,000') ? 'selected' : ''; ?>>$1,000 - $2,000</option>
                                <option value="$2,000 - $3,000" <?php echo ($event['budget'] === '$2,000 - $3,000') ? 'selected' : ''; ?>>$2,000 - $3,000</option>
                                <option value="$3,000 - $4,000" <?php echo ($event['budget'] === '$3,000 - $4,000') ? 'selected' : ''; ?>>$3,000 - $4,000</option>
                                <option value="$4,000 - $5,000" <?php echo ($event['budget'] === '$4,000 - $5,000') ? 'selected' : ''; ?>>$4,000 - $5,000</option>
                                <option value="$5,000 - $7,000" <?php echo ($event['budget'] === '$5,000 - $7,000') ? 'selected' : ''; ?>>$5,000 - $7,000</option>
                                <option value="$7,000 - $10,000" <?php echo ($event['budget'] === '$7,000 - $10,000') ? 'selected' : ''; ?>>$7,000 - $10,000</option>
                                <option value="$10,000 - $20,000" <?php echo ($event['budget'] === '$10,000 - $20,000') ? 'selected' : ''; ?>>$10,000 - $20,000</option>
                                <option value="$20,000+" <?php echo ($event['budget'] === '$20,000+') ? 'selected' : ''; ?>>$20,000+</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="message">Additional Information</label>
                            <textarea id="message" name="message" rows="5"><?php echo htmlspecialchars($event['message']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <div class="button-group">
                                <a href="event-details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Reservation</button>
                            </div>
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

    <script src="../scripts/script.js"></script>
    <script src="../scripts/reservation.js"></script>
</body>

</html>