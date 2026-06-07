<?php
session_start();
require_once '../config/db_connect.php';

// Check customer login
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'CUSTOMER') {
    $_SESSION['message'] = 'Please log in as a customer to place orders.';
    $_SESSION['message_type'] = 'error';
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['place_order'])) {
    header("Location: view_cart.php");
    exit();
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    $_SESSION['message'] = 'Your cart is empty!';
    $_SESSION['message_type'] = 'error';
    header("Location: view_medicines.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$order_date = date('Y-m-d');
$total_amount = 0;

// Calculate total
foreach ($_SESSION['cart'] as $item) {
    $total_amount += ($item['price'] * $item['quantity']);
}

// Handle prescription upload
$prescription_path = null;
if (isset($_FILES['prescription']) && $_FILES['prescription']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/prescriptions/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['prescription']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        $_SESSION['message'] = 'Invalid file type. Only JPG, PNG, and PDF files are allowed.';
        $_SESSION['message_type'] = 'error';
        header("Location: view_cart.php");
        exit();
    }
    
    // Check file size (5MB max)
    if ($_FILES['prescription']['size'] > 5 * 1024 * 1024) {
        $_SESSION['message'] = 'File size too large. Maximum 5MB allowed.';
        $_SESSION['message_type'] = 'error';
        header("Location: view_cart.php");
        exit();
    }
    
    $new_filename = $customer_id . '_' . time() . '.' . $file_extension;
    $prescription_path = $upload_dir . $new_filename;
    
    if (!move_uploaded_file($_FILES['prescription']['tmp_name'], $prescription_path)) {
        $_SESSION['message'] = 'Failed to upload prescription. Please try again.';
        $_SESSION['message_type'] = 'error';
        header("Location: view_cart.php");
        exit();
    }
}

// Start transaction
$conn->begin_transaction();

try {
    // Generate unique order ID
    $order_id_query = "SELECT ORDER_ID FROM orders ORDER BY ORDER_ID DESC LIMIT 1";
    $result = $conn->query($order_id_query);
    
    if ($result && $result->num_rows > 0) {
        $last_id = $result->fetch_assoc()['ORDER_ID'];
        // Handle both 'ORD0001' and numeric formats
        if (preg_match('/(\d+)$/', $last_id, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }
        $order_id = 'ORD' . str_pad($number, 4, '0', STR_PAD_LEFT);
    } else {
        $order_id = 'ORD0001';
    }
    
    // Insert into orders table WITH prescription path
    $order_stmt = $conn->prepare("INSERT INTO orders (ORDER_ID, CUSTOMER_ID, ORDER_DATE, TOTAL_AMOUNT, STATUS, PRESCRIPTION_PATH) VALUES (?, ?, ?, ?, 'PENDING', ?)");
    $order_stmt->bind_param("sssds", $order_id, $customer_id, $order_date, $total_amount, $prescription_path);
    
    if (!$order_stmt->execute()) {
        throw new Exception("Failed to create order: " . $order_stmt->error);
    }
    $order_stmt->close();
    
    // Get the last order_item_id
    $item_id_query = "SELECT ORDER_ITEM_ID FROM order_item ORDER BY ORDER_ITEM_ID DESC LIMIT 1";
    $item_result = $conn->query($item_id_query);
    
    if ($item_result && $item_result->num_rows > 0) {
        $last_item_id = intval($item_result->fetch_assoc()['ORDER_ITEM_ID']);
    } else {
        $last_item_id = 0;
    }
    
    // Insert order items
    $item_stmt = $conn->prepare("INSERT INTO order_item (ORDER_ITEM_ID, ORDER_ID, MEDICINE_ID, QUANTITY, PRICE) VALUES (?, ?, ?, ?, ?)");
    $update_stock_stmt = $conn->prepare("UPDATE medicine SET STOCK_QUANTITY = STOCK_QUANTITY - ? WHERE MEDICINE_ID = ?");
    
    foreach ($_SESSION['cart'] as $medicine_id => $item) {
        $order_item_id = ++$last_item_id;
        $quantity = $item['quantity'];
        $price = $item['price'];
        
        // Insert order item
        $item_stmt->bind_param("issid", $order_item_id, $order_id, $medicine_id, $quantity, $price);
        if (!$item_stmt->execute()) {
            throw new Exception("Failed to add order item: " . $item_stmt->error);
        }
        
        // Update stock
        $update_stock_stmt->bind_param("is", $quantity, $medicine_id);
        if (!$update_stock_stmt->execute()) {
            throw new Exception("Failed to update stock: " . $update_stock_stmt->error);
        }
    }
    
    $item_stmt->close();
    $update_stock_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Clear cart
    unset($_SESSION['cart']);
    
    // Success message
    $_SESSION['message'] = '🎉 Order placed successfully! Order ID: ' . $order_id . ' | Total: Rs. ' . number_format($total_amount, 2);
    $_SESSION['message_type'] = 'success';
    $_SESSION['last_order_id'] = $order_id;
    
    // Close database connection
    $conn->close();
    
    // Redirect to CUSTOMER orders page (not pharmacist view_orders.php)
    header("Location: my_order.php");
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    // Delete uploaded prescription if order failed
    if ($prescription_path && file_exists($prescription_path)) {
        unlink($prescription_path);
    }
    
    $_SESSION['message'] = 'Failed to place order: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
    
    header("Location: view_cart.php");
    exit();
}

$conn->close();
?>