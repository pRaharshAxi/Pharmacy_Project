<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get medicine ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_medicines.php");
    exit();
}

$medicine_id = $_GET['id'];

// Fetch medicine details
$sql = "SELECT * FROM medicine WHERE medicine_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $medicine_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: view_medicines.php");
    exit();
}

$medicine = $result->fetch_assoc();

$f_name = $_SESSION['f_name'] ?? 'Customer';
$l_name = $_SESSION['l_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($medicine['NAME']); ?> - Medicine Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f3f4f6;
        }

        /* ===== DETAILS CARD ===== */
        .details-container {
            max-width: 820px;
            margin: 0 auto;
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .medicine-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 32px 36px;
            color: white;
        }

        .medicine-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }

        .medicine-header .category-badge {
            display: inline-block;
            background: rgba(255,255,255,0.22);
            color: white;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .details-body {
            padding: 32px 36px;
        }

        .detail-row {
            display: flex;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-row:last-of-type {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            width: 180px;
            color: #6b7280;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .detail-value {
            flex: 1;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .price-highlight {
            font-size: 1.8rem;
            color: #10b981;
            font-weight: 700;
        }

        .stock-status {
            padding: 5px 14px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .in-stock {
            background-color: #dcfce7;
            color: #166534;
        }

        .low-stock {
            background-color: #fef9c3;
            color: #854d0e;
        }

        .out-of-stock {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .action-buttons {
            margin-top: 28px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.92rem;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            transition: all 0.2s;
        }

        .btn-order {
            background: #10b981;
            color: white;
        }

        .btn-order:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }

        .btn-back {
            background: #f1f5f9;
            color: #374151;
        }

        .btn-back:hover {
            background: #e2e8f0;
        }

        /* ===== LAYOUT ===== */
        .main-content {
            margin-left: 290px;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-bottom: 1px solid #e5e7eb;
        }

        .header-bar h1 {
            color: #111827;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .content {
            padding: 32px 40px;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 24px;
        }

        .breadcrumb a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb span {
            color: #9ca3af;
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR (matches dashboard_customer.php exactly) ===== -->
<div class="sidebar" style="background: linear-gradient(180deg, #181c2a 0%, #111827 100%); color: #fff; min-width: 290px; width: 290px; display: flex; flex-direction: column; align-items: stretch; justify-content: space-between; height: 100vh; position: fixed; left: 0; top: 0; box-shadow: 4px 0 12px rgba(0,0,0,0.10); overflow: hidden;">
    <div>
        <div class="sidebar-logo" style="padding: 32px 24px 24px 24px; font-size: 1.4rem; font-weight: 700; letter-spacing: -0.5px; color: #fff;">MedCare</div>
        <nav class="sidebar-nav" style="display: flex; flex-direction: column; gap: 2px;">
            <a href="dashboard_customer.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500; text-decoration: none;">Dashboard</a>
            <a href="view_medicines.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 14px; background: #b7aaff; color: #23232a; font-weight: 600; margin-bottom: 2px; margin-left: 10px; margin-right: -8px; box-shadow: 0 6px 24px 0 rgba(183,170,255,0.25), 0 1.5px 8px 0 rgba(0,0,0,0.10); position: relative; z-index: 2; text-decoration: none;">View Medicines</a>
            <a href="search_medicine.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500; text-decoration: none;">Search</a>
            <a href="my_order.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #cbd5e1; font-weight: 500; text-decoration: none;">My Orders</a>
            <div style="height:1px; background:#ef4444; width:80%; margin:12px auto; border-radius:1px;"></div>
            <a href="logout.php" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 12px; color: #ef4444; font-weight: 600; text-decoration: none;">Logout</a>
        </nav>
    </div>
    <div style="padding: 0 24px 24px 24px;">
        <div style="display: flex; align-items: center; gap: 10px; background: #23263a; border-radius: 10px; padding: 10px 14px;">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: #374151; display: flex; align-items: center; justify-content: center; font-size: 1.1em; font-weight: 700; color: #fff; flex-shrink: 0;">
                <?php echo strtoupper(substr($f_name, 0, 1)); ?>
            </div>
            <div style="flex:1; min-width:0;">
                <div style="font-size: 1em; font-weight: 600; color: #fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($f_name . ' ' . $l_name); ?></div>
                <div style="font-size: 0.85em; color: #cbd5e1;">Customer</div>
            </div>
            <a href="logout.php" style="color: #cbd5e1; font-size: 1.2em; text-decoration: none; margin-left: 8px;">⎋</a>
        </div>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">

    <!-- Header Bar -->
    <div class="header-bar">
        <h1>Medicine Details</h1>
        <a href="logout.php" style="display: flex; align-items: center; gap: 8px; color: white; background: #3B82F6; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 6px; font-size: 0.9rem; transition: all 0.3s;" onmouseover="this.style.background='#2563EB';" onmouseout="this.style.background='#3B82F6';">
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

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard_customer.php">Dashboard</a>
            <span>›</span>
            <a href="view_medicines.php">View Medicines</a>
            <span>›</span>
            <span><?php echo htmlspecialchars($medicine['NAME']); ?></span>
        </div>

        <!-- Medicine Details Card -->
        <div class="details-container">

            <!-- Gradient Header -->
            <div class="medicine-header">
                <h1><?php echo htmlspecialchars($medicine['NAME']); ?></h1>
                <span class="category-badge">
                    <?php echo htmlspecialchars($medicine['CATEGORY']); ?>
                </span>
            </div>

            <!-- Detail Rows -->
            <div class="details-body">

                <div class="detail-row">
                    <div class="detail-label">Medicine ID</div>
                    <div class="detail-value"><?php echo htmlspecialchars($medicine['MEDICINE_ID']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Price</div>
                    <div class="detail-value">
                        <span class="price-highlight">Rs.<?php echo number_format($medicine['Price'], 2); ?></span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Dosage</div>
                    <div class="detail-value"><?php echo htmlspecialchars($medicine['DOSAGE']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Expiry Date</div>
                    <div class="detail-value"><?php echo htmlspecialchars($medicine['EXP_DURATION']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Stock Status</div>
                    <div class="detail-value">
                        <?php
                        $stock = $medicine['STOCK_QUANTITY'];
                        if ($stock > 50) {
                            echo '<span class="stock-status in-stock">In Stock (' . $stock . ' units)</span>';
                        } elseif ($stock > 0) {
                            echo '<span class="stock-status low-stock">Low Stock (' . $stock . ' units)</span>';
                        } else {
                            echo '<span class="stock-status out-of-stock">Out of Stock</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <?php if ($medicine['STOCK_QUANTITY'] > 0 && isset($_SESSION['role']) && strtoupper($_SESSION['role']) === 'CUSTOMER'): ?>
                        <a href="place_order.php?medicine_id=<?php echo htmlspecialchars($medicine['MEDICINE_ID']); ?>" class="btn btn-order">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            Order Now
                        </a>
                    <?php endif; ?>

                    <a href="view_medicines.php" class="btn btn-back">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                        Back to Medicines
                    </a>
                </div>

            </div><!-- /details-body -->
        </div><!-- /details-container -->

    </div><!-- /content -->
</div><!-- /main-content -->

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>