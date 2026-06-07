<?php
session_start();
require_once '../config/db_connect.php';

// Admin login check
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['delete']) && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    // Only allow deleting pharmacist or customer
    $stmt = $conn->prepare("SELECT ROLE FROM users WHERE USER_ID = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $role = $result->fetch_assoc()['ROLE'];
        if ($role === 'PHARMACIST' || $role === 'CUSTOMER') {
            $conn->query("DELETE FROM users WHERE USER_ID = '" . $conn->real_escape_string($user_id) . "'");
            $_SESSION['success'] = "User deleted successfully.";
        } else {
            $_SESSION['error'] = "Cannot delete admin user.";
        }
    }
    header("Location: manage_users.php");
    exit();
}
?>