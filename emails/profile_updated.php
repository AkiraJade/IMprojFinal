<?php
/**
 * Profile Updated Email Template
 * 
 * Variables available:
 * - $user: Array containing user information (id, name, email, etc.)
 * - $updateTime: Formatted date and time of the update
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Profile Updated - UrbanThrift</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Profile Updated Successfully</h1>
    </div>
    
    <div class="content">
        <p>Hello <?php echo htmlspecialchars($user['name'] ?? 'Valued Customer'); ?>,</p>
        
        <p>We're writing to inform you that your UrbanThrift account profile was successfully updated on <?php echo $updateTime; ?>.</p>
        
        <p>If you did not make these changes, please contact our support team immediately at <a href="mailto:support@urbanthrift.com">support@urbanthrift.com</a>.</p>
        
        <p>For security reasons, we recommend that you keep your account information secure and never share your password with anyone.</p>
        
        <div style="text-align: center; margin: 25px 0;">
            <a href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST']; ?>/customer/dashboard.php" class="button">View My Profile</a>
        </div>
        
        <p>Thank you for being a valued UrbanThrift customer!</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>&copy; <?php echo date('Y'); ?> UrbanThrift. All rights reserved.</p>
    </div>
</body>
</html>
