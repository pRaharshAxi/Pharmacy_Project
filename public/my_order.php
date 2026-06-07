<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'CUSTOMER') {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$f_name = $_SESSION['f_name'] ?? 'Customer';
$l_name = $_SESSION['l_name'] ?? '';

// Fetch orders for this specific customer
$sql = "SELECT ORDER_ID, ORDER_DATE, TOTAL_AMOUNT, STATUS 
        FROM orders 
        WHERE CUSTOMER_ID = ? 
        ORDER BY ORDER_DATE DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

// Function to convert database status to customer-friendly status
function getCustomerStatus($dbStatus) {
    if ($dbStatus === 'PROCESSING') {
        return 'ACCEPTED';
    }
    return $dbStatus;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Order History - MedCare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            height: 100vh;
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
        
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            color: #1f2937;
            margin: 0;
            font-size: 28px;
        }
        
        .back-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-link:hover {
            color: #2563eb;
        }
        
        .orders-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .orders-table th {
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .orders-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }
        
        .orders-table tbody tr:hover {
            background-color: #f9fafb;
        }
        
        .orders-table td {
            padding: 16px 20px;
            color: #4b5563;
        }
        
        .order-id {
            font-weight: 600;
            color: #1f2937;
        }
        
        /* Status Badges */
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status.completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status.pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status.accepted {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .status.cancel {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .btn-view {
            display: inline-block;
            padding: 8px 16px;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        
        .btn-view:hover {
            background-color: #2563eb;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-state h3 {
            color: #374151;
            margin-bottom: 15px;
        }
        
        .empty-state a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .empty-state a:hover {
            background-color: #2563eb;
        }
        
        .amount {
            font-weight: 600;
            color: #1f2937;
        }

        .status-legend {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .status-legend h4 {
            margin: 0 0 12px 0;
            color: #374151;
            font-size: 14px;
        }

        .legend-items {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" style="background: linear-gradient(180deg, #181c2a 0%, #111827 100%); color: #fff; min-width: 290px; width: 290px; display: flex; flex-direction: column; align-items: stretch; justify-content: space-between; height: 100vh; position: fixed; left: 0; top: 0; box-shadow: 4px 0 12px rgba(0,0,0,0.10); overflow: hidden;">
    <div>
        <div class="sidebar-logo" style="padding: 0 24px; margin-bottom: 32px; font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #fff;">MedCare</div>
        <nav class="sidebar-nav" style="display: flex; flex-direction: column; gap: 2px;">
            <a href="dashboard_customer.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Dashboard</a>
            <a href="view_medicines.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">View Medicines</a>
            <a href="search_medicine.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Search</a>
            <a href="my_order.php" class="active" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 14px; background: #b7aaff; color: #23232a; font-weight: 600; margin-bottom: 2px; margin-left: 10px; margin-right: -8px; box-shadow: 0 6px 24px 0 rgba(183,170,255,0.25), 0 1.5px 8px 0 rgba(0,0,0,0.10); position: relative; z-index: 2;">My Orders</a>
            <div style="height:1px; background:#ef4444; width:80%; margin:12px auto; border-radius:1px;"></div>
            <a href="logout.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #ef4444; font-weight: 600;">Logout</a>
        </nav>
    </div>
    <div style="padding: 0 24px 24px 24px;">
        <div style="display: flex; align-items: center; gap: 10px; background: #23263a; border-radius: 10px; padding: 10px 14px;">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: #374151; display: flex; align-items: center; justify-content: center; font-size: 1.1em; font-weight: 700; color: #fff;">C</div>
            <div style="flex:1; min-width:0;">
                <div style="font-size: 1em; font-weight: 600; color: #fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($f_name . ' ' . $l_name); ?></div>
                <div style="font-size: 0.85em; color: #cbd5e1;">Customer</div>
            </div>
            <a href="logout.php" style="color: #cbd5e1; font-size: 1.2em; text-decoration: none; margin-left: 8px;">⎋</a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div style="margin-left: 290px;" class="main-content">

    <!-- Header Bar -->
    <div class="header-bar">
        <h1>My Orders</h1>
        <a href="logout.php" style="display: flex; align-items: center; gap: 8px; color: white; background: #3B82F6; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 6px; transition: all 0.3s; font-size: 0.9rem;" onmouseover="this.style.background='#2563EB';" onmouseout="this.style.background='#3B82F6';">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
        </a>
    </div>

    <!-- Success Message Alert -->
    <?php if (isset($_SESSION['message'])): ?>
        <div style="margin: 20px 40px 0 40px; padding: 16px 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px; animation: slideDown 0.3s ease-out; <?php echo ($_SESSION['message_type'] === 'success') ? 'background: #d1fae5; border-left: 4px solid #10b981; color: #065f46;' : 'background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b;'; ?>">
            <span style="font-size: 20px;">
                <?php echo ($_SESSION['message_type'] === 'success') ? '✓' : '⚠'; ?>
            </span>
            <span style="flex: 1; font-weight: 500;"><?php echo htmlspecialchars($_SESSION['message']); ?></span>
            <button onclick="this.parentElement.style.display='none';" style="background: none; border: none; cursor: pointer; color: inherit; font-size: 18px; padding: 0;">×</button>
        </div>
        <style>
            @keyframes slideDown {
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
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Content Area -->
    <div class="content">
        <?php if ($result->num_rows > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php
                            $customerStatus = getCustomerStatus($row['STATUS']);
                            $statusClass = strtolower($customerStatus);
                        ?>
                        <tr>
                            <td class="order-id">#<?php echo htmlspecialchars($row['ORDER_ID']); ?></td>
                            <td><?php echo date("M d, Y", strtotime($row['ORDER_DATE'])); ?></td>
                            <td class="amount">Rs. <?php echo number_format($row['TOTAL_AMOUNT'], 2); ?></td>
                            <td>
                                <span class="status <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($customerStatus); ?>
                                </span>
                            </td>
                            <td>
                                <a href="order_details.php?id=<?php echo $row['ORDER_ID']; ?>" class="btn-view">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Status Legend -->
            <div class="status-legend">
                <h4>Order Status Guide:</h4>
                <div class="legend-items">
                    <div class="legend-item">
                        <span class="status pending">PENDING</span>
                        <span>Waiting for pharmacist acceptance</span>
                    </div>
                    <div class="legend-item">
                        <span class="status accepted">ACCEPTED</span>
                        <span>Pharmacist has accepted and is processing your order</span>
                    </div>
                    <div class="legend-item">
                        <span class="status completed">COMPLETED</span>
                        <span>Order has been fulfilled and delivered</span>
                    </div>
                    <div class="legend-item">
                        <span class="status cancel">CANCEL</span>
                        <span>Order has been cancelled</span>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <h3>No Orders Yet</h3>
                <p>You haven't placed any orders yet. Start shopping to see your order history here.</p>
                <a href="view_medicines.php">Browse Medicines</a>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>