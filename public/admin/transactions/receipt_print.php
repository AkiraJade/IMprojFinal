<?php
// Include config
require_once __DIR__ . '/../../../includes/config.php';

// Check if user is logged in and has the right role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'customer')) {
    header("Location: " . (isset($_SESSION['role']) && $_SESSION['role'] === 'customer' ? '../../login.php' : '../../../login.php'));
    exit();
}

// Validate order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, c.name AS customer_name, c.contact_number, c.email, c.address
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_q = $stmt->get_result();
$order = $order_q->fetch_assoc();

if (!$order) {
    header("Location: read.php");
    exit();
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name AS product_name
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_q = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= $order_id ?> - UrbanThrift</title>
    <style>
        @media print {
            .no-print { display: none; }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            padding: 20px;
            background: #fff;
            color: #000;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border: 2px solid #000;
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .receipt-header h1 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .receipt-header p {
            margin: 3px 0;
            font-size: 14px;
        }
        
        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-section h3 {
            font-size: 14px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .info-section p {
            font-size: 13px;
            margin: 5px 0;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th {
            background: #000;
            color: #fff;
            padding: 10px;
            text-align: left;
            font-size: 13px;
        }
        
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        
        .items-table tr:last-child td {
            border-bottom: 2px solid #000;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .receipt-total {
            text-align: right;
            margin-top: 20px;
        }
        
        .receipt-total h2 {
            font-size: 24px;
            margin-top: 10px;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #000;
            font-size: 12px;
        }
        
        .print-button {
            text-align: center;
            margin: 20px 0;
        }
        
        .print-button button {
            background: #7B1FA2;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .print-button button:hover {
            background: #5E1580;
        }
    </style>
</head>
<body>

<div class="print-button no-print">
    <button onclick="window.print()">🖨️ Print Receipt</button>
    <button onclick="window.close()" style="background: #666;">Close</button>
</div>

<div class="receipt-container">
    <div class="receipt-header">
        <h1>🛍️ URBANTHRIFT</h1>
        <p><strong>Thrift Clothing Shop</strong></p>
        <p>123 Fashion Street, Manila, Philippines</p>
        <p>Tel: +63 917 123 4567 | Email: info@urbanthrift.com</p>
    </div>
    
    <div class="receipt-info">
        <div class="info-section">
            <h3>RECEIPT INFORMATION</h3>
            <p><strong>Receipt #:</strong> <?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></p>
            <p><strong>Date:</strong> <?= date('F d, Y h:i A', strtotime($order['order_date'])) ?></p>
            <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
        </div>
        
        <div class="info-section">
            <h3>CUSTOMER INFORMATION</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($order['contact_number'] ?? 'N/A') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($order['email'] ?? 'N/A') ?></p>
        </div>
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th>ITEM</th>
                <th class="text-right">QTY</th>
                <th class="text-right">UNIT PRICE</th>
                <th class="text-right">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($item = $items_q->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="text-right"><?= $item['quantity'] ?></td>
                <td class="text-right">₱<?= number_format($item['price'], 2) ?></td>
                <td class="text-right">₱<?= number_format($item['quantity'] * $item['price'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <div class="receipt-total">
        <p style="font-size: 18px;"><strong>TOTAL AMOUNT</strong></p>
        <h2>₱<?= number_format($order['total_amount'], 2) ?></h2>
    </div>
    
    <div class="receipt-footer">
        <p><strong>Thank you for shopping at UrbanThrift!</strong></p>
        <p>For inquiries, please contact us at info@urbanthrift.com</p>
        <p style="margin-top: 10px; font-size: 11px;">This is a computer-generated receipt and does not require_once a signature.</p>
    </div>
</div>

</body>
</html>
