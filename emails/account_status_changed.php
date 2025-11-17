<?php
/**
 * Account Status Changed Email Template
 * 
 * Variables available:
 * - $user: Array containing user information (id, name, email, etc.)
 * - $isActivated: Boolean indicating if account was activated (true) or deactivated (false)
 * - $changeTime: Formatted date and time of the status change
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Account <?php echo $isActivated ? 'Reactivated' : 'Deactivated'; ?> - UrbanThrift</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; border-bottom: 2px solid #f0f0f0; margin-bottom: 20px; }
        .content { padding: 20px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #f0f0f0; font-size: 12px; color: #777; text-align: center; }
        .button { 
            display: inline-block; 
            background-color: #4CAF50; 
            color: white; 
            padding: 10px 20px; 
            text-decoration: none; 
            border-radius: 4px; 
            margin: 15px 0; 
        }
        .notice { 
            background-color: <?php echo $isActivated ? '#e8f5e9' : '#ffebee'; ?>; 
            border-left: 4px solid <?php echo $isActivated ? '#4CAF50' : '#f44336'; ?>; 
            padding: 12px 20px; 
            margin: 20px 0; 
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Account <?php echo $isActivated ? 'Reactivated' : 'Deactivated'; ?></h1>
    </div>
    
    <div class="content">
        <p>Hello <?php echo htmlspecialchars($user['name'] ?? 'Valued Customer'); ?>,</p>
        
        <div class="notice">
            <h3>Your UrbanThrift account has been <?php echo $isActivated ? 'reactivated' : 'deactivated'; ?>.</h3>
            <p>This action was completed on <?php echo $changeTime; ?>.</p>
        </div>
        
        <?php if ($isActivated): ?>
            <p>Welcome back! Your account has been successfully reactivated. You can now log in and continue shopping with us.</p>
            <div style="text-align: center; margin: 25px 0;">
                <a href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST']; ?>/login.php" class="button">Log In to Your Account</a>
            </div>
        <?php else: ?>
            <p>We're sorry to see you go. Your account has been deactivated as requested.</p>
            <p>If this was a mistake, or if you change your mind, you can reactivate your account by logging in within the next 30 days. After that period, your account and all associated data will be permanently deleted.</p>
            <div style="text-align: center; margin: 25px 0;">
                <a href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST']; ?>" class="button">Visit UrbanThrift</a>
            </div>
        <?php endif; ?>
        
        <p>If you did not request this change, please contact our support team immediately at <a href="mailto:support@urbanthrift.com">support@urbanthrift.com</a> to secure your account.</p>
        
        <p>Thank you for being a part of UrbanThrift<?php echo $isActivated ? ' again' : ''; ?>!</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>&copy; <?php echo date('Y'); ?> UrbanThrift. All rights reserved.</p>
    </div>
</body>
</html>
