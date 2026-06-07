<?php
session_start();
require_once '../config/db_connect.php';

// Admin check
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

$medicine_id = $_GET['id'] ?? '';

if (empty($medicine_id)) {
    $_SESSION['error'] = "Invalid medicine ID.";
    header("Location: manage_medicines.php");
    exit();
}

// Check if medicine exists
$stmt = $conn->prepare("SELECT NAME FROM medicine WHERE MEDICINE_ID = ?");
$stmt->bind_param("s", $medicine_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Medicine not found.";
    header("Location: manage_medicines.php");
    exit();
}

$medicine = $result->fetch_assoc();
$stmt->close();

// Check if medicine is used in any orders (optional - prevents deletion if in orders)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_item WHERE MEDICINE_ID = ?");
$stmt->bind_param("s", $medicine_id);
$stmt->execute();
$order_check = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($order_check['count'] > 0) {
    $_SESSION['error'] = "Cannot delete medicine '{$medicine['NAME']}' because it's used in {$order_check['count']} order(s). Consider setting stock to 0 instead.";
    header("Location: manage_medicines.php");
    exit();
}

// Delete the medicine
$stmt = $conn->prepare("DELETE FROM medicine WHERE MEDICINE_ID = ?");
$stmt->bind_param("s", $medicine_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Medicine '{$medicine['NAME']}' deleted successfully.";
} else {
    $_SESSION['error'] = "Error deleting medicine: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: manage_medicines.php");
exit();
?>