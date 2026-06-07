<?php
session_start();
require_once '../config/db_connect.php';

// Access Control
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - MedCare</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        /* Header */
        header {
            background: linear-gradient(120deg, #1d3557, #457b9d);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            border-radius: 10px;
            margin-bottom: 30px;
        }

        header h2 {
            font-size: 1.8rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 500;
        }

        nav a:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Container */
        .cart-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }

        .cart-header h2 {
            color: #1d3557;
            font-size: 2rem;
        }

        .continue-shopping {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .continue-shopping:hover {
            color: #5568d3;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-cart h3 {
            color: #666;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .empty-cart p {
            color: #999;
            margin-bottom: 30px;
        }

        /* Cart Table */
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .cart-table thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .cart-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 1rem;
        }

        .cart-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }

        .cart-table tbody tr:hover {
            background: #f8f9fa;
        }

        .cart-table td {
            padding: 20px 15px;
            vertical-align: middle;
        }

        .item-name {
            font-weight: 600;
            color: #1d3557;
            font-size: 1.1rem;
        }

        .item-price {
            color: #27ae60;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .quantity-input {
            width: 80px;
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            text-align: center;
            transition: border-color 0.3s;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .subtotal {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            text-align: center;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: scale(1.05);
        }

        .btn-update {
            background: #3498db;
            color: white;
        }

        .btn-update:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 30px;
            font-size: 1.1rem;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Cart Summary */
        .cart-summary {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 1rem;
        }

        .summary-row.total {
            border-top: 3px solid #667eea;
            margin-top: 15px;
            padding-top: 20px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #1d3557;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            gap: 15px;
        }

        .prescription-upload {
            margin-top: 20px;
            padding: 20px;
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
        }

        .prescription-upload label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #856404;
        }

        .prescription-upload input[type="file"] {
            padding: 10px;
            border: 2px solid #ffc107;
            border-radius: 8px;
            width: 100%;
            margin-bottom: 15px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .cart-container {
                padding: 20px;
            }

            .cart-table {
                font-size: 0.9rem;
            }

            .cart-table th,
            .cart-table td {
                padding: 10px 5px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<header>
    <h2>🏥 MedCare</h2>
    <nav>
        <a href="dashboard_customer.php">Dashboard</a>
        <a href="view_medicines.php">Medicines</a>
        <a href="view_cart.php">🛒 Cart</a>
        <a href="customer_orders.php">My Orders</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="cart-container">
    <div class="cart-header">
        <h2>🛒 Shopping Cart</h2>
        <a href="view_medicines.php" class="continue-shopping">
            ← Continue Shopping
        </a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-cart">
            <h3>Your cart is empty! 🛒</h3>
            <p>Browse our medicines and add items to your cart.</p>
            <a href="view_medicines.php" class="btn btn-primary">Browse Medicines</a>
        </div>
    <?php else: ?>
        <form action="update_cart.php" method="POST">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    foreach ($_SESSION['cart'] as $id => $item): 
                        $subtotal = $item['price'] * $item['quantity'];
                        $grand_total += $subtotal;
                    ?>
                    <tr>
                        <td>
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <small style="color: #999;">ID: <?php echo htmlspecialchars($id); ?></small>
                        </td>
                        <td class="item-price">Rs. <?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <input 
                                type="number" 
                                name="qty[<?php echo $id; ?>]" 
                                value="<?php echo $item['quantity']; ?>" 
                                min="1"
                                max="<?php echo $item['stock']; ?>"
                                class="quantity-input"
                            >
                            <small style="display: block; color: #999; margin-top: 5px;">
                                Max: <?php echo $item['stock']; ?>
                            </small>
                        </td>
                        <td class="subtotal">Rs. <?php echo number_format($subtotal, 2); ?></td>
                        <td>
                            <a 
                                href="update_cart.php?action=remove&id=<?php echo $id; ?>" 
                                class="btn btn-danger" 
                                onclick="return confirm('Remove <?php echo htmlspecialchars($item['name']); ?> from cart?');"
                            >
                                🗑️ Remove
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-summary">
                <div class="summary-row">
                    <span>Items in Cart:</span>
                    <span><?php echo count($_SESSION['cart']); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Grand Total:</span>
                    <span>Rs. <?php echo number_format($grand_total, 2); ?></span>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" name="update_cart" class="btn btn-update">
                    🔄 Update Quantities
                </button>
            </div>
        </form>

        <!-- Separate form for prescription upload and placing order -->
        <form action="place_order.php" method="POST" enctype="multipart/form-data">
            <div class="prescription-upload">
                <label for="prescription">📋 Upload Prescription (Required)</label>
                <small style="display: block; margin-bottom: 10px; color: #856404;">
                    Accepted formats: JPG, PNG, PDF | Maximum size: 5MB
                </small>
                <input 
                    type="file" 
                    name="prescription" 
                    id="prescription" 
                    accept="image/jpeg,image/png,application/pdf" 
                    required
                >
                <button type="submit" name="place_order" class="btn btn-primary" style="width: 100%;">
                    ✅ Place Order
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>