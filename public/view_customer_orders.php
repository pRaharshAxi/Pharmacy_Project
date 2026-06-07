<?php
session_start();
require_once '../config/db_connect.php';

// PHARMACIST LOGIN CHECK
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: login.php");
    exit();
}

// PHARMACIST INFO
$f_name = $_SESSION['f_name'] ?? 'Pharmacist';
$l_name = $_SESSION['l_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Orders - MedCare</title>
<style>
html, body { height:100%; margin:0; font-family:'Segoe UI',sans-serif; display:flex; flex-direction:column; background:#78c5e4; }
header { background:linear-gradient(120deg,#667eea,#764ba2); color:white; padding:20px 40px; display:flex; justify-content:space-between; align-items:center; border-bottom-left-radius:15px; border-bottom-right-radius:15px; box-shadow:0 4px 15px rgba(0,0,0,0.15); }
header h2 { margin:0; font-size:1.8rem; }
nav a { color:white; text-decoration:none; margin-left:20px; font-weight:600; transition:0.3s; }
nav a:hover { opacity:0.8; }

.dashboard-container { flex:1; max-width:1100px; margin:30px auto; padding:0 20px; }
.card, .table-container { background:white; padding:20px; border-radius:15px; box-shadow:0 8px 20px rgba(0,0,0,0.1); margin-bottom:25px; }
.card h3 { margin:0 0 10px; color:#667eea; }
.card p { color:#555; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { padding:12px 15px; text-align:center; border-bottom:1px solid #ddd; }
th { background:#667eea; color:white; }
tr:hover { background:#f0f4ff; }
button { padding:8px 12px; border:none; border-radius:8px; background:#667eea; color:white; cursor:pointer; transition:0.3s; }
button:hover { opacity:0.85; }

footer { text-align:center; padding:15px; background:linear-gradient(120deg,#667eea,#764ba2); color:white; margin-top:auto; border-top-left-radius:15px; border-top-right-radius:15px; box-shadow:0 -4px 15px rgba(0,0,0,0.1); }

@media(max-width:600px){header{flex-direction:column;align-items:flex-start;} nav{margin-top:10px;}}
</style>
</head>
<body>
<header>
    <h2>MedCare - Pharmacist</h2>
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
    <div class="card">
        <h3>Customer Orders</h3>
        <p>Review and manage customer orders efficiently.</p>
    </div>

    <div class="card">
        <!-- Back button -->
        <a href="dashboard_pharmacist.php"><button style="margin-bottom:15px;">← Back to Dashboard</button></a>

        <table>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Medicine</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php
            // Fetch orders with proper column names
            $orders = $conn->query("
                SELECT o.ORDER_ID, CONCAT(c.F_NAME,' ',c.L_NAME) AS CUSTOMER, m.NAME AS MEDICINE, o.QUANTITY, o.STATUS
                FROM orders o
                JOIN customer c ON o.CUSTOMER_ID = c.CUSTOMER_ID
                JOIN medicine m ON o.MEDICINE_ID = m.MEDICINE_ID
                ORDER BY o.STATUS='PENDING' DESC, o.ORDER_ID DESC
            ");

            while($row = $orders->fetch_assoc()) {
                echo "<tr>
                    <td>{$row['ORDER_ID']}</td>
                    <td>{$row['CUSTOMER']}</td>
                    <td>{$row['MEDICINE']}</td>
                    <td>{$row['QUANTITY']}</td>
                    <td>{$row['STATUS']}</td>
                    <td><a href='update_order_status.php?id={$row['ORDER_ID']}'><button>Update</button></a></td>
                </tr>";
            }
            ?>
        </table>
    </div>
</div>

<footer>© 2026 PillPoint Pharmacy Management System</footer>
</body>
</html>
<?php $conn->close(); ?>
