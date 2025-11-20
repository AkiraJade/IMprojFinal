<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order #<?= $order['order_number'] ?> Status Update</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; border-bottom: 1px solid #eee; }
        .status-update { margin: 20px 0; padding: 20px; background: #f0f8ff; border-left: 4px solid #9b4de0; }
        .order-details { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .button { display: inline-block; padding: 10px 20px; background: #9b4de0; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Order Status Update</h2>
        <p>Your order status has been updated</p>
    </div>

    <div class="status-update">
        <h3>New Status: <?= htmlspecialchars(ucfirst($status)) ?></h3>
        <p>Updated on: <?= $updateDate ?></p>
    </div>

    <div class="order-details">
        <h3>Order #<?= $order['order_number'] ?></h3>
        <p>Order Date: <?= date('F j, Y', strtotime($order['created_at'])) ?></p>
        <p>Total Amount: ₱<?= number_format($order['total_amount'], 2) ?></p>
    </div>

    <div>
        <p>Hello <?= htmlspecialchars($user['name']) ?>,</p>
        <p>The status of your order #<?= $order['order_number'] ?> has been updated to: <strong><?= htmlspecialchars(ucfirst($status)) ?></strong>.</p>
        
        <?php if ($status === 'shipped' && !empty($trackingInfo)): ?>
        <p>Your order has been shipped with tracking number: <strong><?= htmlspecialchars($trackingInfo) ?></strong></p>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?= BASE_URL ?>/customer/orders.php?order_id=<?= $order['id'] ?>" class="button">View Order Details</a>
        </div>
        
        <p>If you have any questions about your order, please contact our customer service team.</p>
    </div>

    <div class="footer">
        <p>© <?= date('Y') ?> UrbanThrift. All rights reserved.</p>
        <p>This is an automated email, please do not reply directly to this message.</p>
    </div>
</body>
</html>
