<?php
// Ensure required variables are set
if (!isset($order, $user, $shippedDate)) {
    throw new InvalidArgumentException('Missing required template variables: order, user, or shippedDate');
}

// Helper function to safely output variables
function safe_output($value, $default = '') {
    return isset($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $default;
}

// Set default values for optional variables
$trackingInfo = $trackingInfo ?? null;
$orderNumber = $order['order_number'] ?? 'N/A';
$itemCount = $order['item_count'] ?? 0;
$totalAmount = $order['total_amount'] ?? 0;
$trackingNumber = $order['tracking_number'] ?? null;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Your Order #<?= safe_output($orderNumber) ?> Has Shipped</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            max-width: 600px; 
            margin: 0 auto; 
            padding: 20px; 
            -webkit-font-smoothing: antialiased;
            -webkit-text-size-adjust: none;
        }
        .header { 
            text-align: center; 
            padding: 20px 0; 
            border-bottom: 1px solid #eee; 
            margin-bottom: 20px;
        }
        .shipping-info { 
            margin: 20px 0; 
            padding: 20px; 
            background: #f0f8ff; 
            border-left: 4px solid #4CAF50; 
            border-radius: 4px;
        }
        .tracking-info { 
            margin: 20px 0; 
            padding: 15px; 
            background: #f9f9f9; 
            border-radius: 5px; 
            border-left: 4px solid #2196F3;
        }
        .order-details { 
            margin: 20px 0; 
            padding: 15px; 
            background: #f5f5f5; 
            border-radius: 5px; 
            border-left: 4px solid #9C27B0;
        }
        .button { 
            display: inline-block; 
            padding: 12px 24px; 
            background: #9b4de0; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            margin: 20px 0; 
            font-weight: bold;
            text-align: center;
        }
        .button:hover {
            background: #7e3db8;
            color: white;
            text-decoration: none;
        }
        .footer { 
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 1px solid #eee; 
            font-size: 12px; 
            color: #777; 
            text-align: center; 
        }
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .shipping-info, .tracking-info, .order-details {
                padding: 15px;
                margin: 15px 0;
            }
        }
        a {
            color: #9b4de0;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Your Order is on the Way! 🚚</h2>
        <p>We're excited to let you know your order has been shipped.</p>
        <?php if (!empty($trackingNumber)): ?>
        <p>Tracking #: <?= safe_output($trackingNumber) ?></p>
        <?php endif; ?>
    </div>

    <div class="shipping-info">
        <h3>Shipping Confirmation</h3>
        <p>Your order #<?= safe_output($orderNumber) ?> is now on its way to you.</p>
        <p>Shipped on: <?= safe_output($shippedDate) ?></p>
    </div>

    <?php if (!empty($trackingInfo)): ?>
    <div class="tracking-info">
        <h3>Tracking Information</h3>
        <p>You can track your package using the following tracking number:</p>
        <p style="font-size: 18px; font-weight: bold;"><?= safe_output($trackingInfo) ?></p>
        <p>Please allow 24-48 hours for the tracking information to be available in the carrier's system.</p>
    </div>
    <?php endif; ?>

    <div class="order-details">
        <h3>Order Summary</h3>
        <p>Order #: <?= safe_output($orderNumber) ?></p>
        <p>Items: <?= (int)$itemCount ?> item<?= (int)$itemCount !== 1 ? 's' : '' ?></p>
        <p>Total Amount: ₱<?= number_format((float)$totalAmount, 2) ?></p>
    </div>

    <div>
        <p>Hello <?= safe_output($user['name'] ?? 'Valued Customer') ?>,</p>
        <p>Your order has been shipped and is on its way to you! We hope you love your new items.</p>
        
        <?php if (!empty($trackingInfo) || !empty($trackingNumber)): ?>
        <p>You can track your shipment using the tracking number provided above. Please note that it may take up to 24 hours for tracking information to become available.</p>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 30px 0;">
            <?php if (!empty($order['id'])): ?>
            <a href="<?= safe_output(BASE_URL ?? '') ?>/customer/orders.php?order_id=<?= (int)$order['id'] ?>" class="button">View Order Details</a>
            <?php endif; ?>
        </div>
        
        <p>If you have any questions about your order or need assistance, please don't hesitate to contact our customer service team at <a href="mailto:support@urbanthrift.com">support@urbanthrift.com</a>.</p>
        
        <p>Thank you for shopping with us!</p>
    </div>

    <div class="footer">
        <p>© <?= date('Y') ?> UrbanThrift. All rights reserved.</p>
        <p>This is an automated email, please do not reply directly to this message.</p>
        <p>
            <small>
                <a href="<?= safe_output(BASE_URL ?? '') ?>/preferences" style="color: #777; text-decoration: none;">Email Preferences</a> | 
                <a href="<?= safe_output(BASE_URL ?? '') ?>/privacy" style="color: #777; text-decoration: none;">Privacy Policy</a> | 
                <a href="<?= safe_output(BASE_URL ?? '') ?>/contact" style="color: #777; text-decoration: none;">Contact Us</a>
            </small>
        </p>
    </div>
</body>
</html>
