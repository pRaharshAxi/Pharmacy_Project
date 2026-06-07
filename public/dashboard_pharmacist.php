<?php
session_start();
require_once '../config/db_connect.php';

/* ===== PHARMACIST LOGIN CHECK ===== */
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: login.php");
    exit();
}

/* ===== PHARMACIST INFO ===== */
$f_name = $_SESSION['f_name'] ?? 'Pharmacist';
$l_name = $_SESSION['l_name'] ?? '';

/* ===== STATS ===== */

// Total medicines
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM medicine");
$stmt->execute();
$total_medicines = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Low stock medicines
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM medicine WHERE STOCK_QUANTITY < 10");
$stmt->execute();
$low_stock = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Pending orders
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM orders WHERE STATUS='PENDING'");
$stmt->execute();
$pending_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total suppliers
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM supplier");
$stmt->execute();
$total_suppliers = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Fetch ONLY PENDING orders for table (so pharmacists only see orders they can accept)
$stmt = $conn->prepare("
    SELECT o.ORDER_ID, o.CUSTOMER_ID, u.F_NAME, u.L_NAME, o.TOTAL_AMOUNT, o.STATUS, o.ORDER_DATE
    FROM orders o
    JOIN users u ON o.CUSTOMER_ID = u.USER_ID
    WHERE o.STATUS = 'PENDING'
    ORDER BY o.ORDER_DATE DESC
    LIMIT 10
");
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pharmacist Dashboard - MedCare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../css/dashboard.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Additional styles for alerts */
.alert {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 6px;
    font-weight: 500;
}

.alert-success {
    background-color: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.alert-error {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

.alert-close {
    float: right;
    cursor: pointer;
    font-weight: bold;
    color: inherit;
    opacity: 0.7;
}

.alert-close:hover {
    opacity: 1;
}

/* Button styles */
.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-action {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-view {
    background: #3b82f6;
    color: white;
}

.btn-view:hover {
    background: #2563eb;
}

.btn-accept {
    background: #10b981;
    color: white;
}

.btn-accept:hover {
    background: #059669;
    transform: translateY(-1px);
}

.btn-icon {
    font-size: 14px;
}

/* Update table cell alignment */
.data-table td:last-child {
    text-align: center;
}
</style>
</head>

<body>

<!-- Sidebar -->
<div class="sidebar" style="background: linear-gradient(180deg, #181c2a 0%, #111827 100%); color: #fff; min-width: 290px; width: 290px; display: flex; flex-direction: column; align-items: stretch; justify-content: space-between; height: 100vh; position: fixed; left: 0; top: 0; box-shadow: 4px 0 12px rgba(0,0,0,0.10); overflow: hidden;">
    <div>
        <div class="sidebar-logo" style="padding: 0 24px; margin-bottom: 32px; font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #fff;">MedCare</div>
        <nav class="sidebar-nav" style="display: flex; flex-direction: column; gap: 2px;">
            <a href="dashboard_pharmacist.php" class="active" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 14px; background: #b7aaff; color: #23232a; font-weight: 600; margin-bottom: 2px; margin-left: 10; margin-right: -8px; box-shadow: 0 6px 24px 0 rgba(183,170,255,0.25), 0 1.5px 8px 0 rgba(0,0,0,0.10); position: relative; z-index: 2;">Dashboard</a>
            <a href="view_suppliers.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Suppliers</a>
            <a href="view_orders.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Orders</a>
            <a href="view_low_stock.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Low Stock</a>
        </nav>
    </div>
    <div style="padding: 0 24px 24px 24px;">
        <div style="display: flex; align-items: center; gap: 10px; background: #23263a; border-radius: 10px; padding: 10px 14px;">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: #374151; display: flex; align-items: center; justify-content: center; font-size: 1.1em; font-weight: 700; color: #fff;">P</div>
            <div style="flex:1; min-width:0;">
                <div style="font-size: 1em; font-weight: 600; color: #fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?php echo htmlspecialchars($f_name . ' ' . $l_name); ?>
                </div>
                <div style="font-size: 0.85em; color: #cbd5e1;">Pharmacist</div>
            </div>
            <a href="logout.php" style="color: #cbd5e1; font-size: 1.2em; text-decoration: none; margin-left: 8px;">⎋</a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div style="margin-left: 290px;" class="main-content">

    <!-- Header Bar -->
    <div class="header-bar" style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-bottom: 1px solid #e5e7eb; margin-bottom: 0;">
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

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <span class="alert-close" onclick="this.parentElement.style.display='none';">&times;</span>
            <?php 
            echo htmlspecialchars($_SESSION['success']); 
            unset($_SESSION['success']);
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <span class="alert-close" onclick="this.parentElement.style.display='none';">&times;</span>
            <?php 
            echo htmlspecialchars($_SESSION['error']); 
            unset($_SESSION['error']);
            ?>
        </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card medicines">
                <div class="stat-icon" style="margin-bottom: 10px;">
                    <!-- Medicines Icon -->
                    <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#8B5CF6;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.341A8 8 0 118.659 4.572m10.769 10.769A8 8 0 018.659 4.572m10.769 10.769L8.659 4.572"/></svg>
                </div>
                <div class="stat-label">Total Medicines</div>
                <div class="stat-number"><?php echo $total_medicines; ?></div>
                <div class="stat-change">↑ 12%</div>
            </div>

            <a href="view_low_stock.php" style="text-decoration: none; color: inherit; cursor: pointer;">
                <div class="stat-card low-stock" style="transition: all 0.3s; cursor: pointer;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';">
                    <div class="stat-icon" style="margin-bottom: 10px;">
                        <!-- Low Stock Icon -->
                        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#F59E0B;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="stat-label">Low Stock</div>
                    <div class="stat-number"><?php echo $low_stock; ?></div>
                    <div class="stat-change negative">⚠ Attention needed</div>
                </div>
            </a>

            <div class="stat-card pending">
                <div class="stat-icon" style="margin-bottom: 10px;">
                    <!-- Pending Orders Icon -->
                    <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#3B82F6;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-7a4 4 0 11-8 0 4 4 0 018 0zm6 10v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2a6 6 0 0112 0z"/></svg>
                </div>
                <div class="stat-label">Pending Orders</div>
                <div class="stat-number"><?php echo $pending_orders; ?></div>
                <div class="stat-change negative">⏳ Awaiting action</div>
            </div>

            <div class="stat-card suppliers">
                <div class="stat-icon" style="margin-bottom: 10px;">
                    <!-- Suppliers Icon -->
                    <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#10B981;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M16 3v4M8 3v4m-5 4h18"/></svg>
                </div>
                <div class="stat-label">Total Suppliers</div>
                <div class="stat-number"><?php echo $total_suppliers; ?></div>
                <div class="stat-change">↑ 8%</div>
            </div>
        </div>

        <!-- Orders Table Section -->
        <div class="table-header">
            <h2>Pending Orders (Awaiting Acceptance)</h2>
            <div class="filters">
                <input type="text" class="search-box" placeholder="Search orders...">
            </div>
        </div>

        <!-- Data Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($orders && $orders->num_rows > 0) {
                    while ($row = $orders->fetch_assoc()) {
                        $initials = substr($row['F_NAME'], 0, 1) . substr($row['L_NAME'], 0, 1);
                        echo "
                        <tr>
                            <td><strong>#" . htmlspecialchars($row['ORDER_ID']) . "</strong></td>
                            <td>
                                <div class='user-cell'>
                                    <div class='user-avatar'>$initials</div>
                                    <div>
                                        <div>" . htmlspecialchars($row['F_NAME'] . ' ' . $row['L_NAME']) . "</div>
                                        <div style='font-size:0.85rem; color:#9CA3AF;'>ID: " . htmlspecialchars($row['CUSTOMER_ID']) . "</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class='status pending'>" . htmlspecialchars($row['STATUS']) . "</span></td>
                            <td>" . date('M d, Y', strtotime($row['ORDER_DATE'])) . "</td>
                            <td><strong>Rs. " . number_format($row['TOTAL_AMOUNT'], 2) . "</strong></td>
                            <td>
                                <div class='action-buttons'>
                                    <a href='view_order_details.php?order_id=" . htmlspecialchars($row['ORDER_ID']) . "' class='btn-action btn-view'>
                                        <span class='btn-icon'>📋</span> View
                                    </a>
                                    <a href='accept_order.php?order_id=" . htmlspecialchars($row['ORDER_ID']) . "' class='btn-action btn-accept' onclick='return confirm(\"Are you sure you want to accept this order? An invoice will be generated automatically.\");'>
                                        <span class='btn-icon'>✓</span> Accept
                                    </a>
                                </div>
                            </td>
                        </tr>
                        ";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; padding:32px;'>No pending orders found</td></tr>";
                }
                ?>
            </tbody>
        </table>

    </div>

</div>

<script>
// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Search functionality
document.querySelector('.search-box').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

</body>
</html>