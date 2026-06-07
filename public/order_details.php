<?php
session_start();
require_once '../config/db_connect.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'CUSTOMER') {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

// Check if order_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Order ID is required.";
    header("Location: my_order.php");
    exit();
}

$order_id = $_GET['id'];

// Get order details - verify it belongs to this customer
$stmt = $conn->prepare("
    SELECT o.ORDER_ID, o.ORDER_DATE, o.TOTAL_AMOUNT, o.STATUS, o.CUSTOMER_ID
    FROM orders o
    WHERE o.ORDER_ID = ? AND o.CUSTOMER_ID = ?
");
$stmt->bind_param("ss", $order_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Order not found or you don't have permission to view it.";
    header("Location: my_order.php");
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Get order items
$stmt = $conn->prepare("
    SELECT m.NAME, m.CATEGORY, m.DOSAGE, oi.QUANTITY, oi.Price, (oi.QUANTITY * oi.Price) AS SUBTOTAL
    FROM order_item oi
    JOIN medicine m ON oi.MEDICINE_ID = m.MEDICINE_ID
    WHERE oi.ORDER_ID = ?
");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

// Check if invoice exists for this order
$invoice = null;
$stmt = $conn->prepare("
    SELECT INVOICE_ID, INVOICE_DATE, PAYMENT_METHOD
    FROM invoice
    WHERE ORDER_ID = ?
");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$invoice_result = $stmt->get_result();
if ($invoice_result->num_rows > 0) {
    $invoice = $invoice_result->fetch_assoc();
}
$stmt->close();

// Function to convert database status to customer-friendly status
function getCustomerStatus($dbStatus) {
    if ($dbStatus === 'PROCESSING') {
        return 'ACCEPTED';
    }
    return $dbStatus;
}

$customerStatus = getCustomerStatus($order['STATUS']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Details #<?php echo htmlspecialchars($order_id); ?> - MedCare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f7fa;
    padding: 20px;
}

.order-container {
    max-width: 1000px;
    margin: 0 auto;
    background: white;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e5e7eb;
}

.order-header h1 {
    color: #1f2937;
    font-size: 28px;
}

.status-badge {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-accepted {
    background: #dbeafe;
    color: #1e40af;
}

.status-completed {
    background: #d1fae5;
    color: #065f46;
}

.status-cancel {
    background: #fee2e2;
    color: #991b1b;
}

.order-info {
    background: #f9fafb;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 30px;
    border-left: 4px solid #3b82f6;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
}

.info-label {
    color: #6b7280;
    font-weight: 500;
}

.info-value {
    color: #1f2937;
    font-weight: 600;
}

.items-section {
    margin: 30px 0;
}

.items-section h2 {
    color: #1f2937;
    margin-bottom: 20px;
    font-size: 20px;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.items-table thead {
    background: #3b82f6;
    color: white;
}

.items-table th {
    padding: 14px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
}

.items-table tbody tr {
    border-bottom: 1px solid #e5e7eb;
}

.items-table tbody tr:hover {
    background: #f9fafb;
}

.items-table td {
    padding: 14px;
    color: #374151;
}

.items-table td:last-child {
    text-align: right;
    font-weight: 600;
}

.medicine-badge {
    display: inline-block;
    padding: 4px 8px;
    background: #e0e7ff;
    color: #4338ca;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 8px;
}

.total-section {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}

.total-card {
    width: 350px;
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    color: #6b7280;
}

.total-row.grand-total {
    border-top: 2px solid #3b82f6;
    margin-top: 10px;
    padding-top: 15px;
    font-size: 20px;
    font-weight: bold;
    color: #1f2937;
}

.invoice-section {
    background: #dbeafe;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    border-left: 4px solid #3b82f6;
}

.invoice-section h3 {
    color: #1e40af;
    margin-bottom: 10px;
}

.invoice-info {
    color: #1e40af;
}

.action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #e5e7eb;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.status-message {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
}

.status-message.pending {
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid #f59e0b;
}

.status-message.accepted {
    background: #dbeafe;
    color: #1e40af;
    border-left: 4px solid #3b82f6;
}

.status-message.completed {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}
</style>
</head>
<body>

<div class="order-container">
    <!-- Order Header -->
    <div class="order-header">
        <div>
            <h1>Order #<?php echo htmlspecialchars($order_id); ?></h1>
            <p style="color: #6b7280; margin-top: 5px;">
                Placed on <?php echo date('F d, Y \a\t h:i A', strtotime($order['ORDER_DATE'])); ?>
            </p>
        </div>
        <span class="status-badge status-<?php echo strtolower($customerStatus); ?>">
            <?php echo htmlspecialchars($customerStatus); ?>
        </span>
    </div>

    <!-- Status Message -->
    <?php if ($customerStatus === 'PENDING'): ?>
    <div class="status-message pending">
        ⏳ Your order is waiting to be accepted by a pharmacist. We'll notify you once it's accepted!
    </div>
    <?php elseif ($customerStatus === 'ACCEPTED'): ?>
    <div class="status-message accepted">
        ✅ Great news! Your order has been accepted by our pharmacist and is being processed. 
        <?php if ($invoice): ?>
            An invoice has been generated for your order.
        <?php endif; ?>
    </div>
    <?php elseif ($customerStatus === 'COMPLETED'): ?>
    <div class="status-message completed">
        🎉 Your order has been completed and delivered. Thank you for choosing MedCare!
    </div>
    <?php endif; ?>

    <!-- Invoice Section (if exists) -->
    <?php if ($invoice): ?>
    <div class="invoice-section">
        <h3>📄 Invoice Generated</h3>
        <div class="invoice-info">
            <p><strong>Invoice ID:</strong> <?php echo htmlspecialchars($invoice['INVOICE_ID']); ?></p>
            <p><strong>Invoice Date:</strong> <?php echo date('M d, Y', strtotime($invoice['INVOICE_DATE'])); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($invoice['PAYMENT_METHOD']); ?></p>
            <a href="view_invoice.php?invoice_id=<?php echo htmlspecialchars($invoice['INVOICE_ID']); ?>" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#2563eb'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='#3b82f6'; this.style.transform='translateY(0)';">
                View Invoice
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Order Information -->
    <div class="order-info">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Order ID:</span>
                <span class="info-value">#<?php echo htmlspecialchars($order_id); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Order Date:</span>
                <span class="info-value"><?php echo date('M d, Y', strtotime($order['ORDER_DATE'])); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value"><?php echo htmlspecialchars($customerStatus); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Amount:</span>
                <span class="info-value" style="color: #3b82f6; font-size: 18px;">Rs. <?php echo number_format($order['TOTAL_AMOUNT'], 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="items-section">
        <h2>📋 Order Items</h2>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Medicine Name</th>
                    <th>Category</th>
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
                        $item_subtotal = $item['QUANTITY'] * $item['Price'];
                        $subtotal += $item_subtotal;
                        echo "
                        <tr>
                            <td>
                                <strong>" . htmlspecialchars($item['NAME']) . "</strong>
                                <span class='medicine-badge'>" . htmlspecialchars($item['DOSAGE'] ?? 'N/A') . "</span>
                            </td>
                            <td>" . htmlspecialchars($item['CATEGORY']) . "</td>
                            <td>" . $item['QUANTITY'] . "</td>
                            <td>Rs. " . number_format($item['Price'], 2) . "</td>
                            <td>Rs. " . number_format($item_subtotal, 2) . "</td>
                        </tr>
                        ";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding:20px;'>No items found</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Total Section -->
        <div class="total-section">
            <div class="total-card">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>Rs. <?php echo number_format($order['TOTAL_AMOUNT'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Tax (0%):</span>
                    <span>Rs. 0.00</span>
                </div>
                <div class="total-row">
                    <span>Delivery Fee:</span>
                    <span>Rs. 0.00</span>
                </div>
                <div class="total-row grand-total">
                    <span>Total:</span>
                    <span>Rs. <?php echo number_format($order['TOTAL_AMOUNT'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="my_order.php" class="btn btn-secondary">
            ← Back to My Orders
        </a>
        <a href="dashboard_customer.php" class="btn btn-primary">
            Go to Dashboard
        </a>
    </div>
</div>

</body>
</html>

<?php
$conn->close();
?>