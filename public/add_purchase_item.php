<?php
session_start();
require_once '../config/db_connect.php';

// PHARMACIST LOGIN CHECK
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role'])!=='PHARMACIST') {
    header("Location: login.php");
    exit();
}

$success = false;
$error = "";

// ADD MEDICINE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD']=='POST') {
    if (!empty($_POST['name']) && !empty($_POST['stock']) && !empty($_POST['price'])) {
        $stmt = $conn->prepare("INSERT INTO medicine(NAME, STOCK_QUANTITY, PRICE) VALUES(?,?,?)");
        $stmt->bind_param("sid", $_POST['name'], $_POST['stock'], $_POST['price']);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = "Database Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Please fill in all fields!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Stock - PillPoint</title>
<style>
/* --- Basic Reset --- */
* { box-sizing: border-box; margin:0; padding:0; }
body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* --- Header --- */
header {
    background: linear-gradient(120deg, #667eea, #764ba2);
    color: #fff;
    padding: 20px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    border-bottom-left-radius: 15px;
    border-bottom-right-radius: 15px;
}
header h2 { font-size: 1.8rem; }
nav a {
    color: #fff;
    text-decoration: none;
    margin-left: 20px;
    font-weight: 600;
    transition: transform 0.2s, opacity 0.2s;
}
nav a:hover { transform: scale(1.05); opacity: 0.85; }

/* --- Container --- */
.dashboard-container {
    flex: 1;
    max-width: 700px;
    margin: 40px auto;
    padding: 0 20px;
}

/* --- Welcome Section --- */
.welcome-section {
    background: #fff;
    padding: 30px 25px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    text-align: center;
}
.welcome-section h1 { font-size: 2rem; color: #333; margin-bottom: 10px; }

/* --- Card/Form --- */
.card {
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.card form { display: flex; flex-direction: column; }
.card form input {
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 1rem;
    transition: border-color 0.3s, box-shadow 0.3s;
}
.card form input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 8px rgba(102,126,234,0.4);
}
.card form button {
    padding: 12px;
    background: linear-gradient(120deg, #667eea, #764ba2);
    color: #fff;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
}
.card form button:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
}

/* --- Success/Error Message --- */
.message {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}
.success { background: #d4edda; color: #155724; }
.error { background: #f8d7da; color: #721c24; }

/* --- Dashboard Button --- */
.back-dashboard {
    display: inline-block;
    padding: 12px 25px;
    background: #667eea;
    color: #fff;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    transition: background 0.3s, transform 0.2s;
}
.back-dashboard:hover { background: #764ba2; transform: scale(1.05); }

/* --- Footer --- */
footer {
    text-align: center;
    padding: 15px;
    background: linear-gradient(120deg, #667eea, #764ba2);
    color: #fff;
    margin-top: auto;
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
}

/* --- Responsive --- */
@media(max-width: 600px) {
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
        <a href="add_purchase_item.php">Add Stock</a>
        <a href="view_suppliers.php">Suppliers</a>
        <a href="add_supplier.php">Add Supplier</a>
        <a href="view_customer_orders.php">Orders</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="dashboard-container">
    <div class="welcome-section">
        <h1>Add Medicine Stock</h1>
    </div>

    <div class="card">
        <!-- Show success or error message -->
        <?php if($success): ?>
            <div class="message success">✅ Medicine added successfully!</div>
            <a class="back-dashboard" href="dashboard_pharmacist.php">Go Back to Dashboard</a>
        <?php elseif($error): ?>
            <div class="message error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Show form only if medicine not successfully added yet -->
        <?php if(!$success): ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Medicine Name" required>
            <input type="number" name="stock" placeholder="Stock Quantity" required>
            <input type="number" step="0.01" name="price" placeholder="Price per Unit" required>
            <button type="submit">Add Medicine</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<footer>© 2026 PillPoint Pharmacy Management System</footer>

</body>
</html>
<?php $conn->close(); ?>
