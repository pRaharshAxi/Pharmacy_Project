<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if invoice_id is provided
if (!isset($_GET['invoice_id']) || empty($_GET['invoice_id'])) {
    $_SESSION['error'] = "Invoice ID is required.";
    header("Location: dashboard_pharmacist.php");
    exit();
}

$invoice_id = $_GET['invoice_id'];

// Get invoice details
$stmt = $conn->prepare("
    SELECT i.*, u.F_NAME, u.L_NAME, u.EMAIL,
           p.F_NAME AS P_F_NAME, p.L_NAME AS P_L_NAME
    FROM invoice i
    JOIN users u ON i.CUSTOMER_ID = u.USER_ID
    JOIN users p ON i.PHARMACIST_ID = p.USER_ID
    WHERE i.INVOICE_ID = ?
");
$stmt->bind_param("s", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Invoice not found.";
    header("Location: dashboard_pharmacist.php");
    exit();
}

$invoice = $result->fetch_assoc();
$stmt->close();

// Get order items
$stmt = $conn->prepare("
    SELECT m.NAME, oi.QUANTITY, oi.PRICE, (oi.QUANTITY * oi.PRICE) AS SUBTOTAL
    FROM order_item oi
    JOIN medicine m ON oi.MEDICINE_ID = m.MEDICINE_ID
    WHERE oi.ORDER_ID = ?
");
$stmt->bind_param("s", $invoice['ORDER_ID']);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice #<?php echo htmlspecialchars($invoice_id); ?> - MedCare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f5f5;
    padding: 20px;
}

.invoice-container {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    padding: 40px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 8px;
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 3px solid #2563eb;
}

.company-info h1 {
    color: #2563eb;
    font-size: 32px;
    margin-bottom: 5px;
}

.company-info p {
    color: #666;
    font-size: 14px;
}

.invoice-details {
    text-align: right;
}

.invoice-details h2 {
    font-size: 28px;
    color: #333;
    margin-bottom: 10px;
}

.invoice-details p {
    color: #666;
    margin: 5px 0;
}

.info-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.info-box {
    background: #f9fafb;
    padding: 20px;
    border-radius: 6px;
}

.info-box h3 {
    color: #333;
    margin-bottom: 12px;
    font-size: 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-box p {
    color: #666;
    margin: 5px 0;
    font-size: 14px;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
}

.items-table thead {
    background: #2563eb;
    color: white;
}

.items-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.items-table tbody tr {
    border-bottom: 1px solid #e5e7eb;
}

.items-table tbody tr:hover {
    background: #f9fafb;
}

.items-table td {
    padding: 15px;
    color: #333;
}

.items-table td:last-child,
.items-table th:last-child {
    text-align: right;
}

.total-section {
    display: flex;
    justify-content: flex-end;
    margin-top: 30px;
}

.total-box {
    width: 300px;
    background: #f9fafb;
    padding: 20px;
    border-radius: 6px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    color: #666;
}

.total-row.grand-total {
    border-top: 2px solid #2563eb;
    margin-top: 10px;
    padding-top: 15px;
    font-size: 20px;
    font-weight: bold;
    color: #2563eb;
}

.action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
}

.btn-primary {
    background: #2563eb;
    color: white;
}

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-paid {
    background: #d1fae5;
    color: #065f46;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

@media print {
    body {
        background: white;
        padding: 0;
    }
    
    .invoice-container {
        box-shadow: none;
        padding: 20px;
    }
    
    .action-buttons {
        display: none;
    }
}
</style>
</head>
<body>

<div class="invoice-container">
    <!-- Invoice Header -->
    <div class="invoice-header">
        <div class="company-info">
            <h1>MedCare</h1>
            <p>Pharmacy Management System</p>
            <p>Email: info@medcare.lk</p>
            <p>Phone: 0771234567</p>
        </div>
        <div class="invoice-details">
            <h2>INVOICE</h2>
            <p><strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice_id); ?></p>
            <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($invoice['INVOICE_DATE'])); ?></p>
            <p><strong>Order #:</strong> <?php echo htmlspecialchars($invoice['ORDER_ID']); ?></p>
            <p><span class="status-badge status-paid">PAID</span></p>
        </div>
    </div>

    <!-- Customer and Pharmacist Info -->
    <div class="info-section">
        <div class="info-box">
            <h3>Bill To:</h3>
            <p><strong><?php echo htmlspecialchars($invoice['F_NAME'] . ' ' . $invoice['L_NAME']); ?></strong></p>
            <p>Customer ID: <?php echo htmlspecialchars($invoice['CUSTOMER_ID']); ?></p>
            <p>Email: <?php echo htmlspecialchars($invoice['EMAIL']); ?></p>
        </div>
        <div class="info-box">
            <h3>Processed By:</h3>
            <p><strong><?php echo htmlspecialchars($invoice['P_F_NAME'] . ' ' . $invoice['P_L_NAME']); ?></strong></p>
            <p>Pharmacist ID: <?php echo htmlspecialchars($invoice['PHARMACIST_ID']); ?></p>
            <p>Payment Method: <?php echo htmlspecialchars($invoice['PAYMENT_METHOD']); ?></p>
        </div>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th>Medicine Name</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            if ($items && $items->num_rows > 0) {
                while ($item = $items->fetch_assoc()) {
                    $item_subtotal = $item['QUANTITY'] * $item['PRICE'];
                    $subtotal += $item_subtotal;
                    echo "
                    <tr>
                        <td>" . htmlspecialchars($item['NAME']) . "</td>
                        <td>" . $item['QUANTITY'] . "</td>
                        <td>Rs. " . number_format($item['PRICE'], 2) . "</td>
                        <td>Rs. " . number_format($item_subtotal, 2) . "</td>
                    </tr>
                    ";
                }
            }
            ?>
        </tbody>
    </table>

    <!-- Total Section -->
    <div class="total-section">
        <div class="total-box">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>Rs. <?php echo number_format($invoice['TOTAL_AMOUNT'], 2); ?></span>
            </div>
            <div class="total-row">
                <span>Tax (0%):</span>
                <span>Rs. 0.00</span>
            </div>
            <div class="total-row grand-total">
                <span>Total:</span>
                <span>Rs. <?php echo number_format($invoice['TOTAL_AMOUNT'], 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="dashboard_pharmacist.php" class="btn btn-secondary">← Back to Dashboard</a>
        <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
        <a href="download_invoice.php?invoice_id=<?php echo htmlspecialchars($invoice_id); ?>" class="btn btn-success">Download PDF</a>
    </div>
</div>

</body>
</html>