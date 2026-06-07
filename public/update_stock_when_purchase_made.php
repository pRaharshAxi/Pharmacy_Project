<?php
require_once '../config/db_connect.php';

function updateStock($medicine_id, $qty) {
    global $conn;

    if ($qty <= 0) return false;

    $stmt = $conn->prepare(
        "UPDATE medicine 
         SET STOCK_QUANTITY = STOCK_QUANTITY + ?
         WHERE MEDICINE_ID = ?"
    );

    $stmt->bind_param("is", $qty, $medicine_id);
    return $stmt->execute();
}
