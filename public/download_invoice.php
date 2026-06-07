<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if invoice_id is provided
if (!isset($_GET['invoice_id']) || empty($_GET['invoice_id'])) {
    $_SESSION['error'] = "Invoice ID is required.";
    header("Location: dashboard_pharmacist.php");
    exit();
}

$invoice_id = $_GET['invoice_id'];

// Get invoice details
$stmt = $conn->prepare("
    SELECT i.*, u.F_NAME, u.L_NAME, u.EMAIL,
           p.F_NAME AS P_F_NAME, p.L_NAME AS P_L_NAME
    FROM invoice i
    JOIN users u ON i.CUSTOMER_ID = u.USER_ID
    JOIN users p ON i.PHARMACIST_ID = p.USER_ID
    WHERE i.INVOICE_ID = ?
");
$stmt->bind_param("s", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Invoice not found.";
    header("Location: dashboard_pharmacist.php");
    exit();
}

$invoice = $result->fetch_assoc();
$stmt->close();

// Get order items
$stmt = $conn->prepare("
    SELECT m.NAME, oi.QUANTITY, oi.PRICE, (oi.QUANTITY * oi.PRICE) AS SUBTOTAL
    FROM order_item oi
    JOIN medicine m ON oi.MEDICINE_ID = m.MEDICINE_ID
    WHERE oi.ORDER_ID = ?
");
$stmt->bind_param("s", $invoice['ORDER_ID']);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

// Generate HTML content for PDF
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 32px; }
        .invoice-no { font-size: 14px; opacity: 0.9; }
        .info-section { display: flex; gap: 40px; margin: 30px 0; }
        .info-box { flex: 1; }
        .info-box h3 { margin-top: 0; color: #667eea; }
        .info-box p { margin: 5px 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin: 30px 0; }
        table thead { background: #f9fafb; }
        table th { padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
        table td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        .total-section { text-align: right; margin: 20px 0; }
        .total-amount { font-size: 24px; font-weight: bold; color: #667eea; }
        .footer { text-align: center; padding: 20px; border-top: 1px solid #e5e7eb; margin-top: 30px; font-size: 12px; color: #666; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>INVOICE</h1>
            <p class='invoice-no'>Invoice #: " . htmlspecialchars($invoice['INVOICE_ID']) . "</p>
        </div>

        <div class='info-section'>
            <div class='info-box'>
                <h3>Bill To:</h3>
                <p><strong>" . htmlspecialchars($invoice['F_NAME'] . ' ' . $invoice['L_NAME']) . "</strong></p>
                <p>Customer ID: " . htmlspecialchars($invoice['CUSTOMER_ID']) . "</p>
                <p>Email: " . htmlspecialchars($invoice['EMAIL']) . "</p>
            </div>
            <div class='info-box'>
                <h3>Invoice Details:</h3>
                <p><strong>Invoice Date:</strong> " . date('F d, Y', strtotime($invoice['INVOICE_DATE'])) . "</p>
                <p><strong>Order ID:</strong> " . htmlspecialchars($invoice['ORDER_ID']) . "</p>
                <p><strong>Payment Method:</strong> " . htmlspecialchars($invoice['PAYMENT_METHOD']) . "</p>
            </div>
        </div>

        <h3 style='margin-top: 30px;'>Order Items</h3>
        <table>
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th style='text-align: right;'>Subtotal</th>
                </tr>
            </thead>
            <tbody>";

$total = 0;
if ($items && $items->num_rows > 0) {
    while ($item = $items->fetch_assoc()) {
        $subtotal = $item['QUANTITY'] * $item['PRICE'];
        $total += $subtotal;
        $html .= "
                <tr>
                    <td>" . htmlspecialchars($item['NAME']) . "</td>
                    <td>" . htmlspecialchars($item['QUANTITY']) . "</td>
                    <td>Rs. " . number_format($item['PRICE'], 2) . "</td>
                    <td style='text-align: right;'>Rs. " . number_format($subtotal, 2) . "</td>
                </tr>";
    }
}

$html .= "
            </tbody>
        </table>

        <div class='total-section'>
            <p><strong>Total Amount:</strong> <span class='total-amount'>Rs. " . number_format($total, 2) . "</span></p>
        </div>

        <div class='info-section' style='margin-top: 40px;'>
            <div class='info-box'>
                <h3>Processed By:</h3>
                <p><strong>" . htmlspecialchars($invoice['P_F_NAME'] . ' ' . $invoice['P_L_NAME']) . "</strong></p>
                <p>Pharmacist ID: " . htmlspecialchars($invoice['PHARMACIST_ID']) . "</p>
            </div>
        </div>

        <div class='footer'>
            <p>© 2026 MedCare Pharmacy. All rights reserved.</p>
            <p>This is an electronically generated document.</p>
        </div>
    </div>
</body>
</html>";

// Output as HTML file for user to save/print
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="Invoice_' . $invoice_id . '.html"');
echo $html;
exit();
?>
    WHERE oi.ORDER_ID = ?
");
$stmt->bind_param("s", $invoice['ORDER_ID']);
$stmt->execute();
$items = $stmt->get_result();

// Create PDF using TCPDF
try {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('MedCare Pharmacy');
    $pdf->SetAuthor('MedCare');
    $pdf->SetTitle('Invoice ' . $invoice_id);
    $pdf->SetSubject('Invoice');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Build HTML content
    $html = '
    <style>
        h1 { color: #2563eb; font-size: 24px; }
        h2 { color: #333; font-size: 18px; }
        h3 { color: #666; font-size: 14px; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; }
        .header-table td { padding: 5px; }
        .info-box { background-color: #f9fafb; padding: 10px; margin: 10px 0; }
        .items-table th { background-color: #2563eb; color: white; padding: 10px; text-align: left; }
        .items-table td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .total-row { padding: 5px; font-weight: bold; }
        .grand-total { font-size: 16px; color: #2563eb; border-top: 2px solid #2563eb; padding-top: 10px; }
    </style>
    
    <h1>MedCare</h1>
    <p>Pharmacy Management System<br>Email: info@medcare.lk | Phone: 0771234567</p>
    <hr>
    
    <table class="header-table">
        <tr>
            <td width="50%"><h2>INVOICE</h2></td>
            <td width="50%" align="right">
                <strong>Invoice #:</strong> ' . htmlspecialchars($invoice_id) . '<br>
                <strong>Date:</strong> ' . date('M d, Y', strtotime($invoice['INVOICE_DATE'])) . '<br>
                <strong>Order #:</strong> ' . htmlspecialchars($invoice['ORDER_ID']) . '
            </td>
        </tr>
    </table>
    
    <br><br>
    
    <table>
        <tr>
            <td width="50%" class="info-box">
                <h3>Bill To:</h3>
                <strong>' . htmlspecialchars($invoice['F_NAME'] . ' ' . $invoice['L_NAME']) . '</strong><br>
                Customer ID: ' . htmlspecialchars($invoice['CUSTOMER_ID']) . '<br>
                Email: ' . htmlspecialchars($invoice['EMAIL']) . '<br>
                Phone: ' . htmlspecialchars($invoice['CONTACT_NUM']) . '
            </td>
            <td width="50%" class="info-box">
                <h3>Processed By:</h3>
                <strong>' . htmlspecialchars($invoice['P_F_NAME'] . ' ' . $invoice['P_L_NAME']) . '</strong><br>
                Pharmacist ID: ' . htmlspecialchars($invoice['PHARMACIST_ID']) . '<br>
                Payment Method: ' . htmlspecialchars($invoice['PAYMENT_METHOD']) . '
            </td>
        </tr>
    </table>
    
    <br><br>
    
    <table class="items-table" cellpadding="8">
        <thead>
            <tr>
                <th width="40%">Medicine Name</th>
                <th width="20%">Quantity</th>
                <th width="20%">Unit Price</th>
                <th width="20%" align="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>';
    
    $subtotal = 0;
    if ($items && $items->num_rows > 0) {
        while ($item = $items->fetch_assoc()) {
            $item_subtotal = $item['QUANTITY'] * $item['PRICE'];
            $subtotal += $item_subtotal;
            $html .= '
            <tr>
                <td>' . htmlspecialchars($item['MEDICINE_NAME']) . '</td>
                <td>' . $item['QUANTITY'] . '</td>
                <td>Rs. ' . number_format($item['PRICE'], 2) . '</td>
                <td align="right">Rs. ' . number_format($item_subtotal, 2) . '</td>
            </tr>';
        }
    }
    
    $html .= '
        </tbody>
    </table>
    
    <br><br>
    
    <table width="100%">
        <tr>
            <td width="70%"></td>
            <td width="30%">
                <table width="100%" class="info-box">
                    <tr class="total-row">
                        <td>Subtotal:</td>
                        <td align="right">Rs. ' . number_format($invoice['TOTAL_AMOUNT'], 2) . '</td>
                    </tr>
                    <tr class="total-row">
                        <td>Tax (0%):</td>
                        <td align="right">Rs. 0.00</td>
                    </tr>
                    <tr class="total-row grand-total">
                        <td><strong>Total:</strong></td>
                        <td align="right"><strong>Rs. ' . number_format($invoice['TOTAL_AMOUNT'], 2) . '</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <br><br>
    <p style="text-align: center; color: #666; font-size: 10px;">
        Thank you for your business!<br>
        This is a computer-generated invoice and does not require a signature.
    </p>
    ';
    
    // Print text using writeHTMLCell()
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('Invoice_' . $invoice_id . '.pdf', 'D');
    
} catch (Exception $e) {
    // If TCPDF is not available, provide a simple HTML download alternative
    $_SESSION['error'] = "PDF generation is not available. Please use Print function instead. Error: " . $e->getMessage();
    header("Location: view_invoice.php?invoice_id=" . $invoice_id);
    exit();
}

$stmt->close();
?>