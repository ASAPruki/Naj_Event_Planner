<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Check if admin is super_admin
if ($_SESSION['admin_role'] !== 'super_admin') {
    header("Location: dashboard.php");
    exit();
}

// Get admin information for logging
$admin_id = $_SESSION['admin_id'];

require "../../APIs/connect.php";

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : 'all';

// Build the query based on filters
$query = "SELECT r.id, r.name, r.email, r.phone, r.event_type, r.event_date, r.status, 
          f.id as financial_id, f.full_price, f.deposit_amount, f.deposit_paid, f.full_amount_paid, 
          f.deposit_receipt, f.full_payment_receipt, f.deposit_approved_at, f.full_payment_approved_at 
          FROM reservations r 
          LEFT JOIN financial_records f ON r.id = f.reservation_id 
          WHERE 1=1";

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

// Execute the query
$result = $conn->query($query);
$records = [];
while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}

// Log admin activity
$action_details = "Exported financial records to Excel";
$log_query = "INSERT INTO admin_activity_log (admin_id, action_type, action_details, ip_address) VALUES (?, 'export', ?, ?)";
$log_stmt = $conn->prepare($log_query);
$ip_address = $_SERVER['REMOTE_ADDR'];
$log_stmt->bind_param("iss", $admin_id, $action_details, $ip_address);
$log_stmt->execute();
$log_stmt->close();

$conn->close();

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="naj_events_financial_records_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Create Excel content
echo '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Financial Records</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <h1>Naj Events - Financial Records</h1>
    <p>Generated on: ' . date('F d, Y h:i A') . '</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Event Type</th>
                <th>Event Date</th>
                <th>Status</th>
                <th>Full Price</th>
                <th>Deposit Amount</th>
                <th>Deposit Status</th>
                <th>Deposit Approved Date</th>
                <th>Full Payment Status</th>
                <th>Full Payment Approved Date</th>
            </tr>
        </thead>
        <tbody>';

$total_full_price = 0;
$total_deposit_amount = 0;
$total_paid_deposits = 0;
$total_paid_full = 0;

foreach ($records as $record) {
    $full_price = !empty($record['full_price']) ? $record['full_price'] : 0;
    $deposit_amount = !empty($record['deposit_amount']) ? $record['deposit_amount'] : 0;

    $total_full_price += $full_price;
    $total_deposit_amount += $deposit_amount;

    if ($record['deposit_paid']) {
        $total_paid_deposits += $deposit_amount;
    }

    if ($record['full_amount_paid']) {
        $total_paid_full += $full_price;
    }

    $deposit_status = $record['deposit_paid'] ? 'Paid' : ($record['deposit_receipt'] ? 'Pending Approval' : 'Unpaid');
    $full_payment_status = $record['full_amount_paid'] ? 'Paid' : ($record['full_payment_receipt'] ? 'Pending Approval' : 'Unpaid');

    $event_date = date('M d, Y', strtotime($record['event_date']));
    $deposit_approved_date = !empty($record['deposit_approved_at']) ? date('M d, Y', strtotime($record['deposit_approved_at'])) : 'N/A';
    $full_payment_approved_date = !empty($record['full_payment_approved_at']) ? date('M d, Y', strtotime($record['full_payment_approved_at'])) : 'N/A';

    $status = strtolower($record['status']);
    $status_color = '';
    switch ($status) {
        case 'pending':
            $status_color = 'background-color: yellow;';
            break;
        case 'completed':
            $status_color = 'background-color: #add8e6;';
            break;
        case 'confirmed':
            $status_color = 'background-color: #90ee90;';
            break;
        case 'cancelled':
        case 'missed':
            $status_color = 'background-color: #f08080;';
            break;
    }

    echo '
    <tr>
        <td>' . $record['id'] . '</td>
        <td>' . $record['name'] . '</td>
        <td>' . $record['email'] . '</td>
        <td>' . $record['phone'] . '</td>
        <td>' . ucfirst($record['event_type']) . '</td>
        <td>' . $event_date . '</td>
        <td style="' . $status_color . '">' . ucfirst($status) . '</td>
        <td class="text-right">$' . number_format($full_price, 2) . '</td>
        <td class="text-right">$' . number_format($deposit_amount, 2) . '</td>
        <td style="background-color: ' . ($deposit_status === 'Paid' ? '#90ee90' : ($deposit_status === 'Unpaid' ? '#f08080' : '')) . ';">' . $deposit_status . '</td>
        <td' . ($deposit_approved_date === 'N/A' ? ' style="background-color: #d3d3d3;"' : '') . '>' . $deposit_approved_date . '</td>
        <td style="background-color: ' . ($full_payment_status === 'Paid' ? '#90ee90' : ($full_payment_status === 'Unpaid' ? '#f08080' : '')) . ';">' . $full_payment_status . '</td>
        <td' . ($full_payment_approved_date === 'N/A' ? ' style="background-color: #d3d3d3;"' : '') . '>' . $full_payment_approved_date . '</td>
    </tr>';
}

echo '
        </tbody>
        <tfoot>
            <tr>
                <th colspan="7" class="text-right">Totals:</th>
                <th class="text-right">$' . number_format($total_full_price, 2) . '</th>
                <th class="text-right">$' . number_format($total_deposit_amount, 2) . '</th>
                <th colspan="4"></th>
            </tr>
            <tr>
                <th colspan="7" class="text-right">Total Paid Deposits:</th>
                <th class="text-right">$' . number_format($total_paid_deposits, 2) . '</th>
                <th colspan="5"></th>
            </tr>
            <tr>
                <th colspan="7" class="text-right">Total Paid Full Payments:</th>
                <th class="text-right">$' . number_format($total_paid_full, 2) . '</th>
                <th colspan="5"></th>
            </tr>
        </tfoot>
    </table>
    
    <p>This is an automatically generated report from Naj Events Management System.</p>
</body>
</html>';
exit;
