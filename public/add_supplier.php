<?php
session_start();
require_once '../config/db_connect.php';

/* PHARMACIST LOGIN CHECK */
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: ../login.php");
    exit();
}

$message = "";

/* HANDLE FORM SUBMISSION */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $company_name = trim($_POST['company_name']);
    $email        = trim($_POST['email']);
    $main_num     = trim($_POST['main_num']);
    $optional_num = trim($_POST['optional_num']);
    $street       = trim($_POST['street']);
    $city         = trim($_POST['city']);
    $state        = trim($_POST['state']);
    $zip_code     = trim($_POST['zip_code']);
    $country      = trim($_POST['country']);

    if (
        empty($company_name) || empty($email) || empty($main_num) ||
        empty($street) || empty($city) || empty($country)
    ) {
        $message = "⚠️ Please fill in all required fields.";
    } else {

        // Generate unique supplier ID
        $supplier_id = 'SUP' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

        // Insert into supplier table
        $stmt = $conn->prepare(
            "INSERT INTO supplier 
            (SUPPLIER_ID, COMPANY_NAME, EMAIL, ZIP_CODE, CITY, STREET, STATE, COUNTRY)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssssss",
            $supplier_id,
            $company_name,
            $email,
            $zip_code,
            $city,
            $street,
            $state,
            $country
        );

        if ($stmt->execute()) {
            $stmt->close();

            // Insert into supplier_contactnum table
            $stmt2 = $conn->prepare(
                "INSERT INTO supplier_contactnum 
                (SUPPLIER_ID, MAIN_NUM, OPTIONAL_NUM)
                VALUES (?, ?, ?)"
            );

            $stmt2->bind_param(
                "sss",
                $supplier_id,
                $main_num,
                $optional_num
            );

            if ($stmt2->execute()) {
                $message = "✅ Supplier added successfully!";
            } else {
                $message = "❌ Error adding supplier contact number.";
            }

            $stmt2->close();
        } else {
            $message = "❌ Error adding supplier.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Supplier - MedCare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #738291;
    margin: 0;
}

.container {
    max-width: 700px;
    margin: 40px auto;
    background: #efefef;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #1d3557;
}

form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

form input {
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}

form input:focus {
    outline: none;
    border-color: #007bff;
}

.full {
    grid-column: 1 / 3;
}

button {
    grid-column: 1 / 3;
    padding: 12px;
    border: none;
    background: #1d3557;
    color: white;
    font-size: 16px;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 10px;
}

button:hover {
    background: #16324f;
}

.message {
    text-align: center;
    margin-bottom: 15px;
    font-weight: bold;
    padding: 10px;
    border-radius: 6px;
    background: #d4edda;
    color: #155724;
}

.back {
    display: block;
    margin-top: 15px;
    text-align: center;
    text-decoration: none;
    color: #007bff;
    font-weight: 600;
}

.back:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<div class="container">
    <h2>Add New Supplier</h2>

    <?php if (!empty($message)): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="company_name" placeholder="Company Name *" class="full" required>
        
        <input type="email" name="email" placeholder="Email *" class="full" required>

        <input type="text" name="main_num" placeholder="Main Phone Number *" required>
        <input type="text" name="optional_num" placeholder="Optional Phone Number">

        <input type="text" name="street" placeholder="Street Address *" required>
        <input type="text" name="city" placeholder="City *" required>

        <input type="text" name="state" placeholder="State">
        <input type="text" name="zip_code" placeholder="ZIP Code">

        <input type="text" name="country" placeholder="Country *" class="full" required>

        <button type="submit">Add Supplier</button>
    </form>

    <a href="view_suppliers.php" class="back">← Back to Suppliers</a>
</div>

</body>
</html>