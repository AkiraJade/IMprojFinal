<?php

// Include PHPMailer classes manually if autoloader is not available
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
}

class EmailService {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        // Use constants from config if available, otherwise use defaults
        $this->smtpHost = defined('MAIL_HOST') ? MAIL_HOST : 'sandbox.smtp.mailtrap.io';
        $this->smtpPort = defined('MAIL_PORT') ? MAIL_PORT : 2525;
        $this->smtpUsername = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
        $this->smtpPassword = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
        $this->fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'noreply@urbanthrift.com';
        $this->fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'UrbanThrift';
        
        // Override with environment variables if set
        if (getenv('MAILTRAP_USERNAME')) {
            $this->smtpUsername = getenv('MAILTRAP_USERNAME');
        }
        if (getenv('MAILTRAP_PASSWORD')) {
            $this->smtpPassword = getenv('MAILTRAP_PASSWORD');
        }
    }


    private function initMailer() {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';
            
            // Sender info
            $mail->setFrom($this->fromEmail, $this->fromName);
            
            return $mail;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            throw new Exception("Failed to initialize mailer: " . $e->getMessage());
        }
    }

    private function renderTemplate($template, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/../emails/$template.php";
        return ob_get_clean();
    }

    public function sendOrderConfirmation($order, $user) {
        try {
            $mail = $this->initMailer();
            
            // Recipient
            $mail->addAddress($user['email'], $user['name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Order Confirmation - #' . $order['order_number'];
            
            // Render email template
            $mail->Body = $this->renderTemplate('order_confirmation', [
                'user' => $user,
                'order' => $order,
                'orderDate' => date('F j, Y', strtotime($order['created_at']))
            ]);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Order Confirmation Email Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send order status update email
     * 
     * @param array $order Order data with required keys: order_number, total_amount
     * @param array $user User data with required keys: email, name
     * @param string $status New order status
     * @return bool True if email was sent successfully, false otherwise
     * @throws InvalidArgumentException If required parameters are missing
     */
    public function sendOrderStatusUpdate($order, $user, $status) {
        // Validate required parameters
        if (empty($order['order_number']) || !isset($order['total_amount'])) {
            throw new InvalidArgumentException('Order must contain order_number and total_amount');
        }
        
        if (empty($user['email']) || empty($user['name'])) {
            throw new InvalidArgumentException('User must contain email and name');
        }
        
        try {
            $mail = $this->initMailer();
            
            // Recipient
            $mail->addAddress($user['email'], $user['name']);
            
            // Standardize order data
            $orderData = array_merge([
                'order_number' => '',
                'total_amount' => 0,
                'item_count' => 1,
                'tracking_number' => $order['tracking_number'] ?? null
            ], $order);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Order #' . $orderData['order_number'] . ' - Status Update';
            
            // Render email template
            $mail->Body = $this->renderTemplate('order_status_update', [
                'user' => [
                    'name' => $user['name'],
                    'email' => $user['email']
                ],
                'order' => $orderData,
                'status' => $status,
                'updateDate' => date('F j, Y'),
                'trackingInfo' => $orderData['tracking_number']
            ]);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            $errorMsg = sprintf(
                'Order Status Update Email Error (Order #%s to %s): %s',
                $order['order_number'] ?? 'unknown',
                $user['email'] ?? 'unknown',
                $e->getMessage()
            );
            error_log($errorMsg);
            return false;
        }
    }

    /**
     * Send order shipped notification
     * 
     * @param array $order Order data with required keys: order_number, total_amount
     * @param array $user User data with required keys: email, name
     * @param string|null $trackingInfo Optional tracking information
     * @return bool True if email was sent successfully, false otherwise
     * @throws InvalidArgumentException If required parameters are missing
     */
    public function sendShippedNotification($order, $user, $trackingInfo = null) {
        // Validate required parameters
        if (empty($order['order_number']) || !isset($order['total_amount'])) {
            throw new InvalidArgumentException('Order must contain order_number and total_amount');
        }
        
        if (empty($user['email']) || empty($user['name'])) {
            throw new InvalidArgumentException('User must contain email and name');
        }
        
        try {
            $mail = $this->initMailer();
            
            // Recipient
            $mail->addAddress($user['email'], $user['name']);
            
            // Standardize order data
            $orderData = array_merge([
                'order_number' => '',
                'total_amount' => 0,
                'item_count' => 1,
                'tracking_number' => $trackingInfo
            ], $order);
            
            // If tracking info was passed separately, ensure it's in the order data
            if ($trackingInfo !== null) {
                $orderData['tracking_number'] = $trackingInfo;
            }
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Order #' . $orderData['order_number'] . ' Has Shipped';
            
            // Render email template
            $mail->Body = $this->renderTemplate('order_shipped', [
                'user' => [
                    'name' => $user['name'],
                    'email' => $user['email']
                ],
                'order' => $orderData,
                'trackingInfo' => $trackingInfo,
                'shippedDate' => date('F j, Y')
            ]);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            $errorMsg = sprintf(
                'Shipped Notification Email Error (Order #%s to %s): %s',
                $order['order_number'] ?? 'unknown',
                $user['email'] ?? 'unknown',
                $e->getMessage()
            );
            error_log($errorMsg);
            return false;
        }
    }

    public function sendProfileUpdateNotification($user) {
        try {
            $mail = $this->initMailer();
            
            // Recipient
            $mail->addAddress($user['email'], $user['name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Profile Has Been Updated';
            
            // Render email template
            $mail->Body = $this->renderTemplate('profile_updated', [
                'user' => $user,
                'updateTime' => date('F j, Y \a\t g:i a')
            ]);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Profile Update Email Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendAccountStatusNotification($user, $isActivated = true) {
        try {
            error_log("Initializing mailer for account status notification to: " . $user['email']);
            $mail = $this->initMailer();
            
            // Recipient
            $mail->addAddress($user['email'], $user['name']);
            
            // Content
            $mail->isHTML(true);
            $subject = $isActivated ? 'Your Account Has Been Reactivated' : 'Your Account Has Been Deactivated';
            $mail->Subject = $subject;
            
            error_log("Rendering email template for: " . $subject);
            
            // Render email template
            $templateVars = [
                'user' => $user,
                'isActivated' => $isActivated,
                'changeTime' => date('F j, Y \a\t g:i a')
            ];
            
            $mail->Body = $this->renderTemplate('account_status_changed', $templateVars);
            
            if (empty($mail->Body)) {
                error_log("Error: Empty email body generated for template 'account_status_changed'");
                return false;
            }
            
            error_log("Attempting to send email to: " . $user['email']);
            $result = $mail->send();
            
            if ($result) {
                error_log("Successfully sent email to: " . $user['email']);
            } else {
                error_log("Failed to send email. Error: " . $mail->ErrorInfo);
            }
            
            return $result;
        } catch (Exception $e) {
            $errorMsg = "Account Status Email Error to " . $user['email'] . ": " . $e->getMessage();
            error_log($errorMsg);
            error_log("Stack trace: " . $e->getTraceAsString());
            
            if (isset($mail) && $mail instanceof PHPMailer\PHPMailer\PHPMailer) {
                error_log("PHPMailer Error Info: " . $mail->ErrorInfo);
            }
            
            return false;
        }
    }
}
