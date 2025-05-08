<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Check if admin is super_admin
if ($_SESSION['admin_role'] !== 'super_admin') {
    header("Location: admin-dashboard.php");
    exit();
}

// Check if financial record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: financials.php");
    exit();
}

$record_id = $_GET['id'];

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

require "../../APIs/connect.php";

// Process payment approval if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_payment'])) {
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
    $action_details = "Approved " . $payment_type . " payment for financial record #" . $record_id;
    $log_query = "INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'payment_approval', ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
    $log_stmt->execute();
    $log_stmt->close();

    // Redirect to avoid form resubmission
    header("Location: financial-details.php?id=" . $record_id . "&success=1");
    exit();
}

// Get financial record details
$query = "SELECT f.*, r.id as reservation_id, r.name, r.email, r.phone, r.event_type, r.event_date, r.status, 
          a1.name as deposit_approver, a2.name as full_payment_approver
          FROM financial_records f 
          JOIN reservations r ON f.reservation_id = r.id 
          LEFT JOIN admins a1 ON f.deposit_approved_by = a1.id
          LEFT JOIN admins a2 ON f.full_payment_approved_by = a2.id
          WHERE f.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if record exists
if ($result->num_rows === 0) {
    header("Location: financials.php");
    exit();
}

$record = $result->fetch_assoc();
$stmt->close();

