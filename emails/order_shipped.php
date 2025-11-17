<?php
// Ensure required variables are set
if (!isset($order, $user, $shippedDate)) {
    throw new InvalidArgumentException('Missing required template variables: order, user, or shippedDate');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Order #<?= htmlspecialchars($order['order_number'] ?? '') ?> Has Shipped</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; border-bottom: 1px solid #eee; }
        .shipping-info { margin: 20px 0; padding: 20px; background: #f0f8ff; border-left: 4px solid #4CAF50; }
        .tracking-info { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .order-details { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        .button { display: inline-block; padding: 10px 20px; background: #9b4de0; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Your Order is on the Way! 🚚</h2>
        <p>We're excited to let you know your order has been shipped.</p>
        <?php if (isset($order['tracking_number'])): ?>
        <p>Tracking #: <?= htmlspecialchars($order['tracking_number']) ?></p>
        <?php endif; ?>
    </div>

    <div class="shipping-info">
        <h3>Shipping Confirmation</h3>
        <p>Your order #<?= $order['order_number'] ?> is now on its way to you.</p>
        <p>Shipped on: <?= $shippedDate ?></p>
    </div>

    <?php if (!empty($trackingInfo)): ?>
    <div class="tracking-info">
        <h3>Tracking Information</h3>
        <p>You can track your package using the following tracking number:</p>
        <p style="font-size: 18px; font-weight: bold;"><?= htmlspecialchars($trackingInfo) ?></p>
        <p>Please allow 24-48 hours for the tracking information to be available in the carrier's system.</p>
    </div>
    <?php endif; ?>

    <div class="order-details">
        <h3>Order Summary</h3>
        <p>Order #: <?= $order['order_number'] ?></p>
        <p>Items: <?= $order['item_count'] ?? 1 ?> item<?= ($order['item_count'] ?? 1) > 1 ? 's' : '' ?></p>
        <p>Total Amount: ₱<?= number_format($order['total_amount'], 2) ?></p>
    </div>

    <div>
        <p>Hello <?= htmlspecialchars($user['name']) ?>,</p>
        <p>Your order has been shipped and is on its way to you! We hope you love your new items.</p>
        
        <?php if (!empty($trackingInfo)): ?>
        <p>You can track your shipment using the tracking number provided above. Please note that it may take up to 24 hours for tracking information to become available.</p>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?= BASE_URL ?>/customer/orders.php?order_id=<?= $order['id'] ?>" class="button">View Order Details</a>
        </div>
        
        <p>If you have any questions about your order or need assistance, please don't hesitate to contact our customer service team.</p>
    </div>

    <div class="footer">
        <p>© <?= date('Y') ?> UrbanThrift. All rights reserved.</p>
        <p>This is an automated email, please do not reply directly to this message.</p>
    </div>
</body>
</html>
