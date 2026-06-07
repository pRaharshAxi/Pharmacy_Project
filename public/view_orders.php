<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in and is pharmacist
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: login.php");
    exit();
}

$f_name = $_SESSION['f_name'] ?? 'Pharmacist';
$l_name = $_SESSION['l_name'] ?? '';

// Fetch all orders (not just pending) for pharmacist dashboard
$sql = "SELECT o.ORDER_ID, o.CUSTOMER_ID, u.F_NAME, u.L_NAME, o.TOTAL_AMOUNT, o.STATUS, o.ORDER_DATE
        FROM orders o
        JOIN users u ON o.CUSTOMER_ID = u.USER_ID
        ORDER BY o.ORDER_DATE DESC
        LIMIT 50";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - MedCare</title>
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

    /* Main Content */
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

    .data-table {
        width: 100%;
        background: white;
        border-collapse: collapse;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .data-table thead {
        background: #f9fafb;
        border-bottom: 2px solid #e5e7eb;
    }

    .data-table th {
        padding: 16px 24px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.9rem;
    }

    .data-table td {
        padding: 16px 24px;
        border-bottom: 1px solid #e5e7eb;
        color: #4B5563;
    }

    .data-table tbody tr:hover {
        background: #f9fafb;
    }

    .data-table tbody tr:last-child td {
        border-bottom: none;
    }

    .user-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        width: fit-content;
    }

    .status.PENDING {
        background: #FEF3C7;
        color: #92400E;
    }

    .status.PROCESSING {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .status.COMPLETED {
        background: #DCFCE7;
        color: #166534;
    }

    .status.CANCELLED {
        background: #FECACA;
        color: #991B1B;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .empty-state h3 {
        color: #6B7280;
        font-size: 1.2rem;
        margin-bottom: 8px;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }

    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 16px;
        margin-top: 32px;
    }

    .medicines-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }

    .medicine-card {
        background: white;
        border-radius: 12px;
        padding: 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }

    .medicine-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-color: #EF4444;
    }

    .medicine-card h4 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 8px;
    }

    .medicine-info {
        font-size: 0.85rem;
        color: #6B7280;
        margin-bottom: 4px;
    }

    .stock-low {
        padding: 6px 12px;
        background: #FEE2E2;
        color: #991B1B;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
        margin-top: 8px;
    }

    .nav-tabs {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 24px;
    }

    .nav-tab {
        padding: 12px 24px;
        background: none;
        border: none;
        color: #6B7280;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.95rem;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }

    .nav-tab.active {
        color: #3B82F6;
        border-bottom-color: #3B82F6;
    }

    .nav-tab:hover {
        color: #111827;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }
    </style>
</head>

<body>

<!-- Sidebar -->
<div class="sidebar" style="background: linear-gradient(180deg, #181c2a 0%, #111827 100%); color: #fff; min-width: 290px; width: 290px; display: flex; flex-direction: column; align-items: stretch; justify-content: space-between; height: 100vh; position: fixed; left: 0; top: 0; box-shadow: 4px 0 12px rgba(0,0,0,0.10); overflow: hidden;">
    <div>
        <div class="sidebar-logo" style="padding: 0 24px; margin-bottom: 32px; font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #fff;">MedCare</div>
        <nav class="sidebar-nav" style="display: flex; flex-direction: column; gap: 2px;">
            <a href="dashboard_pharmacist.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Dashboard</a>
            <a href="view_suppliers.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Suppliers</a>
            <a href="view_orders.php" class="active" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 14px; background: #b7aaff; color: #23232a; font-weight: 600; margin-bottom: 2px; margin-left: 10; margin-right: -8px; box-shadow: 0 6px 24px 0 rgba(183,170,255,0.25), 0 1.5px 8px 0 rgba(0,0,0,0.10); position: relative; z-index: 2;">Orders</a>
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
<div class="main-content">

    <!-- Header Bar -->
    <div class="header-bar">
        <h1>Orders</h1>
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

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = $result->fetch_assoc()) {
                        $status_class = strtoupper($row['STATUS']);
                        $initials = substr($row['F_NAME'], 0, 1) . substr($row['L_NAME'], 0, 1);
                        echo "
                        <tr>
                            <td>
                                <div class='user-cell'>
                                    <div class='user-avatar'>$initials</div>
                                    <div>
                                        <div>" . htmlspecialchars($row['F_NAME'] . ' ' . $row['L_NAME']) . "</div>
                                        <div style='font-size:0.85rem; color:#9CA3AF;'>Order #" . $row['ORDER_ID'] . "</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class='status $status_class'>" . htmlspecialchars($row['STATUS']) . "</span></td>
                            <td>" . date('M d, Y', strtotime($row['ORDER_DATE'])) . "</td>
                            <td><strong>Rs. " . number_format($row['TOTAL_AMOUNT'], 2) . "</strong></td>
                        </tr>
                        ";
                    }
                    ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <h3>No Orders Found</h3>
                <p>There are no orders to display.</p>
            </div>
        <?php endif; ?>
        </div>

        <!-- Low Stock Tab -->
        <div id="low-stock" class="tab-content">
            <?php if ($low_stock_result && $low_stock_result->num_rows > 0): ?>
                <div class="medicines-grid">
                    <?php 
                    // Reset result pointer to beginning
                    $low_stock_result->data_seek(0);
                    while ($medicine = $low_stock_result->fetch_assoc()): ?>
                        <div class="medicine-card">
                            <h4><?= htmlspecialchars($medicine['NAME']) ?></h4>
                            <div class="medicine-info">
                                <div>Category: <?= htmlspecialchars($medicine['CATEGORY']) ?></div>
                                <div>Price: Rs. <?= number_format($medicine['Price'], 2) ?></div>
                            </div>
                            <span class="stock-low">Stock: <?= $medicine['STOCK_QUANTITY'] ?> unit(s)</span>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Low Stock Medicines</h3>
                    <p>All medicines have sufficient stock.</p>
                </div>
            <?php endif; ?>
        </div>

    </div></div>

</div>

<script>
function switchTab(tabName) {
    // Hide all tab contents
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => {
        content.classList.remove('active');
    });

    // Remove active class from all tabs
    const tabs = document.querySelectorAll('.nav-tab');
    tabs.forEach(tab => {
        tab.classList.remove('active');
    });

    // Show selected tab content
    document.getElementById(tabName).classList.add('active');

    // Add active class to clicked tab
    event.target.classList.add('active');
}
</script>

</body>
</html>

<?php $conn->close(); ?>
?>