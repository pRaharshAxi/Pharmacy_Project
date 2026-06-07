<?php
session_start();
require_once '../config/db_connect.php';

// Check if pharmacist is logged in
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    $_SESSION['error'] = "Order ID is required.";
    header("Location: dashboard_pharmacist.php");
    exit();
}

$order_id = $_GET['order_id'];

// Get order details
$stmt = $conn->prepare("
    SELECT o.ORDER_ID, o.CUSTOMER_ID, o.ORDER_DATE, o.TOTAL_AMOUNT, o.STATUS, o.PRESCRIPTION_PATH, 
           u.F_NAME, u.L_NAME, u.EMAIL
    FROM orders o
    JOIN users u ON o.CUSTOMER_ID = u.USER_ID
    WHERE o.ORDER_ID = ?
");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Order not found.";
    header("Location: dashboard_pharmacist.php");
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Get order items
$stmt = $conn->prepare("
    SELECT m.NAME, m.CATEGORY, m.DOSAGE, oi.QUANTITY, oi.PRICE, (oi.QUANTITY * oi.PRICE) AS SUBTOTAL
    FROM order_item oi
    JOIN medicine m ON oi.MEDICINE_ID = m.MEDICINE_ID
    WHERE oi.ORDER_ID = ?
");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

$pharmacist_name = $_SESSION['f_name'] . ' ' . $_SESSION['l_name'];
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
            font-family: 'Montserrat', sans-serif;
            background: #f5f5f5;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 290px;
            background: linear-gradient(180deg, #181c2a 0%, #111827 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: space-between;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 4px 0 12px rgba(0,0,0,0.10);
            overflow: hidden;
        }

        .sidebar-logo {
            padding: 0 24px;
            margin-bottom: 32px;
            margin-top: 24px;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #fff;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 28px;
            border-radius: 12px;
            color: #cbd5e1;
            font-weight: 500;
            text-decoration: none;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.05);
        }

        .sidebar-nav a.active {
            background: #b7aaff;
            color: #23232a;
            font-weight: 600;
            margin-bottom: 2px;
            margin-left: 10px;
            margin-right: -8px;
            box-shadow: 0 6px 24px 0 rgba(183,170,255,0.25), 0 1.5px 8px 0 rgba(0,0,0,0.10);
            position: relative;
            z-index: 2;
            border-radius: 14px;
        }

        .main-content {
            margin-left: 290px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .header-bar {
            background: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-bottom: 1px solid #e5e7eb;
        }

        .header-bar h1 {
            color: #111827;
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0;
        }

        .content {
            flex: 1;
            overflow-y: auto;
            padding: 32px 40px;
        }

        .order-container {
            max-width: 1000px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-header h2 {
            margin: 0;
            font-size: 24px;
        }

        .status-badge {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .order-details {
            padding: 30px 40px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-section h3 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .detail-item {
            margin-bottom: 16px;
        }

        .detail-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 15px;
            color: #111827;
            font-weight: 500;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            padding: 30px 40px;
        }

        .items-table thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        .items-table th {
            padding: 14px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 14px;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tbody tr:hover {
            background: #f9fafb;
        }

        .subtotal-row {
            font-weight: 600;
            background: #f9fafb;
        }

        .subtotal-row td {
            border-top: 2px solid #e5e7eb;
            padding: 14px;
        }

        .prescription-section {
            padding: 30px 40px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .prescription-section h3 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 16px;
            letter-spacing: 0.5px;
        }

        .prescription-container {
            background: white;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .prescription-container.has-file {
            border: 2px solid #10b981;
            background: #f0fdf4;
        }

        .prescription-button {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.2s;
            margin: 8px;
        }

        .prescription-button:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }

        .no-prescription {
            color: #6b7280;
            font-size: 14px;
        }

        .action-buttons {
            padding: 30px 40px;
            display: flex;
            gap: 12px;
            justify-content: flex-start;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-back {
            background: #f1f5f9;
            color: #374151;
        }

        .btn-back:hover {
            background: #e2e8f0;
        }

        .btn-accept {
            background: #10b981;
            color: white;
        }

        .btn-accept:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            .order-details {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 20px;
            }

            .header-bar {
                padding: 15px 20px;
            }
        }
    </style>
</head>

<body>

<!-- Sidebar -->
<div class="sidebar">
    <div>
        <div class="sidebar-logo">MedCare</div>
        <nav class="sidebar-nav">
            <a href="dashboard_pharmacist.php">Dashboard</a>
            <a href="view_orders.php" class="active">Orders</a>
            <a href="view_low_stock.php">Low Stock</a>
            <a href="view_suppliers.php">Suppliers</a>
        </nav>
    </div>
    <div style="padding: 0 24px 24px 24px;">
        <div style="display: flex; align-items: center; gap: 10px; background: #23263a; border-radius: 10px; padding: 10px 14px;">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: #374151; display: flex; align-items: center; justify-content: center; font-size: 1.1em; font-weight: 700; color: #fff;">P</div>
            <div style="flex:1; min-width:0;">
                <div style="font-size: 1em; font-weight: 600; color: #fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?php echo htmlspecialchars($pharmacist_name); ?>
                </div>
                <div style="font-size: 0.85em; color: #cbd5e1;">Pharmacist</div>
            </div>
            <a href="logout.php" style="color: #cbd5e1; font-size: 1.2em; text-decoration: none; margin-left: 8px;">⎋</a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">

    <!-- Header Bar -->
    <div class="header-bar">
        <h1>Order Details</h1>
        <a href="logout.php" style="display: flex; align-items: center; gap: 8px; color: white; background: #3B82F6; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 6px; transition: all 0.3s; font-size: 0.9rem;" onmouseover="this.style.background='#2563EB';" onmouseout="this.style.background='#3B82F6';">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
        </a>
    </div>

    <!-- Content Area -->
    <div class="content">

        <div class="order-container">

            <!-- Order Header -->
            <div class="order-header">
                <div>
                    <h2>Order #<?php echo htmlspecialchars($order['ORDER_ID']); ?></h2>
                    <p style="margin-top: 8px; opacity: 0.9;">Placed on <?php echo date('F d, Y', strtotime($order['ORDER_DATE'])); ?></p>
                </div>
                <span class="status-badge status-<?php echo strtolower($order['STATUS']); ?>">
                    <?php echo htmlspecialchars($order['STATUS']); ?>
                </span>
            </div>

            <!-- Order Details -->
            <div class="order-details">

                <!-- Customer Information -->
                <div class="detail-section">
                    <h3>👤 Customer Information</h3>
                    <div class="detail-item">
                        <div class="detail-label">Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order['F_NAME'] . ' ' . $order['L_NAME']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Customer ID</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order['CUSTOMER_ID']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order['EMAIL']); ?></div>
                    </div>
                </div>

                <!-- Order Information -->
                <div class="detail-section">
                    <h3>📦 Order Information</h3>
                    <div class="detail-item">
                        <div class="detail-label">Order ID</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order['ORDER_ID']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Order Date</div>
                        <div class="detail-value"><?php echo date('F d, Y', strtotime($order['ORDER_DATE'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order['STATUS']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Total Amount</div>
                        <div class="detail-value" style="color: #3b82f6; font-size: 18px;">Rs. <?php echo number_format($order['TOTAL_AMOUNT'], 2); ?></div>
                    </div>
                </div>

            </div>

            <!-- Order Items -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Category</th>
                        <th>Dosage</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    if ($items && $items->num_rows > 0) {
                        while ($item = $items->fetch_assoc()) {
                            $subtotal = $item['QUANTITY'] * $item['PRICE'];
                            $total += $subtotal;
                            echo "
                            <tr>
                                <td><strong>" . htmlspecialchars($item['NAME']) . "</strong></td>
                                <td>" . htmlspecialchars($item['CATEGORY']) . "</td>
                                <td>" . htmlspecialchars($item['DOSAGE'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars($item['QUANTITY']) . "</td>
                                <td>Rs. " . number_format($item['PRICE'], 2) . "</td>
                                <td style='text-align: right; font-weight: 600;'>Rs. " . number_format($subtotal, 2) . "</td>
                            </tr>
                            ";
                        }
                    }
                    ?>
                    <tr class="subtotal-row">
                        <td colspan="5" style="text-align: right;">Total Amount:</td>
                        <td style="text-align: right;">Rs. <?php echo number_format($total, 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Prescription Section -->
            <?php if ($order['PRESCRIPTION_PATH']): ?>
            <div class="prescription-section">
                <h3>📋 Prescription</h3>
                <div class="prescription-container has-file">
                    <p style="margin-bottom: 12px; color: #10b981; font-weight: 600;">✓ Prescription uploaded</p>
                    <a href="view_prescription.php?order_id=<?php echo htmlspecialchars($order['ORDER_ID']); ?>" class="prescription-button" download>
                        <span>📥</span> Download Prescription
                    </a>
                    <a href="view_prescription.php?order_id=<?php echo htmlspecialchars($order['ORDER_ID']); ?>" class="prescription-button" target="_blank">
                        <span>👁️</span> View Prescription
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="prescription-section">
                <h3>📋 Prescription</h3>
                <div class="prescription-container">
                    <p class="no-prescription">No prescription uploaded for this order</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="dashboard_pharmacist.php" class="btn btn-back">
                    <span>←</span> Back to Dashboard
                </a>
                <?php if ($order['STATUS'] === 'PENDING'): ?>
                <a href="accept_order.php?order_id=<?php echo htmlspecialchars($order['ORDER_ID']); ?>" class="btn btn-accept" onclick="return confirm('Accept this order and generate invoice?');">
                    <span>✓</span> Accept Order
                </a>
                <?php endif; ?>
            </div>

        </div>

    </div>

</div>

</body>
</html>
