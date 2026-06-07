<?php
session_start();
require_once "../config/db_connect.php";

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'CUSTOMER') {
    header("Location: login.php");
    exit();
}

$f_name = $_SESSION['f_name'] ?? 'Customer';
$l_name = $_SESSION['l_name'] ?? '';

// Fetch all available medicines with stock
$sql = "SELECT MEDICINE_ID, NAME, CATEGORY, PRICE, STOCK_QUANTITY, DOSAGE, EXP_DURATION 
        FROM medicine
        WHERE STOCK_QUANTITY > 0
        ORDER BY NAME ASC";
$result = $conn->query($sql);

// Get cart items count and total price
$cart_items = array();
$cart_count = 0;
$total_price = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $medicine_id => $item) {
        // Handle both old and new cart formats
        if (is_array($item)) {
            $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
            $price = isset($item['price']) ? (float)$item['price'] : 0;
            $name = isset($item['name']) ? $item['name'] : '';
            
            $cart_items[$medicine_id] = array(
                'name' => $name,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $price * $quantity
            );
            $cart_count += $quantity;
            $total_price += ($price * $quantity);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Medicines - MedCare</title>
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

        /* Header Styles */
        header {
            background: linear-gradient(120deg, #1d3557, #457b9d);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        header h2 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        nav {
            display: flex;
            gap: 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 500;
        }

        nav a:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .page-header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        /* Success/Error Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

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

        /* Medicine Grid */
        .medicines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .medicine-card {
            background: white;
            border-radius: 12px;
            padding: 14px 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.10);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .medicine-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .medicine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        .medicine-header {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .medicine-header-left {
            flex: 1;
        }

        .medicine-info-icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s;
            padding: 0;
            flex-shrink: 0;
            margin-left: 8px;
        }

        .medicine-info-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .medicine-name {
            font-size: 0.95rem;
            color: #1d3557;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .medicine-category {
            display: inline-block;
            padding: 3px 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 14px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .medicine-details {
            margin: 8px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 0.85rem;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
        }

        .price {
            color: #27ae60;
            font-size: 1rem;
            font-weight: bold;
        }

        .stock-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .stock-high {
            background: #d4edda;
            color: #155724;
        }

        .stock-medium {
            background: #fff3cd;
            color: #856404;
        }

        .stock-low {
            background: #f8d7da;
            color: #721c24;
        }

        /* Add to Cart Form */
        .cart-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 10px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .quantity-control label {
            font-weight: 600;
            color: #333;
            font-size: 0.85rem;
        }

        .quantity-control input {
            width: 60px;
            padding: 6px 8px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 0.85rem;
            text-align: center;
            transition: border-color 0.3s;
        }

        .quantity-control input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .btn-view {
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            color: white;
            margin-top: 10px;
        }

        .btn-view:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(33, 147, 176, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .empty-state h3 {
            color: #666;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #999;
        }

        /* Cart Badge */
        .cart-link {
            position: relative;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }

        /* Cart Sidebar */
        .cart-sidebar {
            position: fixed;
            right: 0;
            top: 0;
            width: 350px;
            height: 100vh;
            background: white;
            box-shadow: -4px 0 12px rgba(0,0,0,0.15);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #f0f0f0;
        }

        .cart-sidebar-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .cart-sidebar-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }

        .cart-close-btn {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px 10px;
        }

        .cart-items-container {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }

        .cart-item {
            background: #f9f9f9;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .cart-item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            font-size: 0.95rem;
        }

        .cart-item-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .cart-item-qty {
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            display: inline-block;
            font-weight: 600;
            color: #667eea;
        }

        .cart-item-price {
            color: #27ae60;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .cart-item-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-remove {
            background: #f0f0f0;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            color: #e74c3c;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-remove:hover {
            background: #e74c3c;
            color: white;
        }

        .cart-empty-message {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-size: 0.95rem;
        }

        .cart-summary {
            padding: 20px;
            border-top: 2px solid #f0f0f0;
            background: #fafafa;
            flex-shrink: 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .summary-row.total {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1d3557;
            padding-top: 12px;
            border-top: 2px solid #e0e0e0;
            margin-top: 12px;
        }

        .summary-row.total span:last-child {
            color: #27ae60;
        }

        .cart-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
        }

        .btn-checkout {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-checkout:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-continue {
            flex: 1;
            padding: 12px;
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-continue:hover {
            background: #e0e0e0;
        }

        /* Main Content Adjustment */
        .main-content {
            margin-right: 370px;
        }

        .container {
            padding-right: 0;
        }

        @media (max-width: 1024px) {
            .cart-sidebar {
                width: 300px;
            }

            .main-content {
                margin-right: 320px;
            }
        }

        @media (max-width: 768px) {
            .cart-sidebar {
                display: none;
            }

            .main-content {
                margin-right: 0;
                margin-left: 0;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .medicines-grid {
                grid-template-columns: 1fr;
            }

            header {
                flex-direction: column;
                gap: 15px;
            }

            nav {
                flex-wrap: wrap;
                justify-content: center;
            }
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
            <a href="view_medicines.php" class="active" style="display: flex; align-items: center; gap: 12px; padding: 13px 28px; border-radius: 14px; background: #b7aaff; color: #23232a; font-weight: 600; margin-bottom: 2px; margin-left: 10px; margin-right: -8px; box-shadow: 0 6px 24px 0 rgba(183,170,255,0.25), 0 1.5px 8px 0 rgba(0,0,0,0.10); position: relative; z-index: 2;">View Medicines</a>
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
    <div class="header-bar">
        <h1>Available Medicines</h1>
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
        <div class="medicines-grid">
            <?php while($medicine = $result->fetch_assoc()): ?>
                <div class="medicine-card">
                    <div class="medicine-header">
                        <div class="medicine-header-left">
                            <h3 class="medicine-name"><?php echo htmlspecialchars($medicine['NAME']); ?></h3>
                            <span class="medicine-category"><?php echo htmlspecialchars($medicine['CATEGORY']); ?></span>
                        </div>
                        <a href="medicine_details.php?id=<?php echo $medicine['MEDICINE_ID']; ?>" class="medicine-info-icon" title="View Details">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                        </a>
                    </div>

                    <div class="medicine-details">
                        <div class="detail-row">
                            <span class="detail-label">Price:</span>
                            <span class="detail-value price">Rs. <?php echo number_format($medicine['PRICE'], 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Dosage:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($medicine['DOSAGE']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Expiry:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($medicine['EXP_DURATION']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Stock:</span>
                            <span class="detail-value">
                                <?php 
                                $stock = $medicine['STOCK_QUANTITY'];
                                if ($stock > 50) {
                                    echo '<span class="stock-badge stock-high">' . $stock . ' units</span>';
                                } elseif ($stock > 20) {
                                    echo '<span class="stock-badge stock-medium">' . $stock . ' units</span>';
                                } else {
                                    echo '<span class="stock-badge stock-low">' . $stock . ' units</span>';
                                }
                                ?>
                            </span>
                        </div>
                    </div>

                    <form action="add_to_cart.php" method="POST" class="cart-form" onsubmit="handleAddToCart(event);">
                        <input type="hidden" name="medicine_id" value="<?php echo htmlspecialchars($medicine['MEDICINE_ID']); ?>">
                        <input type="hidden" name="medicine_name" value="<?php echo htmlspecialchars($medicine['NAME']); ?>">
                        <input type="hidden" name="medicine_price" value="<?php echo htmlspecialchars($medicine['PRICE']); ?>">
                        
                        <div class="quantity-control">
                            <label for="qty_<?php echo $medicine['MEDICINE_ID']; ?>">Quantity:</label>
                            <input 
                                type="number" 
                                id="qty_<?php echo $medicine['MEDICINE_ID']; ?>" 
                                name="quantity" 
                                value="1" 
                                min="1" 
                                max="<?php echo $medicine['STOCK_QUANTITY']; ?>"
                                required
                                style="width: 60px; margin-left: 8px;"
                            >
                        </div>

                        <button type="submit" name="add_to_cart" class="btn btn-primary" title="Add to Cart">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>No Medicines Available</h3>
            <p>There are currently no medicines in stock. Please check back later.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Cart Sidebar -->
<div class="cart-sidebar">
    <div class="cart-sidebar-header">
        <h3>🛒 My cart</h3>
        <button class="cart-close-btn" onclick="closeCart()">×</button>
    </div>

    <div class="cart-items-container" id="cartItemsContainer">
        <?php if (count($cart_items) > 0): ?>
            <?php foreach ($cart_items as $medicine_id => $item): ?>
                <div class="cart-item" id="cart-item-<?php echo $medicine_id; ?>">
                    <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="cart-item-details">
                        <span class="cart-item-qty"><?php echo $item['quantity']; ?> x</span>
                        <span class="cart-item-price">Rs. <?php echo number_format($item['price'], 2); ?></span>
                    </div>
                    <div style="text-align: right; font-size: 0.9rem; color: #666; margin-bottom: 8px;">
                        Subtotal: <strong style="color: #27ae60;">Rs. <?php echo number_format($item['subtotal'], 2); ?></strong>
                    </div>
                    <div class="cart-item-actions">
                        <form action="update_cart.php" method="POST" style="display: inline;">
                            <input type="hidden" name="medicine_id" value="<?php echo $medicine_id; ?>">
                            <input type="hidden" name="quantity" value="<?php echo $item['quantity'] - 1; ?>">
                            <button type="submit" class="btn-remove">Remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="cart-empty-message">
                <p>Your cart is empty</p>
                <p style="font-size: 2rem; margin: 10px 0;">🛍️</p>
                <p>Add items to get started</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="cart-summary">
        <div class="summary-row">
            <span>Items:</span>
            <span id="cartCount"><?php echo $cart_count; ?></span>
        </div>
        <div class="summary-row">
            <span>Subtotal:</span>
            <span id="cartSubtotal">Rs. <?php echo number_format($total_price, 2); ?></span>
        </div>
        <div class="summary-row">
            <span>Discount:</span>
            <span style="color: #27ae60;">%10</span>
        </div>
        <div class="summary-row total">
            <span>Final</span>
            <span id="cartTotal">Rs. <?php echo number_format($total_price * 0.9, 2); ?></span>
        </div>

        <div class="cart-actions">
            <?php if ($cart_count > 0): ?>
                <a href="place_order.php" class="btn-checkout">Checkout</a>
            <?php else: ?>
                <button class="btn-checkout" disabled style="opacity: 0.5; cursor: not-allowed;">Checkout</button>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>

<script>
function handleAddToCart(event) {
    // Allow form submission to proceed normally
    return true;
}

function closeCart() {
    // This would toggle cart visibility on mobile
    const sidebar = document.querySelector('.cart-sidebar');
    if (sidebar) {
        sidebar.style.display = 'none';
    }
}

// Refresh cart after item is added
function refreshCart() {
    // This can be called via AJAX to update cart without page reload
    location.reload();
}
</script>

<?php $conn->close(); ?>

</body>
</html>