<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Function to handle the response
function handleResponse($success, $message = '', $data = [], $isAjax = true) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit();
    } else {
        // For non-AJAX requests, use session flash message and redirect
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $success ? 'success' : 'error';
        
        // Redirect to return_url or cart page
        $redirect = $data['redirect'] ?? BASE_URL . '/cart/cart.php';
        header('Location: ' . $redirect);
        exit();
    }
}

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    handleResponse(false, 'Please login to add items to cart', 
        ['redirect' => BASE_URL . '/login.php'], 
        $isAjax
    );
}

// Check if product ID is provided and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    handleResponse(false, 'Invalid product', [], $isAjax);
}

$product_id = intval($_GET['id']);
$customer_id = $_SESSION['user_id'];
$quantity = isset($_GET['quantity']) ? max(1, intval($_GET['quantity'])) : 1;

// Debug information
error_log("Adding to cart - Product ID: $product_id, Quantity: $quantity, Customer ID: $customer_id");

// Start transaction
$conn->begin_transaction();

try {
    // Check if product exists and has enough stock
    $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        throw new Exception('Product not found or out of stock');
    }
    
    // Check current cart quantity
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE product_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $product_id, $customer_id);
    $stmt->execute();
    $cart_item = $stmt->get_result()->fetch_assoc();
    
    $new_quantity = $cart_item ? ($cart_item['quantity'] + $quantity) : $quantity;
    
    // Check stock availability
    if ($new_quantity > $product['stock']) {
        throw new Exception('Not enough stock available. Only ' . $product['stock'] . ' items left in stock.');
    }
    
    // Add or update cart item
    if ($cart_item) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $customer_id, $product_id, $quantity);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update cart. Please try again.');
    }
    
    $conn->commit();
    
    // Prepare response data
    $responseData = [
        'message' => 'Product added to cart successfully!',
        'cartCount' => $new_quantity
    ];
    
    // Add redirect URL if provided and valid
    if (isset($_GET['return_url'])) {
        $return_url = urldecode($_GET['return_url']);
        if (strpos($return_url, BASE_URL) === 0 || strpos($return_url, '/') === 0) {
            $responseData['redirect'] = $return_url;
        } else {
            error_log("Invalid return URL: $return_url");
        }
    }
    
    handleResponse(true, $responseData['message'], $responseData, $isAjax);
    
} catch (Exception $e) {
    $conn->rollback();
    handleResponse(false, $e->getMessage(), [], $isAjax);
}
?>
