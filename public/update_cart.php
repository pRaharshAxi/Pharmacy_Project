<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Remove Action (via URL parameter)
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    if (isset($_SESSION['cart'][$id])) {
        $medicine_name = $_SESSION['cart'][$id]['name'];
        unset($_SESSION['cart'][$id]);
        
        $_SESSION['message'] = $medicine_name . ' removed from cart.';
        $_SESSION['message_type'] = 'success';
    }
    
    header("Location: view_cart.php");
    exit();
}

// Handle Quantity Update (via Form POST)
if (isset($_POST['update_cart'])) {
    $errors = [];
    
    foreach ($_POST['qty'] as $id => $qty) {
        $qty = (int)$qty;
        
        if ($qty <= 0) {
            // If user types 0 or negative, remove item
            if (isset($_SESSION['cart'][$id])) {
                unset($_SESSION['cart'][$id]);
            }
        } else {
            // Update quantity with stock validation
            if (isset($_SESSION['cart'][$id])) {
                // Verify stock availability from database
                $stmt = $conn->prepare("SELECT STOCK_QUANTITY, NAME FROM medicine WHERE MEDICINE_ID = ?");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $medicine = $result->fetch_assoc();
                
                if ($medicine) {
                    if ($qty > $medicine['STOCK_QUANTITY']) {
                        $errors[] = $medicine['NAME'] . ': Only ' . $medicine['STOCK_QUANTITY'] . ' units available.';
                        // Set to max available stock
                        $_SESSION['cart'][$id]['quantity'] = $medicine['STOCK_QUANTITY'];
                    } else {
                        $_SESSION['cart'][$id]['quantity'] = $qty;
                    }
                    
                    // Update stock info in cart
                    $_SESSION['cart'][$id]['stock'] = $medicine['STOCK_QUANTITY'];
                } else {
                    // Medicine no longer exists
                    unset($_SESSION['cart'][$id]);
                    $errors[] = 'Some items are no longer available and were removed.';
                }
            }
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['message'] = implode('<br>', $errors);
        $_SESSION['message_type'] = 'error';
    } else {
        $_SESSION['message'] = 'Cart updated successfully!';
        $_SESSION['message_type'] = 'success';
    }
    
    header("Location: view_cart.php");
    exit();
}

// If accessed directly without proper parameters, redirect to cart
header("Location: view_cart.php");
exit();
?>