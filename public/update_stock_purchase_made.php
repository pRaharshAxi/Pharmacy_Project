<?php
require_once '../config/db_connect.php';

/**
 * Increase medicine stock quantity
 *
 * @param string $medicine_id
 * @param int $qty
 * @return bool
 */
function updateStock($medicine_id, $qty) {
    global $conn;

    // Basic validation
    if (empty($medicine_id) || $qty <= 0) {
        return false;
    }

    $stmt = $conn->prepare(
        "UPDATE medicine 
         SET STOCK_QUANTITY = STOCK_QUANTITY + ? 
         WHERE MEDICINE_ID = ?"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("is", $qty, $medicine_id);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}
