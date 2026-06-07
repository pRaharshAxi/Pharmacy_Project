<?php
session_start();
require_once '../config/db_connect.php';

/* PHARMACIST LOGIN CHECK */
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: ../login.php");
    exit();
}

$f_name = $_SESSION['f_name'] ?? 'Pharmacist';
$l_name = $_SESSION['l_name'] ?? '';

/* Fetch suppliers */
$sql = "SELECT SUPPLIER_ID, COMPANY_NAME, EMAIL, STREET, CITY, STATE, ZIP_CODE, COUNTRY 
        FROM supplier 
        ORDER BY COMPANY_NAME";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Suppliers | MedCare</title>
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

.supplier-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

.supplier-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid #e5e7eb;
    transition: all 0.3s;
}

.supplier-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-color: #3B82F6;
}

.supplier-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 16px;
}

.supplier-info {
    margin-bottom: 16px;
}

.info-row {
    display: flex;
    align-items: flex-start;
    padding: 8px 0;
    gap: 12px;
    font-size: 0.9rem;
    color: #6B7280;
    line-height: 1.4;
}

.info-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #3B82F6;
}

.supplier-id {
    display: inline-block;
    padding: 6px 12px;
    background: #f0f4f8;
    border-radius: 20px;
    font-size: 0.8rem;
    color: #4B5563;
    margin-bottom: 20px;
}

.btn-purchase {
    width: 100%;
    padding: 10px;
    background: #3B82F6;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: block;
    text-align: center;
    transition: 0.2s;
}

.btn-purchase:hover {
    background: #2563EB;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
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
</style>
</head>

<body>

<!-- Sidebar -->
<div class="sidebar" style="background: linear-gradient(180deg, #181c2a 0%, #111827 100%); color: #fff; min-width: 290px; width: 290px; display: flex; flex-direction: column; align-items: stretch; justify-content: space-between; height: 100vh; position: fixed; left: 0; top: 0; box-shadow: 4px 0 12px rgba(0,0,0,0.10); overflow: hidden;">
    <div>
        <div class="sidebar-logo" style="padding: 0 24px; margin-bottom: 32px; font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #fff;">MedCare</div>
        <nav class="sidebar-nav" style="display: flex; flex-direction: column; gap: 2px;">
            <a href="dashboard_pharmacist.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500;">Dashboard</a>
            <a href="view_suppliers.php" class="active" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 14px; background: #b7aaff; color: #23232a; font-weight: 600; margin-bottom: 2px; margin-left: 10; margin-right: -8px; box-shadow: 0 6px 24px 0 rgba(183,170,255,0.25), 0 1.5px 8px 0 rgba(0,0,0,0.10); position: relative; z-index: 2;">Suppliers</a>
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
<div class="main-content">

    <!-- Header Bar -->
    <div class="header-bar">
        <h1>Suppliers</h1>
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
            <div class="supplier-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="supplier-card">
                        <h3><?= htmlspecialchars($row['COMPANY_NAME']) ?></h3>
                        
                        <div class="supplier-info">
                            <div class="info-row">
                                <div class="info-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                        <path d="m22 7-10 5L2 7"></path>
                                    </svg>
                                </div>
                                <span><?= htmlspecialchars($row['EMAIL']) ?></span>
                            </div>
                            
                            <div class="info-row">
                                <div class="info-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </div>
                                <span>
                                    <?= htmlspecialchars($row['STREET'] . ', ' . $row['CITY']) ?><br>
                                    <?= htmlspecialchars($row['STATE'] . ', ' . $row['ZIP_CODE']) ?><br>
                                    <?= htmlspecialchars($row['COUNTRY']) ?>
                                </span>
                            </div>
                        </div>

                        <span class="supplier-id">ID: <?= htmlspecialchars($row['SUPPLIER_ID']) ?></span>

                        <a href="create_purchase.php?supplier_id=<?= urlencode($row['SUPPLIER_ID']) ?>" class="btn-purchase">
                            Purchase from this Supplier
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No Suppliers Found</h3>
                <p>Add suppliers to start purchasing medicine stocks.</p>
            </div>
        <?php endif; ?>

    </div>

</div>

</body>
</html>

<?php $conn->close(); ?>