<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin-login.php");
    exit();
}

// Check if admin is super_admin
if ($_SESSION['admin_role'] !== 'super_admin') {
    header("Location: dashboard.php");
    exit();
}

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

require "../../APIs/connect.php";

// Process payment approval if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_payment'])) {
    $record_id = $_POST['record_id'];
    $payment_type = $_POST['payment_type'];
    $reservation_id = $_POST['reservation_id'];

    // Update the financial record
    if ($payment_type === 'deposit') {
        $update_query = "UPDATE financial_records SET deposit_paid = 1, deposit_approved_by = ?, deposit_approved_at = NOW() WHERE id = ?";
    } else {
        $update_query = "UPDATE financial_records SET full_amount_paid = 1, full_payment_approved_by = ?, full_payment_approved_at = NOW() WHERE id = ?";
    }

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $admin_id, $record_id);
    $stmt->execute();
    $stmt->close();

    // Log admin activity
    $action_details = "Approved " . $payment_type . " payment for reservation #" . $reservation_id;
    $log_query = "INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'payment_approval', ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
    $log_stmt->execute();
    $log_stmt->close();

    // Redirect to avoid form resubmission
    header("Location: financials.php?success=1");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : 'all';

// Build the query based on filters
$query = "SELECT r.id, r.name, r.email, r.phone, r.event_type, r.event_date, r.status, 
          f.id as financial_id, f.full_price, f.deposit_amount, f.deposit_paid, f.full_amount_paid, 
          f.deposit_receipt, f.full_payment_receipt 
          FROM reservations r 
          LEFT JOIN financial_records f ON r.id = f.reservation_id 
          WHERE (r.status = 'confirmed' OR r.status = 'completed')";

// Add status filter
if ($status_filter !== 'all') {
    $query .= " AND r.status = '$status_filter'";
}

// Add payment filter
if ($payment_filter === 'unpaid') {
    $query .= " AND (f.deposit_paid = 0 OR f.full_amount_paid = 0 OR f.id IS NULL)";
} elseif ($payment_filter === 'partially_paid') {
    $query .= " AND f.deposit_paid = 1 AND f.full_amount_paid = 0";
} elseif ($payment_filter === 'fully_paid') {
    $query .= " AND f.deposit_paid = 1 AND f.full_amount_paid = 1";
}

$query .= " ORDER BY r.event_date DESC";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total records for pagination
$total_records_query = "SELECT COUNT(*) as total FROM ($query) as subquery";
$total_result = $conn->query($total_records_query);
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Add pagination to the main query
$query .= " LIMIT $offset, $records_per_page";

// Execute the query
$result = $conn->query($query);
$events = [];
while ($row = $result->fetch_assoc()) {
    // If financial record doesn't exist, create a new one with default values
    if (!isset($row['financial_id'])) {
        // Default values
        $full_price = 0;
        $deposit_amount = 0;

        // Add price per guest
        $price_per_guest = 50;
        $full_price = $base_price + ($row['guests'] * $price_per_guest);
        $deposit_amount = $full_price * 0.3;

        // Insert new financial record
        $insert_query = "INSERT INTO financial_records (reservation_id, full_price, deposit_amount) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("idd", $row['id'], $full_price, $deposit_amount);
        $insert_stmt->execute();
        $financial_id = $conn->insert_id;
        $insert_stmt->close();

        // Update the row with the new financial data
        $row['financial_id'] = $financial_id;
        $row['full_price'] = $full_price;
        $row['deposit_amount'] = $deposit_amount;
        $row['deposit_paid'] = 0;
        $row['full_amount_paid'] = 0;
        $row['deposit_receipt'] = null;
        $row['full_payment_receipt'] = null;
    }

    $events[] = $row;
}

