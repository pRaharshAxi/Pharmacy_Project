<?php
session_start();
require_once '../config/db_connect.php';

// Check if pharmacist is logged in
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    $_SESSION['error'] = "Order ID is required.";
    header("Location: dashboard_pharmacist.php");
    exit();
}

$order_id = $_GET['order_id'];
$pharmacist_id = $_SESSION['user_id'];

// Start transaction
$conn->begin_transaction();

try {
    // Check if order exists and is still pending
    $stmt = $conn->prepare("SELECT ORDER_ID, CUSTOMER_ID, TOTAL_AMOUNT, STATUS FROM orders WHERE ORDER_ID = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Order not found.");
    }
    
    $order = $result->fetch_assoc();
    $stmt->close();
    
    // Check if order is still pending
    if ($order['STATUS'] !== 'PENDING') {
        throw new Exception("This order has already been " . strtolower($order['STATUS']) . ".");
    }
    
    $customer_id = $order['CUSTOMER_ID'];
    $total_amount = $order['TOTAL_AMOUNT'];
    
    // Get customer email
    $stmt = $conn->prepare("SELECT EMAIL, F_NAME, L_NAME FROM users WHERE USER_ID = ?");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();
    $customer_result = $stmt->get_result();
    $customer = $customer_result->fetch_assoc();
    $customer_email = $customer['EMAIL'];
    $customer_name = $customer['F_NAME'] . ' ' . $customer['L_NAME'];
    $stmt->close();
    
    // Update order status to PROCESSING (accepted by pharmacist)
    $stmt = $conn->prepare("UPDATE orders SET STATUS = 'PROCESSING' WHERE ORDER_ID = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $stmt->close();
    
    // Generate invoice
    $invoice_id = "INV" . strtoupper(uniqid());
    $payment_method = "CASH ON DELIVERY";
    $invoice_date = date("Y-m-d");
    
    // Insert invoice directly into database
    $stmt = $conn->prepare("INSERT INTO invoice (INVOICE_ID, ORDER_ID, CUSTOMER_ID, PHARMACIST_ID, INVOICE_DATE, PAYMENT_METHOD) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $invoice_id, $order_id, $customer_id, $pharmacist_id, $invoice_date, $payment_method);
    if (!$stmt->execute()) {
        throw new Exception("Failed to create invoice: " . $stmt->error);
    }
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Send invoice email to customer
    $subject = "Your Order #" . $order_id . " Has Been Accepted - MedCare Pharmacy";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .order-info { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .info-item { margin: 8px 0; }
            .button { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            .footer { background: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Order Accepted! ✓</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($customer_name) . "</strong>,</p>
                
                <p>Great news! Your order has been accepted by MedCare Pharmacy and is being processed.</p>
                
                <div class='order-info'>
                    <div class='info-item'><strong>Order ID:</strong> " . htmlspecialchars($order_id) . "</div>
                    <div class='info-item'><strong>Invoice ID:</strong> " . htmlspecialchars($invoice_id) . "</div>
                    <div class='info-item'><strong>Order Date:</strong> " . date('F d, Y') . "</div>
                    <div class='info-item'><strong>Total Amount:</strong> Rs. " . number_format($total_amount, 2) . "</div>
                    <div class='info-item'><strong>Payment Method:</strong> Cash on Delivery</div>
                </div>
                
                <p>Your prescription and medicines are being prepared by our pharmacist. You will receive your order soon.</p>
                
                <p>Thank you for choosing MedCare Pharmacy!</p>
                
                <p><em>- MedCare Pharmacy Team</em></p>
            </div>
            <div class='footer'>
                <p>© 2026 MedCare Pharmacy. All rights reserved.</p>
                <p>This is an automated email. Please do not reply to this address.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@medcare.com" . "\r\n";
    
    mail($customer_email, $subject, $message, $headers);
    
    // Set success message
    $_SESSION['success'] = "Order accepted successfully! Invoice has been generated and sent to customer's email.";
    
    // Redirect to view invoice
    header("Location: view_invoice.php?invoice_id=" . $invoice_id);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    $_SESSION['error'] = "Error accepting order: " . $e->getMessage();
    header("Location: dashboard_pharmacist.php");
    exit();
}
?>