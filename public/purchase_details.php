<?php
session_start();
require_once '../config/db_connect.php';

// Check pharmacist login
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'PHARMACIST') {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_suppliers.php");
    exit();
}

$supplier_id   = $_POST['supplier_id']   ?? '';
$pharmacist_id = $_POST['pharmacist_id'] ?? '';
$quantities    = $_POST['quantities']    ?? [];
$costs         = $_POST['costs']         ?? [];

// Filter out medicines with quantity 0
$selected_medicines = [];
$total_amount = 0;

foreach ($quantities as $medicine_id => $quantity) {
    $qty  = intval($quantity);
    $cost = floatval($costs[$medicine_id] ?? 0);

    if ($qty > 0 && $cost > 0) {
        $selected_medicines[] = [
            'medicine_id' => $medicine_id,
            'quantity'    => $qty,
            'unit_cost'   => $cost
        ];
        $total_amount += ($qty * $cost);
    }
}

// Check if at least one medicine was selected
if (empty($selected_medicines)) {
    $_SESSION['message']      = 'Please select at least one medicine with quantity greater than 0.';
    $_SESSION['message_type'] = 'error';
    header("Location: create_purchase.php?supplier_id=" . $supplier_id);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {

    // ── Generate unique Purchase ID ──────────────────────────────────────────
    $result  = $conn->query("SELECT PURCHASE_ID FROM purchase ORDER BY PURCHASE_ID DESC LIMIT 1");

    if ($result && $result->num_rows > 0) {
        $last_id = $result->fetch_assoc()['PURCHASE_ID'];
        // Works for both 'PUR0001' and plain numeric formats
        if (preg_match('/(\d+)$/', $last_id, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }
    } else {
        $number = 1;
    }

    $purchase_id   = 'PUR' . str_pad($number, 4, '0', STR_PAD_LEFT);
    $purchase_date = date('Y-m-d');

    // ── Insert into purchase table ───────────────────────────────────────────
    $purchase_stmt = $conn->prepare(
        "INSERT INTO purchase (PURCHASE_ID, PURCHASE_DATE, SUPPLIER_ID, PHARMACIST_ID, TOTAL_AMOUNT)
         VALUES (?, ?, ?, ?, ?)"
    );
    if (!$purchase_stmt) {
        throw new Exception('Failed to prepare purchase: ' . $conn->error);
    }
    $purchase_stmt->bind_param("ssssd", $purchase_id, $purchase_date, $supplier_id, $pharmacist_id, $total_amount);
    if (!$purchase_stmt->execute()) {
        throw new Exception("Failed to create purchase: " . $purchase_stmt->error);
    }
    $purchase_stmt->close();

    // ── Get last purchase_item_id for sequential IDs ─────────────────────────
    $item_result = $conn->query("SELECT PURCHASE_ITEM_ID FROM purchase_item ORDER BY PURCHASE_ITEM_ID DESC LIMIT 1");
    $last_item_id = ($item_result && $item_result->num_rows > 0)
        ? intval($item_result->fetch_assoc()['PURCHASE_ITEM_ID'])
        : 0;

    // ── Prepare reusable statements ──────────────────────────────────────────
    $item_stmt = $conn->prepare(
        "INSERT INTO purchase_item (PURCHASE_ITEM_ID, PURCHASE_ID, QUANTITY, MEDICINE_ID, UNIT_COST)
         VALUES (?, ?, ?, ?, ?)"
    );
    if (!$item_stmt) throw new Exception('Failed to prepare purchase_item: ' . $conn->error);

    $pharmacist_item_stmt = $conn->prepare(
        "INSERT INTO pharmacist_purchaseitem (PHARMACIST_ID, PURCHASE_ITEM_ID) VALUES (?, ?)"
    );
    if (!$pharmacist_item_stmt) throw new Exception('Failed to prepare pharmacist_purchaseitem: ' . $conn->error);

    $update_stock_stmt = $conn->prepare(
        "UPDATE medicine SET STOCK_QUANTITY = STOCK_QUANTITY + ? WHERE MEDICINE_ID = ?"
    );
    if (!$update_stock_stmt) throw new Exception('Failed to prepare stock update: ' . $conn->error);

    // ── Insert each selected medicine ────────────────────────────────────────
    foreach ($selected_medicines as $item) {
        $purchase_item_id = ++$last_item_id;
        $medicine_id      = $item['medicine_id'];
        $quantity         = $item['quantity'];
        $unit_cost        = $item['unit_cost'];

        // Insert purchase_item row
        $item_stmt->bind_param("isisd", $purchase_item_id, $purchase_id, $quantity, $medicine_id, $unit_cost);
        if (!$item_stmt->execute()) {
            throw new Exception("Failed to insert purchase item: " . $item_stmt->error);
        }

        // Link pharmacist to purchase item
        $pharmacist_item_stmt->bind_param("si", $pharmacist_id, $purchase_item_id);
        if (!$pharmacist_item_stmt->execute()) {
            throw new Exception("Failed to link pharmacist to purchase item: " . $pharmacist_item_stmt->error);
        }

        // Update medicine stock
        $update_stock_stmt->bind_param("is", $quantity, $medicine_id);
        if (!$update_stock_stmt->execute()) {
            throw new Exception("Failed to update medicine stock: " . $update_stock_stmt->error);
        }
    }

    // ── Close reusable statements ────────────────────────────────────────────
    $item_stmt->close();
    $pharmacist_item_stmt->close();
    $update_stock_stmt->close();

    // ── Insert/Update supplier_pharmacist relationship ───────────────────────
    $check = $conn->prepare("SELECT 1 FROM supplier_pharmacist WHERE SUPPLIER_ID = ? AND PHARMACIST_ID = ?");
    if (!$check) throw new Exception('Failed to prepare supplier_pharmacist check: ' . $conn->error);
    $check->bind_param('ss', $supplier_id, $pharmacist_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if (!$exists) {
        $rel_stmt = $conn->prepare("INSERT INTO supplier_pharmacist (SUPPLIER_ID, PHARMACIST_ID) VALUES (?, ?)");
        if (!$rel_stmt) throw new Exception('Failed to prepare supplier_pharmacist insert: ' . $conn->error);
        $rel_stmt->bind_param('ss', $supplier_id, $pharmacist_id);
        if (!$rel_stmt->execute()) throw new Exception('Failed to record relationship: ' . $rel_stmt->error);
        $rel_stmt->close();
    }

    // ── Commit ───────────────────────────────────────────────────────────────
    $conn->commit();

    $_SESSION['message']      = 'Purchase created successfully! Purchase ID: ' . $purchase_id
                                . ' | Total: Rs. ' . number_format($total_amount, 2)
                                . ' | Items: ' . count($selected_medicines);
    $_SESSION['message_type'] = 'success';

    header("Location: purchase_details.php?id=" . $purchase_id);
    exit();

} catch (Exception $e) {
    $conn->rollback();

    $_SESSION['message']      = 'Error creating purchase: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';

    header("Location: create_purchase.php?supplier_id=" . $supplier_id);
    exit();
}
?>