// Log admin activity
$action_details = "Viewed financial details for record #" . $record_id;
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
    <title>Financial Details - Naj Events Admin</title>
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
                <h1 class="admin-header-title">Financial Details</h1>
            </header>

            <div class="admin-content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="admin-alert admin-alert-success">
                        <i class="fas fa-check-circle"></i>
                        Payment has been successfully approved.
                        <button class="admin-alert-close">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-header-left">
                            <a href="financials.php" class="admin-btn admin-btn-light">
                                <i class="fas fa-arrow-left"></i> Back to Financials
                            </a>
                        </div>
                        <h2 class="admin-card-title">
                            Financial Record #<?php echo $record['id']; ?>
                        </h2>
                        <div class="admin-card-header-right">
                            <a href="event-details.php?id=<?php echo $record['reservation_id']; ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                                <i class="fas fa-calendar-alt"></i> View Event
                            </a>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-detail-section">
                            <h3 class="admin-detail-section-title">Event Information</h3>
                            <div class="admin-detail-grid">
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Client Name</div>
                                    <div class="admin-detail-value"><?php echo $record['name']; ?></div>
                                </div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Email</div>
                                    <div class="admin-detail-value"><?php echo $record['email']; ?></div>
                                </div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Phone</div>
                                    <div class="admin-detail-value"><?php echo $record['phone']; ?></div>
                                </div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Event Type</div>
                                    <div class="admin-detail-value"><?php echo ucfirst($record['event_type']); ?></div>
                                </div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Event Date</div>
                                    <div class="admin-detail-value"><?php echo date('F d, Y', strtotime($record['event_date'])); ?></div>
                                </div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Status</div>
                                    <div class="admin-detail-value">
                                        <span class="admin-badge <?php echo $record['status'] === 'confirmed' ? 'success' : 'info'; ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="admin-detail-section">
                            <h3 class="admin-detail-section-title">Payment Information</h3>
                            <div class="admin-detail-grid">
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Full Price</div>
                                    <div class="admin-detail-value">$<?php echo number_format($record['full_price'], 2); ?></div>
                                </div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Deposit Amount (30%)</div>
                                    <div class="admin-detail-value">$<?php echo number_format($record['deposit_amount'], 2); ?></div>
                                </div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Deposit Status</div>
                                    <div class="admin-detail-value">
                                        <?php if ($record['deposit_paid']): ?>
                                            <span class="admin-badge success">Paid</span>
                                        <?php elseif ($record['deposit_receipt']): ?>
                                            <span class="admin-badge warning">Pending Approval</span>
                                        <?php else: ?>
                                            <span class="admin-badge danger">Unpaid</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="admin-detail-item">
                                    <div class="admin-detail-label">Full Payment Status</div>
                                    <div class="admin-detail-value">
                                        <?php if ($record['full_amount_paid']): ?>
                                            <span class="admin-badge success">Paid</span>
                                        <?php elseif ($record['full_payment_receipt']): ?>
                                            <span class="admin-badge warning">Pending Approval</span>
                                        <?php else: ?>
                                            <span class="admin-badge danger">Unpaid</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($record['deposit_paid']): ?>
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Deposit Approved By</div>
                                        <div class="admin-detail-value"><?php echo $record['deposit_approver']; ?></div>
                                    </div>
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Deposit Approved At</div>
                                        <div class="admin-detail-value"><?php echo date('F d, Y H:i', strtotime($record['deposit_approved_at'])); ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($record['full_amount_paid']): ?>
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Full Payment Approved By</div>
                                        <div class="admin-detail-value"><?php echo $record['full_payment_approver']; ?></div>
                                    </div>
                                    <div class="admin-detail-item">
                                        <div class="admin-detail-label">Full Payment Approved At</div>
                                        <div class="admin-detail-value"><?php echo date('F d, Y H:i', strtotime($record['full_payment_approved_at'])); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($record['deposit_receipt'] || $record['full_payment_receipt']): ?>
                            <div class="admin-detail-section">
                                <h3 class="admin-detail-section-title">Payment Receipts</h3>
                                <div class="admin-receipts-container">
                                    <?php if ($record['deposit_receipt']): ?>
                                        <div class="admin-receipt-card">
                                            <h4>Deposit Receipt</h4>
                                            <div class="admin-receipt-image">
                                                <img src="uploads/receipts/<?php echo $record['deposit_receipt']; ?>" alt="Deposit Receipt">
                                            </div>
                                            <?php if (!$record['deposit_paid']): ?>
                                                <div class="admin-receipt-actions">
                                                    <button class="admin-btn admin-btn-success approve-payment"
                                                        data-id="<?php echo $record['id']; ?>"
                                                        data-reservation="<?php echo $record['reservation_id']; ?>"
                                                        data-type="deposit">
                                                        <i class="fas fa-check"></i> Approve Deposit Payment
                                                    </button>
                                                    <button class="admin-btn admin-btn-danger decline-payment"
                                                        data-id="<?php echo $record['id']; ?>"
                                                        data-reservation="<?php echo $record['reservation_id']; ?>"
                                                        data-type="deposit">
                                                        <i class="fas fa-times"></i> Decline Receipt
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($record['full_payment_receipt']): ?>
                                        <div class="admin-receipt-card">
                                            <h4>Full Payment Receipt</h4>
                                            <div class="admin-receipt-image">
                                                <img src="uploads/receipts/<?php echo $record['full_payment_receipt']; ?>" alt="Full Payment Receipt">
                                            </div>
                                            <?php if (!$record['full_amount_paid']): ?>
                                                <div class="admin-receipt-actions">
                                                    <button class="admin-btn admin-btn-success approve-payment"
                                                        data-id="<?php echo $record['id']; ?>"
                                                        data-reservation="<?php echo $record['reservation_id']; ?>"
                                                        data-type="full">
                                                        <i class="fas fa-check"></i> Approve Full Payment
                                                    </button>
                                                    <button class="admin-btn admin-btn-danger decline-payment"
                                                        data-id="<?php echo $record['id']; ?>"
                                                        data-reservation="<?php echo $record['reservation_id']; ?>"
                                                        data-type="full">
                                                        <i class="fas fa-times"></i> Decline Receipt
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
                <form id="approvePaymentForm" action="financial-details.php?id=<?php echo $record_id; ?>" method="post">
                    <input type="hidden" name="payment_type" id="payment_type">
                    <input type="hidden" name="reservation_id" value="<?php echo $record['reservation_id']; ?>">
                    <input type="hidden" name="approve_payment" value="1">
                    <div class="admin-form-group">
                        <button type="submit" class="admin-btn admin-btn-success">Approve Payment</button>
                        <button type="button" class="admin-btn admin-btn-light admin-modal-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Decline Payment Modal -->
    <div id="declinePaymentModal" class="admin-modal">
        <div class="admin-modal-content">
            <div class="admin-modal-header">
                <h3>Decline Payment Receipt</h3>
                <button class="admin-modal-close">&times;</button>
            </div>
            <div class="admin-modal-body">
                <form id="declinePaymentForm" action="decline-receipt.php" method="post">
                    <input type="hidden" name="record_id" id="decline_record_id">
                    <input type="hidden" name="payment_type" id="decline_payment_type">
                    <input type="hidden" name="reservation_id" id="decline_reservation_id">

                    <div class="admin-form-group">
                        <label for="decline_reason">Reason for declining:</label>
                        <textarea id="decline_reason" name="decline_reason" rows="4" class="admin-form-control" required></textarea>
                        <small class="admin-form-text">This message will be sent to the user.</small>
                    </div>

                    <div class="admin-form-group">
                        <button type="submit" class="admin-btn admin-btn-danger">Decline Receipt</button>
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
        const paymentTypeInput = document.getElementById('payment_type');

        approveButtons.forEach(button => {
            button.addEventListener('click', function() {
                const paymentType = this.getAttribute('data-type');
                paymentTypeInput.value = paymentType;
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

        // Decline payment modal
        const declineButtons = document.querySelectorAll('.decline-payment');
        const declineModal = document.getElementById('declinePaymentModal');
        const declineModalClose = declineModal.querySelector('.admin-modal-close');
        const declineModalCancel = declineModal.querySelector('.admin-modal-cancel');
        const declineRecordIdInput = document.getElementById('decline_record_id');
        const declinePaymentTypeInput = document.getElementById('decline_payment_type');
        const declineReservationIdInput = document.getElementById('decline_reservation_id');

        declineButtons.forEach(button => {
            button.addEventListener('click', function() {
                const recordId = this.getAttribute('data-id');
                const paymentType = this.getAttribute('data-type');
                const reservationId = this.getAttribute('data-reservation');

                declineRecordIdInput.value = recordId;
                declinePaymentTypeInput.value = paymentType;
                declineReservationIdInput.value = reservationId;

                declineModal.style.display = 'block';
            });
        });

        if (declineModalClose) {
            declineModalClose.addEventListener('click', function() {
                declineModal.style.display = 'none';
            });
        }

        if (declineModalCancel) {
            declineModalCancel.addEventListener('click', function() {
                declineModal.style.display = 'none';
            });
        }

        window.addEventListener('click', function(event) {
            if (event.target === declineModal) {
                declineModal.style.display = 'none';
            }
        });
    </script>

    <style>
        .admin-detail-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            padding: 20px;
        }

        .admin-detail-section-title {
            color: var(--admin-dark);
            font-size: 1.2rem;
            font-weight: 500;
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--admin-border);
        }

        .admin-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .admin-detail-item {
            margin-bottom: 10px;
        }

        .admin-detail-label {
            font-size: 0.9rem;
            color: var(--admin-gray);
            margin-bottom: 5px;
        }

        .admin-detail-value {
            font-weight: 500;
            color: var(--admin-dark);
        }

        .admin-receipts-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .admin-receipt-card {
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 8px;
            padding: 15px;
        }

        .admin-receipt-card h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.1rem;
            color: var(--admin-dark);
        }

        .admin-receipt-image {
            margin-bottom: 15px;
            text-align: center;
        }

        .admin-receipt-image img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .admin-receipt-actions {
            text-align: center;
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
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

        @media (max-width: 768px) {
            .admin-detail-grid {
                grid-template-columns: 1fr;
            }

            .admin-receipts-container {
                grid-template-columns: 1fr;
            }
        }

        .admin-btn-danger {
            background-color: var(--admin-danger);
            color: white;
        }

        .admin-btn-danger:hover {
            background-color: #d32f2f;
        }
    </style>
</body>

</html>