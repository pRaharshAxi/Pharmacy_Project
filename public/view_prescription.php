<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in and is pharmacist
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("HTTP/1.0 404 Not Found");
    echo "Order ID is required.";
    exit();
}

$order_id = $_GET['order_id'];

// Get prescription path from database
$stmt = $conn->prepare("SELECT PRESCRIPTION_PATH FROM orders WHERE ORDER_ID = ?");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("HTTP/1.0 404 Not Found");
    echo "Order not found.";
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

if (!$order['PRESCRIPTION_PATH']) {
    header("HTTP/1.0 404 Not Found");
    echo "No prescription found for this order.";
    exit();
}

// Construct full file path from the public directory
// The path stored is relative like: ../uploads/prescriptions/filename.pdf
$file_path = __DIR__ . '/' . $order['PRESCRIPTION_PATH'];

// Normalize the path
$file_path = realpath($file_path);

// Verify file exists
if (!$file_path || !file_exists($file_path)) {
    header("HTTP/1.0 404 Not Found");
    echo "Prescription file not found at: " . htmlspecialchars($order['PRESCRIPTION_PATH']);
    exit();
}

// Get file information
$file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$file_size = filesize($file_path);

// Set appropriate content type
$content_types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];

$content_type = $content_types[$file_extension] ?? 'application/octet-stream';

// Set headers
header('Content-Type: ' . $content_type);
header('Content-Length: ' . $file_size);
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');

// Output file
readfile($file_path);
exit();
