<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Check if event ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$event_id = $_GET['id'];

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

require "../../APIs/connect.php";

// Initialize variables for form data and error/success messages
$success_message = '';
$error_message = '';

// Get event details
$event_query = "SELECT r.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.id as user_id 
                FROM reservations r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.id = ?";
$stmt = $conn->prepare($event_query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if event exists
if ($result->num_rows === 0) {
    header("Location: events.php");
    exit();
}

$event = $result->fetch_assoc();
$stmt->close();

// Get all accessories for the form
$accessories_query = "SELECT * FROM accessories_inventory WHERE is_available = 1 ORDER BY category, name";
$accessories_result = $conn->query($accessories_query);
$accessories = [];
while ($row = $accessories_result->fetch_assoc()) {
    $accessories[] = $row;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $event_type = trim($_POST['event_type']);
    $event_date = trim($_POST['event_date']);
    $guests = (int)$_POST['guests'];
    $location_type = trim($_POST['location_type']);
    $venue = trim($_POST['venue']);
    $budget = trim($_POST['budget']);
    $status = trim($_POST['status']);
    $message = trim($_POST['message']);

    // Handle accessories
    $selected_accessories = isset($_POST['accessories']) ? $_POST['accessories'] : [];
    $accessories_str = implode(', ', $selected_accessories);

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($event_type) || empty($event_date)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!preg_match("/^\d{2}\s?\d{3}\s?\d{3}$/", $phone)) { //Check if the number is in this lebanese numbers format
        $error_message = "Please enter a valid phone number.";
    } else {
        // Build update query dynamically based on changed fields
        $update_fields = [];
        $params = [];
        $types = "";

        // Check each field for changes
        if ($name !== $event['name']) {
            $update_fields[] = "name = ?";
            $params[] = $name;
            $types .= "s";
        }

        if ($email !== $event['email']) {
            $update_fields[] = "email = ?";
            $params[] = $email;
            $types .= "s";
        }

        if ($phone !== $event['phone']) {
            $update_fields[] = "phone = ?";
            $params[] = $phone;
            $types .= "s";
        }

        if ($event_type !== $event['event_type']) {
            $update_fields[] = "event_type = ?";
            $params[] = $event_type;
            $types .= "s";
        }

        if ($event_date !== date('Y-m-d', strtotime($event['event_date']))) {
            $update_fields[] = "event_date = ?";
            $params[] = $event_date;
            $types .= "s";
        }

        if ($guests !== (int)$event['guests']) {
            $update_fields[] = "guests = ?";
            $params[] = $guests;
            $types .= "i";
        }

        if ($location_type !== $event['location_type']) {
            $update_fields[] = "location_type = ?";
            $params[] = $location_type;
            $types .= "s";
        }

        if ($venue !== $event['venue']) {
            $update_fields[] = "venue = ?";
            $params[] = $venue;
            $types .= "s";
        }

        if ($budget !== $event['budget']) {
            $update_fields[] = "budget = ?";
            $params[] = $budget;
            $types .= "s";
        }

        // Check if status has changed - we'll need this for notification
        $status_changed = ($status !== $event['status']);

        if ($status_changed) {
            $update_fields[] = "status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if ($message !== $event['message']) {
            $update_fields[] = "message = ?";
            $params[] = $message;
            $types .= "s";
        }

        if ($accessories_str !== $event['accessories']) {
            $update_fields[] = "accessories = ?";
            $params[] = $accessories_str;
            $types .= "s";
        }

        // Always update the updated_at timestamp
        $update_fields[] = "updated_at = CURRENT_TIMESTAMP";

        // Only proceed if there are changes
        if (count($update_fields) > 1) { // > 1 because we always update updated_at
            $update_query = "UPDATE reservations SET " . implode(", ", $update_fields) . " WHERE id = ?";
            $params[] = $event_id;
            $types .= "i";

            $update_stmt = $conn->prepare($update_query);

            // Bind parameters dynamically
            if (!empty($params)) {
                $refs = [];
                foreach ($params as $key => $value) {
                    $refs[$key] = &$params[$key];
                }

                call_user_func_array([$update_stmt, 'bind_param'], array_merge([$types], $refs));
            }

            if ($update_stmt->execute()) {
                // Log admin activity
                $action_details = "Updated event #" . $event_id;
                $log_query = "INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'update', ?, ?)";
                $log_stmt = $conn->prepare($log_query);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
                $log_stmt->execute();
                $log_stmt->close();

                // Create notification if status has changed and user_id exists
                if ($status_changed && isset($event['user_id']) && !empty($event['user_id'])) {
                    $user_id = $event['user_id'];
                    $event_type_display = ucfirst($event_type);
                    $notification_type = '';
                    $notification_message = '';

                    // Set notification type and message based on status
                    switch ($status) {
                        case 'confirmed':
                            $notification_type = 'success';
                            $notification_message = "Your $event_type_display event on " . date('F d, Y', strtotime($event_date)) . " has been confirmed.";
                            break;
                        case 'cancelled':
                            $notification_type = 'danger';
                            $notification_message = "Your $event_type_display event on " . date('F d, Y', strtotime($event_date)) . " has been cancelled.";
                            break;
                        case 'completed':
                            $notification_type = 'info';
                            $notification_message = "Your $event_type_display event on " . date('F d, Y', strtotime($event_date)) . " has been marked as completed.";
                            break;
                        default:
                            $notification_type = 'warning';
                            $notification_message = "The status of your $event_type_display event on " . date('F d, Y', strtotime($event_date)) . " has been updated to $status.";
                    }

                    // Insert notification
                    $notification_query = "INSERT INTO notifications (user_id, event_id, message, type) VALUES (?, ?, ?, ?)";
                    $notification_stmt = $conn->prepare($notification_query);
                    $notification_stmt->bind_param("iiss", $user_id, $event_id, $notification_message, $notification_type);
                    $notification_stmt->execute();
                    $notification_stmt->close();
                }

                $success_message = "Event updated successfully!";

                // Refresh event data
                $stmt = $conn->prepare($event_query);
                $stmt->bind_param("i", $event_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $event = $result->fetch_assoc();
                $stmt->close();
            } else {
                $error_message = "Error updating event: " . $conn->error;
            }

            $update_stmt->close();
        } else {
            $success_message = "No changes were made to the event.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Naj Events Admin</title>
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
                        <a href="events.php" class="admin-nav-link active">
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
                <h1 class="admin-header-title">Edit Event</h1>
            </header>

            <div class="admin-content">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-header-actions">
                            <a href="event-details.php?id=<?php echo $event_id; ?>" class="admin-btn admin-btn-light">
                                <i class="fas fa-arrow-left"></i> Back to Event Details
                            </a>
                        </div>
                        <h2 class="admin-card-title">
                            Edit <?php echo ucfirst($event['event_type']); ?> Event #<?php echo $event['id']; ?>
                        </h2>
                    </div>
                    <div class="admin-card-body">
                        <?php if (!empty($success_message)): ?>
                            <div class="admin-alert admin-alert-success" style="text-align: center;">
                                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error_message)): ?>
                            <div class="admin-alert admin-alert-danger" style="text-align: center;">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <form id="edit-event-form" method="post" action="edit-event.php?id=<?php echo $event_id; ?>" class="admin-form">
                            <div class="admin-form-section">
                                <h3 class="admin-form-section-title">Client Information</h3>
                                <div class="admin-form-row">
                                    <div class="admin-form-group">
                                        <label for="name">Client Name <span class="required">*</span></label>
                                        <input type="text" id="name" name="name" class="admin-form-control" value="<?php echo htmlspecialchars($event['name']); ?>" required>
                                    </div>
                                    <div class="admin-form-group">
                                        <label for="email">Email <span class="required">*</span></label>
                                        <input type="email" id="email" name="email" class="admin-form-control" value="<?php echo htmlspecialchars($event['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="admin-form-row">
                                    <div class="admin-form-group">
                                        <label for="phone">Phone <span class="required">*</span></label>
                                        <input type="text" id="phone" name="phone" class="admin-form-control" value="<?php echo htmlspecialchars($event['phone']); ?>" required>
                                    </div>
                                    <div class="admin-form-group">
                                        <label for="status">Status <span class="required">*</span></label>
                                        <select id="status" name="status" class="admin-form-select" required>
                                            <option value="pending" <?php echo $event['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $event['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="admin-form-section">
                                <h3 class="admin-form-section-title">Event Information</h3>
                                <div class="admin-form-row">
                                    <div class="admin-form-group">
                                        <label for="event_type">Event Type <span class="required">*</span></label>
                                        <select id="event_type" name="event_type" class="admin-form-select" required>
                                            <option value="wedding" <?php echo $event['event_type'] === 'wedding' ? 'selected' : ''; ?>>Wedding</option>
                                            <option value="birthday" <?php echo $event['event_type'] === 'birthday' ? 'selected' : ''; ?>>Birthday</option>
                                            <option value="corporate" <?php echo $event['event_type'] === 'corporate' ? 'selected' : ''; ?>>Corporate</option>
                                            <option value="proposal" <?php echo $event['event_type'] === 'proposal' ? 'selected' : ''; ?>>Proposal</option>
                                            <option value="katb kteb" <?php echo $event['event_type'] == 'katb kteb' ? 'selected' : ''; ?>>Katb Kteb</option>
                                            <option value="other" <?php echo $event['event_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="admin-form-group">
                                        <label for="event_date">Event Date <span class="required">*</span></label>
                                        <input type="date" id="event_date" name="event_date" class="admin-form-control" value="<?php echo date('Y-m-d', strtotime($event['event_date'])); ?>" required>
                                    </div>
                                </div>
                                <div class="admin-form-row">
                                    <div class="admin-form-group">
                                        <label for="guests">Number of Guests <span class="required">*</span></label>
                                        <input type="number" id="guests" name="guests" class="admin-form-control" value="<?php echo $event['guests']; ?>" min="1" required>
                                    </div>
                                    <div class="admin-form-group">
                                        <label for="location_type">Location Type <span class="required">*</span></label>
                                        <select id="location_type" name="location_type" class="admin-form-select" required>
                                            <option value="indoor" <?php echo $event['location_type'] === 'indoor' ? 'selected' : ''; ?>>Indoor</option>
                                            <option value="outdoor" <?php echo $event['location_type'] === 'outdoor' ? 'selected' : ''; ?>>Outdoor</option>
                                            <option value="both" <?php echo $event['location_type'] === 'both' ? 'selected' : ''; ?>>Both</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="admin-form-row">
                                    <div class="admin-form-group">
                                        <label for="venue">Venue</label>
                                        <input type="text" id="venue" name="venue" class="admin-form-control" value="<?php echo htmlspecialchars($event['venue']); ?>">
                                    </div>
                                    <div class="admin-form-group">
                                        <label for="budget">Budget Range</label>
                                        <select id="budget" name="budget" class="admin-form-select">
                                            <option value="">Select Budget Range</option>
                                            <option value="under-1000" <?php echo $event['budget'] === 'under-1000' ? 'selected' : ''; ?>>Under $1000</option>
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
                                </div>
                            </div>

                            <div class="admin-form-section">
                                <h3 class="admin-form-section-title">Accessories</h3>
                                <div class="admin-accessories-selection">
                                    <?php
                                    $event_accessories = explode(', ', $event['accessories']);
                                    $current_category = '';

                                    foreach ($accessories as $accessory):
                                        if ($current_category !== $accessory['category']):
                                            if ($current_category !== '') {
                                                echo '</div>'; // Close previous category
                                            }
                                            $current_category = $accessory['category'];
                                    ?>
                                            <h4 class="admin-accessories-category"><?php echo ucfirst($current_category); ?></h4>
                                            <div class="admin-accessories-items">
                                            <?php endif; ?>

                                            <div class="admin-accessory-checkbox">
                                                <input type="checkbox" id="accessory_<?php echo $accessory['id']; ?>" name="accessories[]" value="<?php echo $accessory['name']; ?>" <?php echo in_array($accessory['name'], $event_accessories) ? 'checked' : ''; ?>>
                                                <label for="accessory_<?php echo $accessory['id']; ?>">
                                                    <?php echo $accessory['name']; ?>
                                                </label>
                                            </div>

                                        <?php endforeach; ?>

                                        <?php if ($current_category !== ''): ?>
                                            </div> <!-- Close last category -->
                                        <?php endif; ?>
                                </div>
                            </div>

                            <br>

                            <div class="admin-form-section">
                                <h3 class="admin-form-section-title">Additional Information</h3>
                                <div class="admin-form-group">
                                    <label for="message">Message / Special Requests</label>
                                    <textarea id="message" name="message" class="admin-form-control" rows="5" style="resize: vertical;"><?php echo htmlspecialchars($event['message']); ?></textarea>
                                </div>
                            </div>

                            <div class="admin-form-actions">
                                <button type="submit" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <a href="event-details.php?id=<?php echo $event_id; ?>" class="admin-btn admin-btn-light">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../admin-scripts/edit-event.js"></script>
</body>

</html>