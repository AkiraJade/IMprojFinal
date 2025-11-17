<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation - #<?= $order['order_number'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; border-bottom: 1px solid #eee; }
        .logo { max-width: 200px; margin-bottom: 20px; }
        .order-details { margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 5px; }
        .button { display: inline-block; padding: 10px 20px; background: #9b4de0; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Thank You for Your Order!</h1>
        <p>Your order has been received and is being processed.</p>
    </div>

    <div class="order-details">
        <h2>Order #<?= $order['order_number'] ?></h2>
        <p>Order Date: <?= $orderDate ?></p>
        <p>Total Amount: ₱<?= number_format($order['total_amount'], 2) ?></p>
        <p>Payment Method: <?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?></p>
    </div>

    <div>
        <p>Hello <?= htmlspecialchars($user['name']) ?>,</p>
        <p>We've received your order and it's now being processed. You'll receive another email when your order is on its way.</p>
        <p>You can check the status of your order anytime by logging into your account.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?= BASE_URL ?>/customer/orders.php?order_id=<?= $order['id'] ?>" class="button">View Order Status</a>
        </div>
        
        <p>If you have any questions about your order, please don't hesitate to contact our customer service team.</p>
    </div>

    <div class="footer">
        <p>© <?= date('Y') ?> UrbanThrift. All rights reserved.</p>
        <p>This is an automated email, please do not reply directly to this message.</p>
    </div>
</body>
</html>
