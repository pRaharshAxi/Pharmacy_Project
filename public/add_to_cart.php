<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Process add to cart request
if (isset($_POST['add_to_cart'])) {
    $medicine_id = mysqli_real_escape_string($conn, $_POST['medicine_id']);
    $quantity = (int)$_POST['quantity'];

    // Validate quantity
    if ($quantity <= 0) {
        $_SESSION['message'] = 'Invalid quantity!';
        $_SESSION['message_type'] = 'error';
        header("Location: view_medicines.php");
        exit();
    }

    // Fetch medicine details from database
    $stmt = $conn->prepare("SELECT MEDICINE_ID, NAME, PRICE, STOCK_QUANTITY FROM medicine WHERE MEDICINE_ID = ?");
    $stmt->bind_param("s", $medicine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medicine = $result->fetch_assoc();

    if ($medicine) {
        // Check if requested quantity is available
        if ($quantity > $medicine['STOCK_QUANTITY']) {
            $_SESSION['message'] = 'Only ' . $medicine['STOCK_QUANTITY'] . ' units available in stock!';
            $_SESSION['message_type'] = 'error';
            header("Location: view_medicines.php");
            exit();
        }

        // Initialize cart if not set
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Check if medicine already exists in cart
        if (isset($_SESSION['cart'][$medicine_id])) {
            // Update quantity
            $new_quantity = $_SESSION['cart'][$medicine_id]['quantity'] + $quantity;
            
            // Check if new total quantity exceeds stock
            if ($new_quantity > $medicine['STOCK_QUANTITY']) {
                $_SESSION['message'] = 'Cannot add more. Total would exceed available stock!';
                $_SESSION['message_type'] = 'error';
                header("Location: view_medicines.php");
                exit();
            }
            
            $_SESSION['cart'][$medicine_id]['quantity'] = $new_quantity;
            $_SESSION['message'] = 'Cart updated! ' . $medicine['NAME'] . ' quantity increased.';
        } else {
            // Add new item to cart
            $_SESSION['cart'][$medicine_id] = [
                'id' => $medicine['MEDICINE_ID'],
                'name' => $medicine['NAME'],
                'price' => $medicine['PRICE'],
                'quantity' => $quantity,
                'stock' => $medicine['STOCK_QUANTITY']
            ];
            $_SESSION['message'] = $medicine['NAME'] . ' added to cart successfully!';
        }

        $_SESSION['message_type'] = 'success';
        header("Location: view_medicines.php");
        exit();
    } else {
        $_SESSION['message'] = 'Medicine not found!';
        $_SESSION['message_type'] = 'error';
        header("Location: view_medicines.php");
        exit();
    }
}

// If accessed without POST data, redirect to medicines page
header("Location: view_medicines.php");
exit();
?>