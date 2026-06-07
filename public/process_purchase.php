<?php
session_start();
require_once '../config/db_connect.php';

// Check pharmacist login
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_suppliers.php");
    exit();
}

$supplier_id = $_POST['supplier_id'];
$pharmacist_id = $_POST['pharmacist_id'];
$quantities = $_POST['quantities'] ?? [];
$costs = $_POST['costs'] ?? [];

// Filter out medicines with quantity 0
$selected_medicines = [];
$total_amount = 0;

foreach ($quantities as $medicine_id => $quantity) {
    $qty = intval($quantity);
    if ($qty > 0) {
        $cost = floatval($costs[$medicine_id]);
        $selected_medicines[] = [
            'medicine_id' => $medicine_id,
            'quantity' => $qty,
            'unit_cost' => $cost
        ];
        $total_amount += ($qty * $cost);
    }
}

// Check if at least one medicine was selected
if (empty($selected_medicines)) {
    $_SESSION['message'] = 'Please select at least one medicine with quantity greater than 0.';
    $_SESSION['message_type'] = 'error';
    header("Location: create_purchase.php?supplier_id=" . $supplier_id);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Generate unique purchase ID
    $purchase_id_query = "SELECT PURCHASE_ID FROM purchase ORDER BY PURCHASE_ID DESC LIMIT 1";
    $result = $conn->query($purchase_id_query);
    
    if ($result && $result->num_rows > 0) {
        $last_id = $result->fetch_assoc()['PURCHASE_ID'];
        // Extract number from ID (handles both 'PUR0001' and '1' formats)
        if (preg_match('/(\d+)$/', $last_id, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }
        $purchase_id = 'PUR' . str_pad($number, 4, '0', STR_PAD_LEFT);
    } else {
        $purchase_id = 'PUR0001';
    }
    
    // Get current date
    $purchase_date = date('Y-m-d');
    
    // Insert into purchase table
    $purchase_stmt = $conn->prepare("INSERT INTO purchase (PURCHASE_ID, PURCHASE_DATE, SUPPLIER_ID, PHARMACIST_ID, TOTAL_AMOUNT) VALUES (?, ?, ?, ?, ?)");
    $purchase_stmt->bind_param("ssssd", $purchase_id, $purchase_date, $supplier_id, $pharmacist_id, $total_amount);
    
    if (!$purchase_stmt->execute()) {
        throw new Exception("Failed to create purchase: " . $purchase_stmt->error);
    }
    $purchase_stmt->close();
    
    // Get the last purchase_item_id
    $item_id_query = "SELECT PURCHASE_ITEM_ID FROM purchase_item ORDER BY PURCHASE_ITEM_ID DESC LIMIT 1";
    $item_result = $conn->query($item_id_query);
    
    if ($item_result && $item_result->num_rows > 0) {
        $last_item_id = intval($item_result->fetch_assoc()['PURCHASE_ITEM_ID']);
    } else {
        $last_item_id = 0;
    }
    
    // Prepare statements for purchase items
    $item_stmt = $conn->prepare("INSERT INTO purchase_item (PURCHASE_ITEM_ID, PURCHASE_ID, QUANTITY, MEDICINE_ID, UNIT_COST) VALUES (?, ?, ?, ?, ?)");
    $pharmacist_item_stmt = $conn->prepare("INSERT INTO pharmacist_purchaseitem (PHARMACIST_ID, PURCHASE_ITEM_ID) VALUES (?, ?)");
    $update_stock_stmt = $conn->prepare("UPDATE medicine SET STOCK_QUANTITY = STOCK_QUANTITY + ? WHERE MEDICINE_ID = ?");
    
    // Insert each selected medicine
    foreach ($selected_medicines as $item) {
        // Generate unique purchase_item_id
        $purchase_item_id = ++$last_item_id;
        $medicine_id = $item['medicine_id'];
        $quantity = $item['quantity'];
        $unit_cost = $item['unit_cost'];
        
        // Insert into purchase_item
        $item_stmt->bind_param("isisd", $purchase_item_id, $purchase_id, $quantity, $medicine_id, $unit_cost);
        if (!$item_stmt->execute()) {
            throw new Exception("Failed to insert purchase item: " . $item_stmt->error);
        }
        
        // Insert into pharmacist_purchaseitem
        $pharmacist_item_stmt->bind_param("si", $pharmacist_id, $purchase_item_id);
        if (!$pharmacist_item_stmt->execute()) {
            throw new Exception("Failed to link pharmacist to purchase item: " . $pharmacist_item_stmt->error);
        }
        
        // Update medicine stock
        $update_stock_stmt->bind_param("is", $quantity, $medicine_id);
        if (!$update_stock_stmt->execute()) {
            throw new Exception("Failed to update medicine stock: " . $update_stock_stmt->error);
        }
    }
    
    // Close statements
    $item_stmt->close();
    $pharmacist_item_stmt->close();
    $update_stock_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Success message
    $_SESSION['message'] = 'Purchase created successfully! Purchase ID: ' . $purchase_id;
    $_SESSION['message_type'] = 'success';
    
    // Redirect to purchase details
    header("Location: purchase_details.php?id=" . $purchase_id);
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    $_SESSION['message'] = 'Error creating purchase: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
    
    header("Location: create_purchase.php?supplier_id=" . $supplier_id);
    exit();
}

$conn->close();
?>