<?php
session_start();
require_once '../config/db_connect.php';

// Check pharmacist login
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: login.php");
    exit();
}

$pharmacist_id = $_SESSION['user_id'];

// Fetch all purchases
$sql = "SELECT p.PURCHASE_ID, p.PURCHASE_DATE, p.TOTAL_AMOUNT, 
               s.COMPANY_NAME, s.SUPPLIER_ID
        FROM purchase p
        JOIN supplier s ON p.SUPPLIER_ID = s.SUPPLIER_ID
        WHERE p.PHARMACIST_ID = ?
        ORDER BY p.PURCHASE_DATE DESC, p.PURCHASE_ID DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $pharmacist_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Purchase History - MedCare</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

/* Header */
header {
    background: linear-gradient(120deg, #1d3557, #457b9d);
    color: white;
    padding: 20px 40px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
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
    font-size: 2.5rem;
    margin-bottom: 10px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

/* Alert */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* Purchases Grid */
.purchases-grid {
    display: grid;
    gap: 20px;
}

.purchase-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    transition: all 0.3s;
}

.purchase-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
}

.purchase-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 15px;
    margin-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.purchase-id {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1d3557;
}

.purchase-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.detail-label {
    color: #666;
    font-size: 0.9rem;
    font-weight: 500;
}

.detail-value {
    color: #333;
    font-size: 1.1rem;
    font-weight: 600;
}

.purchase-total {
    color: #27ae60;
    font-size: 1.5rem;
}

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
}

.btn-view {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-view:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

/* Empty State */
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
    margin-bottom: 20px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 30px;
}

.btn-primary:hover {
    transform: scale(1.05);
}

/* Responsive */
@media (max-width: 768px) {
    .purchase-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
}
</style>
</head>
<body>

<header>
    <h2>🏥 MedCare</h2>
    <nav>
        <a href="dashboard_pharmacist.php">Dashboard</a>
        <a href="view_suppliers.php">Suppliers</a>
        <a href="view_purchases.php">Purchase History</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="page-header">
        <h1>📋 Purchase History</h1>
        <p>View your medicine purchase records</p>
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

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="purchases-grid">
            <?php while($purchase = $result->fetch_assoc()): ?>
                <div class="purchase-card">
                    <div class="purchase-header">
                        <div class="purchase-id">Purchase #<?php echo htmlspecialchars($purchase['PURCHASE_ID']); ?></div>
                    </div>

                    <div class="purchase-details">
                        <div class="detail-item">
                            <span class="detail-label">Purchase Date</span>
                            <span class="detail-value">
                                <?php echo date('M d, Y', strtotime($purchase['PURCHASE_DATE'])); ?>
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Supplier</span>
                            <span class="detail-value">
                                <?php echo htmlspecialchars($purchase['COMPANY_NAME']); ?>
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Total Amount</span>
                            <span class="detail-value purchase-total">
                                Rs. <?php echo number_format($purchase['TOTAL_AMOUNT'], 2); ?>
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Items</span>
                            <span class="detail-value">
                                <?php
                                // Count items
                                $count_stmt = $conn->prepare("SELECT COUNT(*) as item_count FROM purchase_item WHERE PURCHASE_ID = ?");
                                $count_stmt->bind_param("s", $purchase['PURCHASE_ID']);
                                $count_stmt->execute();
                                $count = $count_stmt->get_result()->fetch_assoc();
                                echo $count['item_count'] . ' item(s)';
                                $count_stmt->close();
                                ?>
                            </span>
                        </div>
                    </div>

                    <a href="purchase_details.php?id=<?php echo $purchase['PURCHASE_ID']; ?>" class="btn btn-view">
                        View Details →
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>No Purchase History</h3>
            <p>You haven't made any purchases yet. Start by selecting a supplier!</p>
            <a href="view_suppliers.php" class="btn btn-primary">View Suppliers</a>
        </div>
    <?php endif; ?>
</div>

<?php 
$stmt->close();
$conn->close(); 
?>

</body>
</html>