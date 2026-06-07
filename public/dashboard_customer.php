<?php
session_start();
require_once '../config/db_connect.php';

/* ===== CUSTOMER LOGIN CHECK ===== */
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'CUSTOMER') {
    header("Location: login.php");
    exit();
}

/* ===== CUSTOMER INFO ===== */
$user_id = $_SESSION['user_id'];
$f_name = $_SESSION['f_name'] ?? 'Customer';
$l_name = $_SESSION['l_name'] ?? '';

/* ===== STATS ===== */
// Total available medicines
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM medicine WHERE STOCK_QUANTITY > 0");
$stmt->execute();
$total_medicines = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total categories
$stmt = $conn->prepare("SELECT COUNT(DISTINCT CATEGORY) AS total FROM medicine WHERE STOCK_QUANTITY > 0");
$stmt->execute();
$total_categories = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total orders for customer
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM orders WHERE CUSTOMER_ID = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Pending orders for customer
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM orders WHERE CUSTOMER_ID = ? AND STATUS = 'PENDING'");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$pending_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Accepted orders for customer (PROCESSING status means accepted by pharmacist)
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM orders WHERE CUSTOMER_ID = ? AND STATUS = 'PROCESSING'");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$accepted_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Get last order date
$stmt = $conn->prepare("SELECT MAX(ORDER_DATE) AS last_order FROM orders WHERE CUSTOMER_ID = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$last_order = $stmt->get_result()->fetch_assoc()['last_order'] ?? null;
$stmt->close();

// Get total money spent
$stmt = $conn->prepare("SELECT SUM(TOTAL_AMOUNT) AS total_spent FROM orders WHERE CUSTOMER_ID = ? AND STATUS IN ('COMPLETED', 'PROCESSING')");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$total_spent = $stmt->get_result()->fetch_assoc()['total_spent'] ?? 0;
$stmt->close();

// Fetch recent orders for table (limit 10)
$stmt = $conn->prepare("SELECT ORDER_ID, TOTAL_AMOUNT, STATUS, ORDER_DATE FROM orders WHERE CUSTOMER_ID = ? ORDER BY ORDER_DATE DESC LIMIT 10");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result();
$stmt->close();

// Function to display customer-friendly status
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
<title>Customer Dashboard - MedCare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../css/dashboard.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Additional status styles */
.status.accepted {
    background-color: #dbeafe;
    color: #1e40af;
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: 600;
    text-transform: uppercase;
}

.stat-card.accepted {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}

.stat-card.accepted .stat-label,
.stat-card.accepted .stat-number,
.stat-card.accepted .stat-change {
    color: white;
}

/* Success message styles */
.alert {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 6px;
    font-weight: 500;
}

.alert-info {
    background-color: #dbeafe;
    color: #1e40af;
    border: 1px solid #3b82f6;
}
</style>
</head>

<body>

<!-- Sidebar -->
<div class="sidebar" style="background: linear-gradient(180deg, #181c2a 0%, #111827 100%); color: #fff; min-width: 290px; width: 290px; display: flex; flex-direction: column; align-items: stretch; justify-content: space-between; height: 100vh; position: fixed; left: 0; top: 0; box-shadow: 4px 0 12px rgba(0,0,0,0.10); overflow: hidden;">
    <div>
        <div class="sidebar-logo" style="padding: 0 24px; margin-bottom: 32px; font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #fff;">MedCare</div>
        <nav class="sidebar-nav" style="display: flex; flex-direction: column; gap: 2px;">
            <a href="dashboard_customer.php" class="active" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 14px; background: #b7aaff; color: #23232a; font-weight: 600; margin-bottom: 2px; margin-left: 10px; margin-right: -8px; box-shadow: 0 6px 24px 0 rgba(183,170,255,0.25), 0 1.5px 8px 0 rgba(0,0,0,0.10); position: relative; z-index: 2;">Dashboard</a>
            <a href="view_medicines.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">View Medicines</a>
            <a href="search_medicine.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Search</a>
            <a href="my_order.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">My Orders</a>
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
    <div class="header-bar" style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-bottom: 1px solid #e5e7eb;">
        <h1 style="color: #111827; font-size: 1.6rem; font-weight: 700; margin: 0;">Dashboard</h1>
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

        <?php if ($accepted_orders > 0): ?>
        <div class="alert alert-info">
            🎉 Great news! You have <?php echo $accepted_orders; ?> order(s) that have been accepted by our pharmacist and are being processed.
        </div>
        <?php endif; ?>

        <!-- Profile Section -->
        <div style="background: white; border-radius: 12px; padding: 24px; margin-bottom: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: flex-start; gap: 24;">
                <!-- Profile Picture -->
                <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 2rem; flex-shrink: 0;">
                    <?php echo strtoupper(substr($f_name, 0, 1) . substr($l_name, 0, 1)); ?>
                </div>
                
                <!-- Profile Info -->
                <div style="flex: 1; margin-left: 32px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <h2 style="margin: 0; font-size: 1.3rem; font-weight: 700; color: #111827;"><?php echo htmlspecialchars($f_name . ' ' . $l_name); ?></h2>
                        <div style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">Active</div>
                    </div>
                    <div style="color: #6B7280; margin-bottom: 16px; font-size: 0.9rem;">Customer · MedCare Pharmacy</div>
                    
                    <!-- Stats -->
                    <div style="display: flex; gap: 32px; flex-wrap: wrap;">
                        <div>
                            <div style="color: #6B7280; font-size: 0.85rem; margin-bottom: 4px;">Member Since</div>
                            <div style="color: #111827; font-weight: 600;">Active Member</div>
                        </div>
                        <div>
                            <div style="color: #6B7280; font-size: 0.85rem; margin-bottom: 4px;">Last Order</div>
                            <div style="color: #111827; font-weight: 600;"><?php echo $last_order ? date('d M Y', strtotime($last_order)) : 'No orders yet'; ?></div>
                        </div>
                        <div>
                            <div style="color: #6B7280; font-size: 0.85rem; margin-bottom: 4px;">Total Orders</div>
                            <div style="color: #111827; font-weight: 600;"><?php echo $total_orders; ?> order(s)</div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; gap: 8px; flex-direction: column;">
                    <a href="my_order.php" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; background: #3B82F6; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#2563EB';" onmouseout="this.style.background='#3B82F6';">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        My Orders
                    </a>
                    <a href="view_medicines.php" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; background: #10B981; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#059669';" onmouseout="this.style.background='#10B981';">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v20M2 12h20"></path>
                        </svg>
                        Browse
                    </a>
                </div>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="margin-bottom: 12px; color: #3B82F6;">
                    <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 5H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2z"></path>
                        <polyline points="3 7 12 13 21 7"></polyline>
                    </svg>
                </div>
                <div class="stat-label">Available Medicines</div>
                <div class="stat-number"><?php echo $total_medicines; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="margin-bottom: 12px; color: #10B981;">
                    <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div class="stat-label">Total Spent</div>
                <div class="stat-number">Rs. <?php echo number_format($total_spent, 2); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="margin-bottom: 12px; color: #EF4444;">
                    <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-number"><?php echo $total_orders; ?></div>
            </div>

            <div class="stat-card pending">
                <div class="stat-icon" style="margin-bottom: 12px; color: #FCD34D;">
                    <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="stat-label">Pending Orders</div>
                <div class="stat-number"><?php echo $pending_orders; ?></div>
            </div>

            <?php if ($accepted_orders > 0): ?>
            <div class="stat-card accepted">
                <div class="stat-label">✓ Accepted Orders</div>
                <div class="stat-number"><?php echo $accepted_orders; ?></div>
                <div class="stat-change">Being Processed</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Orders Table Section -->
        <div class="table-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h2>Recent Orders</h2>
            <div class="filters">
                <button class="filter-btn">All time ✕</button>
                <button class="filter-btn">Status ✕</button>
                <input type="text" class="search-box" placeholder="Search orders...">
            </div>
        </div>

        <!-- Data Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($recent_orders && $recent_orders->num_rows > 0) {
                    while ($row = $recent_orders->fetch_assoc()) {
                        $customerStatus = getCustomerStatus($row['STATUS']);
                        $statusClass = strtolower($customerStatus);
                        
                        echo "<tr>";
                        echo "<td><strong>#" . htmlspecialchars($row['ORDER_ID']) . "</strong></td>";
                        echo "<td><span class='status " . htmlspecialchars($statusClass) . "'>" . htmlspecialchars($customerStatus) . "</span></td>";
                        echo "<td>" . date('M d, Y', strtotime($row['ORDER_DATE'])) . "</td>";
                        echo "<td><strong>Rs. " . number_format($row['TOTAL_AMOUNT'], 2) . "</strong></td>";
                        echo "<td><a href='order_details.php?id=" . htmlspecialchars($row['ORDER_ID']) . "' style='color: #3b82f6; text-decoration: none; font-weight: 500;'>View Details →</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding:32px;'>No orders found</td></tr>";
                }
                ?>
            </tbody>
        </table>

    </div>

</div>

</body>
</html>

<?php
$conn->close();
?>