// Log admin activity for page view
$action_details = "Viewed financial management page";
$log_query = "INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'view', ?, ?)";
$log_stmt = $conn->prepare($log_query);
$ip_address = $_SERVER['REMOTE_ADDR'];
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
    <title>Financial Management - Naj Events Admin</title>
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
                        <a href="financials.php" class="admin-nav-link active">
                            <i class="fas fa-star admin-nav-icon"></i>
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
                <h1 class="admin-header-title">Financial Management</h1>
            </header>

            <div class="admin-content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="admin-alert admin-alert-success">
                        <i class="fas fa-check-circle"></i>
                        Payment has been successfully approved.
                        <button class="admin-alert-close">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['declined'])): ?>
                    <div class="admin-alert admin-alert-success">
                        <i class="fas fa-check-circle"></i>
                        Receipt has been successfully declined and the user has been notified.
                        <button class="admin-alert-close">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Filters</h2>
                    </div>
                    <div class="admin-card-body">
                        <form action="financials.php" method="get" class="admin-filters-form">
                            <div class="admin-filter-group">
                                <label for="status">Event Status</label>
                                <select name="status" id="status" class="admin-form-select">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="admin-filter-group">
                                <label for="payment">Payment Status</label>
                                <select name="payment" id="payment" class="admin-form-select">
                                    <option value="all" <?php echo $payment_filter === 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="unpaid" <?php echo $payment_filter === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                    <option value="partially_paid" <?php echo $payment_filter === 'partially_paid' ? 'selected' : ''; ?>>Deposit Paid Only</option>
                                    <option value="fully_paid" <?php echo $payment_filter === 'fully_paid' ? 'selected' : ''; ?>>Fully Paid</option>
                                </select>
                            </div>
                            <div class="admin-filter-group">
                                <button type="submit" class="admin-btn admin-btn-primary">Apply Filters</button>
                                <a href="financials.php" class="admin-btn admin-btn-light">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Financial Records -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">Financial Records</h2>
                        <div class="admin-card-actions">
                            <a href="#" class="admin-btn admin-btn-primary admin-btn-sm" id="exportFinancials">
                                <i class="fas fa-file-export"></i> Export
                            </a>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Event Type</th>
                                        <th>Event Date</th>
                                        <th>Status</th>
                                        <th>Full Price</th>
                                        <th>Deposit (30%)</th>
                                        <th>Deposit Status</th>
                                        <th>Full Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($events) > 0): ?>
                                        <?php foreach ($events as $event): ?>
                                            <tr>
                                                <td>#<?php echo $event['id']; ?></td>
                                                <td><?php echo $event['name']; ?></td>
                                                <td><?php echo ucfirst($event['event_type']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                                <td>
                                                    <span class="admin-badge <?php echo $event['status'] === 'confirmed' ? 'success' : 'info'; ?>">
                                                        <?php echo ucfirst($event['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($event['full_price'] > 0): ?>
                                                        $<?php echo number_format($event['full_price'], 2); ?>
                                                    <?php else: ?>
                                                        <span class="admin-badge warning">Not Set</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($event['deposit_amount'] > 0): ?>
                                                        $<?php echo number_format($event['deposit_amount'], 2); ?>
                                                        <?php if ($event['full_price'] > 0): ?>
                                                            (<?php echo round(($event['deposit_amount'] / $event['full_price']) * 100); ?>%)
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="admin-badge warning">Not Set</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($event['deposit_paid']): ?>
                                                        <span class="admin-badge success">Paid</span>
                                                    <?php elseif ($event['deposit_receipt']): ?>
                                                        <span class="admin-badge warning">Pending Approval</span>
                                                    <?php else: ?>
                                                        <span class="admin-badge danger">Unpaid</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($event['full_amount_paid']): ?>
                                                        <span class="admin-badge success">Paid</span>
                                                    <?php elseif ($event['full_payment_receipt']): ?>
                                                        <span class="admin-badge warning">Pending Approval</span>
                                                    <?php else: ?>
                                                        <span class="admin-badge danger">Unpaid</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="admin-table-actions">
                                                        <a href="financial-details.php?id=<?php echo $event['financial_id']; ?>" class="admin-btn admin-btn-info admin-btn-sm">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <?php if ($event['deposit_receipt'] && !$event['deposit_paid']): ?>
                                                            <button class="admin-btn admin-btn-success admin-btn-sm approve-payment"
                                                                data-id="<?php echo $event['financial_id']; ?>"
                                                                data-reservation="<?php echo $event['id']; ?>"
                                                                data-type="deposit">
                                                                <i class="fas fa-check"></i> Approve Deposit
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($event['full_payment_receipt'] && !$event['full_amount_paid']): ?>
                                                            <button class="admin-btn admin-btn-success admin-btn-sm approve-payment"
                                                                data-id="<?php echo $event['financial_id']; ?>"
                                                                data-reservation="<?php echo $event['id']; ?>"
                                                                data-type="full">
                                                                <i class="fas fa-check"></i> Approve Full Payment
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center">No financial records found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="admin-pagination" style="justify-content: flex-end;">

                                <!-- Previous Page -->
                                <?php if ($page > 1): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&payment=<?php echo $payment_filter; ?>" class="admin-pagination-link">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <!-- -10 Pages -->
                                <?php if ($page > 10): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo max($page - 10, 1); ?>&status=<?php echo $status_filter; ?>&payment=<?php echo $payment_filter; ?>" class="admin-pagination-link">
                                            <i class="fas fa-angles-left"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $start_page + 4);
                                if ($end_page - $start_page < 4) {
                                    $start_page = max(1, $end_page - 4);
                                }
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&payment=<?php echo $payment_filter; ?>"
                                            class="admin-pagination-link <?php echo $i === $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </div>
                                <?php endfor; ?>

                                <!-- +10 Pages -->
                                <?php if ($page < $total_pages - 9): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo min($page + 10, $total_pages); ?>&status=<?php echo $status_filter; ?>&payment=<?php echo $payment_filter; ?>" class="admin-pagination-link">
                                            <i class="fas fa-angles-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <!-- Next Page -->
                                <?php if ($page < $total_pages): ?>
                                    <div class="admin-pagination-item">
                                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&payment=<?php echo $payment_filter; ?>" class="admin-pagination-link">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Approve Payment Modal -->
    <div id="approvePaymentModal" class="admin-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3>Approve Payment</h3>
                <button class="admin-modal-close">&times;</button>
            </div>
            <div class="admin-modal-body">
                <p>Are you sure you want to approve this payment?</p>
                <p>This action cannot be undone.</p>
                <form id="approvePaymentForm" action="financials.php" method="post">
                    <input type="hidden" name="record_id" id="record_id">
                    <input type="hidden" name="payment_type" id="payment_type">
                    <input type="hidden" name="reservation_id" id="reservation_id">
                    <input type="hidden" name="approve_payment" value="1">
                    <div class="admin-form-group">
                        <button type="submit" class="admin-btn admin-btn-success">Approve Payment</button>
                        <button type="button" class="admin-btn admin-btn-light admin-modal-cancel">Cancel</button>
                    </div>
                </form>
            </div>
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

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const isClickInsideSidebar = adminSidebar.contains(event.target);
            const isClickInsideToggle = toggleSidebar.contains(event.target);

            if (window.innerWidth <= 992 && !isClickInsideSidebar && !isClickInsideToggle && adminSidebar.classList.contains('active')) {
                adminSidebar.classList.remove('active');
                adminMain.classList.remove('sidebar-active');
            }
        });

        // Alert close button
        const alertCloseButtons = document.querySelectorAll('.admin-alert-close');
        alertCloseButtons.forEach(button => {
            button.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });

        // Approve payment modal
        const approveButtons = document.querySelectorAll('.approve-payment');
        const approveModal = document.getElementById('approvePaymentModal');
        const modalClose = approveModal.querySelector('.admin-modal-close');
        const modalCancel = approveModal.querySelector('.admin-modal-cancel');
        const recordIdInput = document.getElementById('record_id');
        const paymentTypeInput = document.getElementById('payment_type');
        const reservationIdInput = document.getElementById('reservation_id');

        approveButtons.forEach(button => {
            button.addEventListener('click', function() {
                const recordId = this.getAttribute('data-id');
                const reservationId = this.getAttribute('data-reservation');
                const paymentType = this.getAttribute('data-type');

                recordIdInput.value = recordId;
                paymentTypeInput.value = paymentType;
                reservationIdInput.value = reservationId;

                approveModal.style.display = 'block';
            });
        });

        if (modalClose) {
            modalClose.addEventListener('click', function() {
                approveModal.style.display = 'none';
            });
        }

        if (modalCancel) {
            modalCancel.addEventListener('click', function() {
                approveModal.style.display = 'none';
            });
        }

        window.addEventListener('click', function(event) {
            if (event.target === approveModal) {
                approveModal.style.display = 'none';
            }
        });

        // Export functionality
        document.getElementById('exportFinancials').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Export functionality will be implemented here.');
        });
    </script>

    <style>
        .admin-filters-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .admin-filter-group {
            flex: 1;
            min-width: 200px;
        }

        .admin-filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .admin-alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            position: relative;
        }

        .admin-alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .admin-alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--admin-success);
            border-left: 4px solid var(--admin-success);
        }

        .admin-alert-close {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        }

        .admin-alert-close:hover {
            opacity: 1;
        }

        .admin-modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .admin-modal-content {
            background-color: white;
            margin: 10% auto;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            animation: modalFadeIn 0.3s;
        }

        .admin-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .admin-modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 500;
        }

        .admin-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--admin-gray);
        }

        .admin-modal-body {
            padding: 20px;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>

</html>