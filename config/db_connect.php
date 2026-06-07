<?php
$host = "db";                       //changed from localhost
$username = "pharmacy_user";        // Changed from root
$password = "pharmacy_password";    // Changed from root_password
$database = "medcare";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>