<?php
session_start();
require_once '../config/db_connect.php';

// Check pharmacist login
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: login.php");
    exit();
}

$pharmacist_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = $_POST['supplier_id'];
    $purchase_date = $_POST['purchase_date'];
    $medicines = $_POST['medicines'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $unit_costs = $_POST['unit_costs'] ?? [];
    
    // Validate input
    if (empty($supplier_id) || empty($medicines)) {
        $_SESSION['message'] = 'Please select a supplier and add at least one medicine.';
        $_SESSION['message_type'] = 'error';
        header("Location: create_purchase.php");
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Generate unique purchase ID
        $purchase_id_query = "SELECT PURCHASE_ID FROM purchase ORDER BY PURCHASE_ID DESC LIMIT 1";
        $result = $conn->query($purchase_id_query);
        
        if ($result->num_rows > 0) {
            $last_id = $result->fetch_assoc()['PURCHASE_ID'];
            $purchase_id = 'PUR' . str_pad((intval(substr($last_id, 3)) + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $purchase_id = 'PUR0001';
        }
        
        // Calculate total amount
        $total_amount = 0;
        for ($i = 0; $i < count($medicines); $i++) {
            $total_amount += ($quantities[$i] * $unit_costs[$i]);
        }
        
        // Insert into purchase table
        $purchase_stmt = $conn->prepare("INSERT INTO purchase (PURCHASE_ID, PURCHASE_DATE, SUPPLIER_ID, PHARMACIST_ID, TOTAL_AMOUNT) VALUES (?, ?, ?, ?, ?)");
        $purchase_stmt->bind_param("ssssd", $purchase_id, $purchase_date, $supplier_id, $pharmacist_id, $total_amount);
        $purchase_stmt->execute();
        $purchase_stmt->close();
        
        // Get the last purchase_item_id to generate unique IDs
        $item_id_query = "SELECT PURCHASE_ITEM_ID FROM purchase_item ORDER BY PURCHASE_ITEM_ID DESC LIMIT 1";
        $item_result = $conn->query($item_id_query);
        
        if ($item_result->num_rows > 0) {
            $last_item_id = $item_result->fetch_assoc()['PURCHASE_ITEM_ID'];
        } else {
            $last_item_id = 0;
        }
        
        // Insert purchase items
        $item_stmt = $conn->prepare("INSERT INTO purchase_item (PURCHASE_ITEM_ID, PURCHASE_ID, QUANTITY, MEDICINE_ID, UNIT_COST) VALUES (?, ?, ?, ?, ?)");
        $pharmacist_item_stmt = $conn->prepare("INSERT INTO pharmacist_purchaseitem (PHARMACIST_ID, PURCHASE_ITEM_ID) VALUES (?, ?)");
        $update_stock_stmt = $conn->prepare("UPDATE medicine SET STOCK_QUANTITY = STOCK_QUANTITY + ? WHERE MEDICINE_ID = ?");
        
        for ($i = 0; $i < count($medicines); $i++) {
            // Generate unique purchase_item_id
            $purchase_item_id = ++$last_item_id;
            $medicine_id = $medicines[$i];
            $quantity = intval($quantities[$i]);
            $unit_cost = floatval($unit_costs[$i]);
            
            // Insert into purchase_item
            $item_stmt->bind_param("isiss", $purchase_item_id, $purchase_id, $quantity, $medicine_id, $unit_cost);
            $item_stmt->execute();
            
            // Insert into pharmacist_purchaseitem
            $pharmacist_item_stmt->bind_param("si", $pharmacist_id, $purchase_item_id);
            $pharmacist_item_stmt->execute();
            
            // Update medicine stock
            $update_stock_stmt->bind_param("is", $quantity, $medicine_id);
            $update_stock_stmt->execute();
        }
        
        $item_stmt->close();
        $pharmacist_item_stmt->close();
        $update_stock_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['message'] = 'Purchase created successfully!';
        $_SESSION['message_type'] = 'success';
        header("Location: purchase_details.php?id=" . $purchase_id);
        exit();
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['message'] = 'Error creating purchase: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
        header("Location: create_purchase.php");
        exit();
    }
}

// Fetch suppliers
$suppliers_sql = "SELECT SUPPLIER_ID, COMPANY_NAME FROM supplier ORDER BY COMPANY_NAME";
$suppliers_result = $conn->query($suppliers_sql);

// Fetch medicines
$medicines_sql = "SELECT MEDICINE_ID, NAME, CATEGORY, PRICE, STOCK_QUANTITY FROM medicine ORDER BY NAME";
$medicines_result = $conn->query($medicines_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Purchase - MedCare</title>
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
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
}

.form-card {
    background: white;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
}

.page-title {
    font-size: 2rem;
    color: #1d3557;
    margin-bottom: 30px;
    text-align: center;
}

/* Alert */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* Form */
.form-group {
    margin-bottom: 25px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

input, select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
}

input:focus, select:focus {
    outline: none;
    border-color: #667eea;
}

/* Items Section */
.items-section {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
}

.items-section h3 {
    color: #1d3557;
    margin-bottom: 20px;
}

.item-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 80px;
    gap: 15px;
    margin-bottom: 15px;
    align-items: end;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-add {
    background: #28a745;
    color: white;
}

.btn-add:hover {
    background: #218838;
}

.btn-remove {
    background: #dc3545;
    color: white;
    padding: 10px;
}

.btn-remove:hover {
    background: #c82333;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    width: 100%;
    margin-top: 20px;
}

.btn-primary:hover {
    transform: scale(1.02);
}

.total-section {
    margin-top: 20px;
    padding: 20px;
    background: white;
    border-radius: 10px;
    text-align: right;
}

.total-amount {
    font-size: 1.8rem;
    color: #27ae60;
    font-weight: bold;
}

@media (max-width: 768px) {
    .item-row {
        grid-template-columns: 1fr;
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
    <div class="form-card">
        <h1 class="page-title">📝 Create New Purchase</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="purchaseForm">
            <div class="form-group">
                <label for="supplier_id">Supplier *</label>
                <select name="supplier_id" id="supplier_id" required>
                    <option value="">-- Select Supplier --</option>
                    <?php while($supplier = $suppliers_result->fetch_assoc()): ?>
                        <option value="<?php echo $supplier['SUPPLIER_ID']; ?>">
                            <?php echo htmlspecialchars($supplier['COMPANY_NAME']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="purchase_date">Purchase Date *</label>
                <input type="date" name="purchase_date" id="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="items-section">
                <h3>Purchase Items</h3>
                <div id="itemsContainer">
                    <div class="item-row">
                        <div class="form-group">
                            <label>Medicine</label>
                            <select name="medicines[]" required>
                                <option value="">-- Select Medicine --</option>
                                <?php 
                                $medicines_result->data_seek(0);
                                while($medicine = $medicines_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $medicine['MEDICINE_ID']; ?>" data-price="<?php echo $medicine['PRICE']; ?>">
                                        <?php echo htmlspecialchars($medicine['NAME']); ?> - Rs. <?php echo number_format($medicine['PRICE'], 2); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantities[]" min="1" value="1" required>
                        </div>
                        <div class="form-group">
                            <label>Unit Cost (Rs.)</label>
                            <input type="number" name="unit_costs[]" step="0.01" min="0.01" required readonly style="background-color: #f0f0f0; cursor: not-allowed;">
                        </div>
                        <button type="button" class="btn btn-remove" onclick="removeItem(this)" style="display:none;">✕</button>
                    </div>
                </div>
                <button type="button" class="btn btn-add" onclick="addItem()">+ Add Item</button>

                <div class="total-section">
                    <strong>Total Amount:</strong>
                    <div class="total-amount">Rs. <span id="totalAmount">0.00</span></div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Create Purchase</button>
        </form>
    </div>
</div>

<script>
// Medicine options HTML for dynamic rows
const medicineOptions = `
    <option value="">-- Select Medicine --</option>
    <?php 
    $medicines_result->data_seek(0);
    while($medicine = $medicines_result->fetch_assoc()): 
    ?>
        <option value="<?php echo $medicine['MEDICINE_ID']; ?>" data-price="<?php echo $medicine['PRICE']; ?>">
            <?php echo htmlspecialchars($medicine['NAME']); ?> - Rs. <?php echo number_format($medicine['PRICE'], 2); ?>
        </option>
    <?php endwhile; ?>
`;

function addItem() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.innerHTML = `
        <div class="form-group">
            <label>Medicine</label>
            <select name="medicines[]" required onchange="updateTotal()">
                ${medicineOptions}
            </select>
        </div>
        <div class="form-group">
            <label>Quantity</label>
            <input type="number" name="quantities[]" min="1" value="1" required onchange="updateTotal()">
        </div>
        <div class="form-group">
            <label>Unit Cost (Rs.)</label>
            <input type="number" name="unit_costs[]" step="0.01" min="0.01" required readonly style="background-color: #f0f0f0; cursor: not-allowed;" onchange="updateTotal()">
        </div>
        <button type="button" class="btn btn-remove" onclick="removeItem(this)">✕</button>
    `;
    container.appendChild(newRow);
    updateRemoveButtons();
}

function removeItem(btn) {
    btn.closest('.item-row').remove();
    updateRemoveButtons();
    updateTotal();
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach((row, index) => {
        const removeBtn = row.querySelector('.btn-remove');
        if (rows.length === 1) {
            removeBtn.style.display = 'none';
        } else {
            removeBtn.style.display = 'block';
        }
    });
}

function updateTotal() {
    let total = 0;
    const rows = document.querySelectorAll('.item-row');
    rows.forEach(row => {
        const quantity = parseFloat(row.querySelector('input[name="quantities[]"]').value) || 0;
        const unitCost = parseFloat(row.querySelector('input[name="unit_costs[]"]').value) || 0;
        total += quantity * unitCost;
    });
    document.getElementById('totalAmount').textContent = total.toFixed(2);
}

// Auto-fill unit cost when medicine is selected
document.addEventListener('change', function(e) {
    if (e.target.name === 'medicines[]') {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        const row = e.target.closest('.item-row');
        if (price && row) {
            row.querySelector('input[name="unit_costs[]"]').value = price;
            updateTotal();
        }
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateTotal();
    
    // Add listeners for quantity and unit cost changes
    document.querySelectorAll('input[name="quantities[]"], input[name="unit_costs[]"]').forEach(input => {
        input.addEventListener('input', updateTotal);
    });
});
</script>

<?php $conn->close(); ?>

</body>
</html>