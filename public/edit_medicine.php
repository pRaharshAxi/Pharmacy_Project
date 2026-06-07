<?php
session_start();
require_once '../config/db_connect.php';

// Admin check
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

$medicine_id = $_GET['id'] ?? '';
$error = '';
$success = '';

// Fetch medicine details
if ($medicine_id) {
    $stmt = $conn->prepare("SELECT * FROM medicine WHERE MEDICINE_ID = ?");
    $stmt->bind_param("s", $medicine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: manage_medicines.php");
        exit();
    }
    
    $medicine = $result->fetch_assoc();
    $stmt->close();
} else {
    header("Location: manage_medicines.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $dosage = trim($_POST['dosage']);
    $exp_duration = trim($_POST['exp_duration']);
    
    // Validation
    if (empty($name) || empty($category) || $price <= 0 || $stock < 0 || empty($dosage) || empty($exp_duration)) {
        $error = "All fields are required and must have valid values.";
    } else {
        // Update medicine
        $stmt = $conn->prepare("UPDATE medicine SET NAME = ?, CATEGORY = ?, Price = ?, STOCK_QUANTITY = ?, DOSAGE = ?, EXP_DURATION = ? WHERE MEDICINE_ID = ?");
        $stmt->bind_param("ssdisss", $name, $category, $price, $stock, $dosage, $exp_duration, $medicine_id);
        
        if ($stmt->execute()) {
            $success = "Medicine updated successfully!";
            // Refresh medicine data
            $stmt = $conn->prepare("SELECT * FROM medicine WHERE MEDICINE_ID = ?");
            $stmt->bind_param("s", $medicine_id);
            $stmt->execute();
            $medicine = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "Error updating medicine: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Medicine - MedCare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', sans-serif;
        background: #f5f7fa;
        padding: 20px;
    }

    .container {
        max-width: 700px;
        margin: 0 auto;
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .header {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e5e7eb;
    }

    .header h1 {
        color: #1f2937;
        margin-bottom: 5px;
    }

    .back-link {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .back-link:hover {
        color: #2563eb;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #374151;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.95rem;
        font-family: inherit;
        transition: border-color 0.2s;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #e5e7eb;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
        flex: 1;
    }

    .btn-primary:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .medicine-info {
        background: #f9fafb;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #3b82f6;
    }

    .medicine-info p {
        margin: 5px 0;
        color: #6b7280;
        font-size: 0.9rem;
    }

    .medicine-info strong {
        color: #1f2937;
    }

    @media (max-width: 640px) {
        .container {
            padding: 20px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="manage_medicines.php" class="back-link">← Back to Medicines</a>
        <h1>Edit Medicine</h1>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="medicine-info">
        <p><strong>Medicine ID:</strong> <?php echo htmlspecialchars($medicine['MEDICINE_ID']); ?></p>
        <p><strong>Current Stock:</strong> <?php echo $medicine['STOCK_QUANTITY']; ?> units</p>
    </div>

    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Medicine Name *</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($medicine['NAME']); ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    <option value="Pain and Fever" <?php echo $medicine['CATEGORY'] === 'Pain and Fever' ? 'selected' : ''; ?>>Pain and Fever</option>
                    <option value="Antibiotics" <?php echo $medicine['CATEGORY'] === 'Antibiotics' ? 'selected' : ''; ?>>Antibiotics</option>
                    <option value="Chronic Care" <?php echo $medicine['CATEGORY'] === 'Chronic Care' ? 'selected' : ''; ?>>Chronic Care</option>
                    <option value="General Wellness" <?php echo $medicine['CATEGORY'] === 'General Wellness' ? 'selected' : ''; ?>>General Wellness</option>
                </select>
            </div>

            <div class="form-group">
                <label for="price">Price (Rs.) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($medicine['Price']); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="stock">Stock Quantity *</label>
                <input type="number" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($medicine['STOCK_QUANTITY']); ?>" required>
            </div>

            <div class="form-group">
                <label for="dosage">Dosage *</label>
                <input type="text" id="dosage" name="dosage" value="<?php echo htmlspecialchars($medicine['DOSAGE']); ?>" placeholder="e.g., 2 per day" required>
            </div>
        </div>

        <div class="form-group">
            <label for="exp_duration">Expiry Duration *</label>
            <input type="text" id="exp_duration" name="exp_duration" value="<?php echo htmlspecialchars($medicine['EXP_DURATION']); ?>" placeholder="e.g., 2-3 years" required>
        </div>

        <div class="form-actions">
            <a href="manage_medicines.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
        </div>
    </form>
</div>

</body>
</html>

<?php $conn->close(); ?>