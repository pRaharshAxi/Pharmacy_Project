<?php
session_start();
require_once '../config/db_connect.php';
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role'])!=='PHARMACIST') {
    header("Location: login.php");
    exit();
}

$order_id = $_GET['id'] ?? 0;
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET STATUS=? WHERE ORDER_ID=?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    header("Location: view_customer_orders.php");
    exit();
}

// Fetch order info
$order = $conn->query("SELECT * FROM orders WHERE ORDER_ID=$order_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Order Status - PillPoint</title>
<style>
    html, body {
        height: 100%;
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        display: flex;
        flex-direction: column;
        background: #78c5e4;
    }

    /* Header */
    header {
        background: linear-gradient(120deg, #667eea, #764ba2);
        color: white;
        padding: 20px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        border-bottom-left-radius: 15px;
        border-bottom-right-radius: 15px;
    }
    header h2 { margin: 0; font-size: 1.8rem; }
    nav a {
        color: white;
        text-decoration: none;
        margin-left: 20px;
        font-weight: 600;
        transition: opacity 0.3s;
    }
    nav a:hover { opacity: 0.8; }

    /* Main content */
    .dashboard-container {
        flex: 1;
        max-width: 1100px;
        margin: 30px auto;
        padding: 0 20px;
    }

    /* Welcome section */
    .welcome-section {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        text-align: center;
    }
    .welcome-section h1 { margin: 0 0 10px; font-size: 2rem; color: #333; }
    .welcome-section p { color: #555; font-size: 1rem; }

    /* Stats grid */
    .stats-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 25px;
        justify-content: center;
    }

    .stat-card {
        flex: 1 1 220px;
        background: white;
        padding: 25px 20px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    }

    .stat-card .number {
        font-size: 2.2rem;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 8px;
    }
    .stat-card .label {
        color: #555;
        font-size: 1rem;
        font-weight: 500;
    }

    /* Footer */
    footer {
        text-align: center;
        padding: 15px;
        background: linear-gradient(120deg, #667eea, #764ba2);
        color: white;
        margin-top: auto;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        box-shadow: 0 -4px 15px rgba(0,0,0,0.1);
    }

    @media(max-width: 600px) {
        .stats-grid { flex-direction: column; gap: 20px; }
        header { flex-direction: column; align-items: flex-start; }
        nav { margin-top: 10px; }
    }
</style>
</head>
<body>
<header>
    <h2>PillPoint - Pharmacist</h2>
    <nav>
        <a href="dashboard_pharmacist.php">Dashboard</a>
        <a href="view_customer_orders.php">Orders</a>
        <a href="add_purchase_item.php">Add Stock</a>
        <a href="view_suppliers.php">Suppliers</a>
        <a href="add_supplier.php">Add Supplier</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="dashboard-container">
    <div class="welcome-section">
        <h1>Update Order Status</h1>
        <p>Order ID: <?php echo $order['ORDER_ID']; ?></p>
    </div>
    <div class="card">
        <form method="POST">
            <select name="status" required>
                <option value="PENDING" <?php if($order['STATUS']=='PENDING') echo 'selected'; ?>>PENDING</option>
                <option value="COMPLETED" <?php if($order['STATUS']=='COMPLETED') echo 'selected'; ?>>COMPLETED</option>
                <option value="CANCELLED" <?php if($order['STATUS']=='CANCELLED') echo 'selected'; ?>>CANCELLED</option>
            </select><br><br>
            <button type="submit">Update Status</button>
        </form>
    </div>
</div>

<footer>© 2026 PillPoint Pharmacy Management System</footer>
</body>
</html>
<?php $conn->close(); ?>
