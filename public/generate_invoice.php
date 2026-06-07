<?php
include 'db.php';

$order_id      = $_GET['order_id'];
$paymentMethod = "CASH";
$pharmacist_id = "u002"; // example pharmacist
$invoice_id    = "INV" . rand(1000,9999);
$invoice_date  = date("Y-m-d");

/* Get order total & customer */
$sql = "SELECT CUSTOMER_ID, TOTAL FROM orders WHERE ORDER_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

$total       = $order['TOTAL'];
$customer_id = $order['CUSTOMER_ID'];

/* Call stored procedure create_invoice */
$stmt = $conn->prepare("CALL create_invoice(?,?,?,?,?,?,?)");
$stmt->bind_param(
    "sssds ss",
    $invoice_id,
    $paymentMethod,
    $invoice_date,
    $order_id,
    $total,
    $pharmacist_id,
    $customer_id
);

$stmt->execute();

/* Update order status */
$stmt = $conn->prepare("CALL update_order_status(?, 'COMPLETED')");
$stmt->bind_param("s", $order_id);
$stmt->execute();

echo "Invoice Generated Successfully! <br>";
echo "<a href='view_invoice.php?invoice_id=$invoice_id'>View Invoice</a>";
?>
