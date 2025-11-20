<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging setup
$debugLogFile = __DIR__ . '/update_debug.log';
file_put_contents($debugLogFile, "[" . date('Y-m-d H:i:s') . "] Script started\n", FILE_APPEND);

function debugLog($message) {
    global $debugLogFile;
    $timestamp = date('Y-m-d H:i:s');
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $caller = isset($backtrace[0]) ? basename($backtrace[0]['file']) . ':' . $backtrace[0]['line'] : '';
    $logMessage = "[$timestamp] [$caller] $message\n";
    file_put_contents($debugLogFile, $logMessage, FILE_APPEND);
}

require_once __DIR__ . '/../../../includes/config.php';
checkAdmin();

debugLog("Session data: " . print_r($_SESSION, true));

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$id = intval($_GET['id']);
$msg = "";

// Debug: Log the current request
error_log("Update product request for ID: " . $id);

// Fetch all suppliers
$suppliers = [];
try {
    $supplierResult = $conn->query("SELECT id, name FROM suppliers ORDER BY name");
    if ($supplierResult) {
        $suppliers = $supplierResult->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching suppliers: " . $e->getMessage());
}

// Fetch existing product data with supplier information
$stmt = $conn->prepare("
    SELECT p.*, sp.supplier_id, s.name as supplier_name 
    FROM products p
    LEFT JOIN supplier_products sp ON p.id = sp.product_id AND sp.is_primary = 1
    LEFT JOIN suppliers s ON sp.supplier_id = s.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: read.php");
    exit();
}

// Log basic request info for debugging
error_log("Processing update for product ID: " . $id);

// Update processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    debugLog("=== FORM SUBMITTED ===");
    debugLog("POST data: " . print_r($_POST, true));
    debugLog("FILES data: " . print_r($_FILES, true));
    
    // Log current directory and permissions
    debugLog("Current directory: " . __DIR__);
    debugLog("Upload directory exists: " . (is_dir('../../uploads') ? 'Yes' : 'No'));
    debugLog("Upload directory writable: " . (is_writable('../../uploads') ? 'Yes' : 'No'));
    
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $size = isset($_POST['size']) ? trim($_POST['size']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    $condition_type = isset($_POST['condition_type']) ? trim($_POST['condition_type']) : '';
    $imageName = $product['image']; // default: no image change
    $supplier_id = (isset($_POST['supplier_id']) && $_POST['supplier_id'] !== '') ? intval($_POST['supplier_id']) : null;
    
    // Debug output - removed for cleaner UI
    // echo '<div style="background: #e2f0fd; padding: 10px; margin: 10px 0; border: 1px solid #b8daff; border-radius: 4px;">';
    // echo '<h4>Processed Form Data:</h4>';
    // echo '<pre>';
    // echo "Name: $name\n";
    // echo "Brand: $brand\n";
    // echo "Category: $category\n";
    // echo "Size: $size\n";
    // echo "Price: $price\n";
    // echo "Stock: $stock\n";
    // echo "Condition: $condition_type\n";
    // echo "Supplier ID: $supplier_id\n";
    // echo "Current Image: $imageName\n";
    // echo '</pre>';
    // echo '</div>';
    
    // Debug log form data
    error_log("Form data: " . print_r([
        'name' => $name,
        'brand' => $brand,
        'category' => $category,
        'size' => $size,
        'price' => $price,
        'stock' => $stock,
        'condition' => $condition_type,
        'supplier_id' => $supplier_id
    ], true));

    // Validate inputs
    $validationErrors = [];
    if (empty($name)) $validationErrors[] = "Product name is required";
    if (empty($category)) $validationErrors[] = "Category is required";
    if ($price <= 0) $validationErrors[] = "Price must be greater than 0";
    if ($stock < 0) $validationErrors[] = "Stock cannot be negative";
    
    if (!empty($validationErrors)) {
        $msg = "❌ " . implode(", ", $validationErrors);
        debugLog("Validation failed: " . $msg);
    } else {
        // Start transaction
        $conn->begin_transaction();
        $success = false;
        
        try {
            // Handle file upload if a new image is provided
            if (!empty($_FILES["image"]["name"]) && $_FILES["image"]["error"] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES["image"]["type"];
                
                if (in_array($file_type, $allowed_types)) {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = __DIR__ . '/../../../public/uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
                    $imageName = uniqid() . '.' . $file_extension;
                    $target = $upload_dir . $imageName;
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {
                        // Delete old image if it exists and is not the default
                        if (!empty($product['image']) && $product['image'] !== 'default.jpg' && file_exists($upload_dir . $product['image'])) {
                            @unlink($upload_dir . $product['image']);
                        }
                    } else {
                        throw new Exception("Failed to move uploaded file");
                    }
                } else {
                    throw new Exception("Invalid file type. Only JPG, PNG, GIF, and WEBP images are allowed.");
                }
            }
            
            // Update the product with supplier_id directly
            $updateQuery = "UPDATE products SET 
                name = ?,
                brand = ?,
                category = ?,
                size = ?,
                price = ?,
                stock = ?,
                condition_type = ?,
                image = ?,
                supplier_id = ?
                WHERE id = ?";
            
            debugLog("Preparing query: $updateQuery");
            $stmt = $conn->prepare($updateQuery);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            // Bind parameters with proper NULL handling for supplier_id
            $types = "ssssdsss";
            $params = [
                $name, 
                $brand, 
                $category, 
                $size, 
                $price, 
                $stock, 
                $condition_type, 
                $imageName
            ];
            
            // Add supplier_id with proper type
            if ($supplier_id !== null) {
                $types .= "i";
                $params[] = $supplier_id;
            } else {
                $types .= "s";
                $params[] = null;
            }
            
            // Add the ID
            $types .= "i";
            $params[] = $id;
            
            // Bind parameters
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update product: " . $stmt->error);
            }
            $stmt->close();
            
            // Update or insert supplier relationship
            if ($supplier_id !== null) {
                // Check if relationship already exists
                $checkStmt = $conn->prepare("SELECT id FROM supplier_products WHERE product_id = ?");
                $checkStmt->bind_param("i", $id);
                $checkStmt->execute();
                $exists = $checkStmt->get_result()->num_rows > 0;
                $checkStmt->close();
                
                if ($exists) {
                    // Update existing relationship
                    $relStmt = $conn->prepare("UPDATE supplier_products SET supplier_id = ?, is_primary = 1 WHERE product_id = ?");
                    $relStmt->bind_param("ii", $supplier_id, $id);
                } else {
                    // Insert new relationship
                    $relStmt = $conn->prepare("INSERT INTO supplier_products (supplier_id, product_id, is_primary) VALUES (?, ?, 1)");
                    $relStmt->bind_param("ii", $supplier_id, $id);
                }
                
                if (!$relStmt->execute()) {
                    throw new Exception("Failed to update supplier relationship: " . $relStmt->error);
                }
                $relStmt->close();
            } else {
                // If no supplier is selected, remove any existing relationship
                $delStmt = $conn->prepare("DELETE FROM supplier_products WHERE product_id = ?");
                $delStmt->bind_param("i", $id);
                $delStmt->execute();
                $delStmt->close();
            }
            
            // Commit transaction
            $conn->commit();
            $success = true;
            $msg = "✅ Product updated successfully!";
            $_SESSION['success'] = $msg;
            debugLog($msg);
            
            // Redirect after successful update
            header("Location: read.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $msg = "❌ Error: " . $e->getMessage();
            error_log("Update failed: " . $msg);
            
            // Clean up uploaded file if something went wrong
            if (isset($imageName) && $imageName !== $product['image'] && file_exists($upload_dir . $imageName)) {
                @unlink($upload_dir . $imageName);
            }
        }

        // Refresh product data with supplier info for display
        debugLog("Refreshing product data for display");
        $refreshQuery = "SELECT p.*, s.name as supplier_name 
                        FROM products p 
                        LEFT JOIN suppliers s ON p.supplier_id = s.id 
                        WHERE p.id = ?";
        $stmt = $conn->prepare($refreshQuery);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result) {
                    $product = $result->fetch_assoc();
                    debugLog("Refreshed product data: " . print_r($product, true));
                }
            }
            $stmt->close();
        }
    }
}

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit Product</h2>
        <?php if (!empty($msg)): ?>
            <div class="alert <?= strpos($msg, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>
        
        <form class="form-box" method="POST" enctype="multipart/form-data" id="productForm" novalidate>
            <input type="hidden" name="debug" value="1">
            
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       class="form-input"
                       value="<?= htmlspecialchars($product['name']); ?>"
                       data-required="true"
                       data-min-length="3"
                       data-max-length="100"
                       data-pattern-message="Product name must be between 3-100 characters">
            </div>

            <div class="form-group">
                <label for="brand">Brand</label>
                <input type="text" 
                       id="brand" 
                       name="brand" 
                       class="form-input"
                       value="<?= htmlspecialchars($product['brand']); ?>"
                       data-required="true"
                       data-min-length="2"
                       data-max-length="50"
                       data-pattern-message="Brand must be between 2-50 characters">
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" 
                       id="category" 
                       name="category" 
                       class="form-input"
                       value="<?= htmlspecialchars($product['category']); ?>"
                       data-required="true"
                       data-pattern="^[a-zA-Z\s&]+"
                       data-pattern-message="Please enter a valid category name">
            </div>

            <div class="form-group">
                <label for="size">Size</label>
                <input type="text" 
                       id="size" 
                       name="size" 
                       class="form-input"
                       value="<?= htmlspecialchars($product['size']); ?>"
                       data-required="true"
                       data-pattern-message="Please specify the product size">
            </div>

            <div class="form-group">
                <label for="price">Price (₱)</label>
                <input type="number" 
                       id="price" 
                       name="price" 
                       class="form-input"
                       step="0.01" 
                       min="0.01"
                       value="<?= htmlspecialchars($product['price']); ?>"
                       data-required="true"
                       data-pattern="^\d+(\.\d{1,2})?$"
                       data-pattern-message="Please enter a valid price">
            </div>

            <div class="form-group">
                <label for="stock">Stock Quantity</label>
                <input type="number" 
                       id="stock" 
                       name="stock" 
                       class="form-input"
                       min="0"
                       value="<?= htmlspecialchars($product['stock']); ?>"
                       data-required="true"
                       data-pattern="^\d+$"
                       data-pattern-message="Please enter a valid stock quantity">
            </div>

            <div class="form-group">
                <label for="condition_type">Condition</label>
                <select name="condition_type" 
                        id="condition_type" 
                        class="form-input"
                        data-required="true">
                    <option value="">-- Select Condition --</option>
                    <option value="Like New" <?= $product['condition_type']=="Like New"?"selected":""; ?>>Like New</option>
                    <option value="Good" <?= $product['condition_type']=="Good"?"selected":""; ?>>Good</option>
                    <option value="Slightly Used" <?= $product['condition_type']=="Slightly Used"?"selected":""; ?>>Slightly Used</option>
                </select>
            </div>

            <div class="form-group">
                <label for="image">Product Image</label>
                <?php if (!empty($product['image'])): ?>
                    <div class="current-image">
                        <p>Current Image:</p>
                        <img src="../../uploads/<?= htmlspecialchars($product['image']); ?>" 
                             alt="Current product image" 
                             style="max-width: 200px; display: block; margin: 10px 0;">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remove_image" id="remove_image">
                            Remove current image
                        </label>
                    </div>
                    <p style="margin: 15px 0 5px;">Upload new image (leave blank to keep current):</p>
                <?php endif; ?>
                <input type="file" 
                       id="image" 
                       name="image" 
                       class="form-input"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       data-file-type="image/jpeg,image/png,image/gif,image/webp"
                       data-max-size="2MB"
                       data-pattern-message="Please upload a valid image (JPG, PNG, GIF, or WebP, max 2MB)">
                <small class="text-muted">Leave blank to keep current image. Allowed formats: JPG, PNG, GIF, WebP. Max size: 2MB</small>
                <div id="imagePreview" style="margin-top: 10px;"></div>
            </div>

            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select name="supplier_id" 
                        id="supplier_id" 
                        class="form-input"
                        data-pattern-message="Please select a supplier">
                    <option value="">-- No Supplier --</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= $supplier['id'] ?>" <?= (isset($product['supplier_id']) && $product['supplier_id'] == $supplier['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($supplier['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($product['supplier_name']) && $product['supplier_name']): ?>
                    <small class="text-muted">Current supplier: <?= htmlspecialchars($product['supplier_name']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    💾 Update Product
                </button>
                <a href="read.php" class="btn-secondary">
                    ❌ Cancel
                </a>
            </div>
        </form>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    const form = document.getElementById('productForm');
    const imageInput = document.getElementById('image');
    const removeImageCheckbox = document.getElementById('remove_image');
    
    // Toggle image removal
    if (removeImageCheckbox) {
        removeImageCheckbox.addEventListener('change', function() {
            const imagePreview = document.querySelector('.current-image');
            if (this.checked && imagePreview) {
                imagePreview.style.opacity = '0.5';
            } else if (imagePreview) {
                imagePreview.style.opacity = '1';
            }
        });
    }
    
    // Image preview functionality
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                // Check file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }
                
                // Check file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Please upload a valid image file (JPG, PNG, GIF, or WebP)');
                    this.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(event) {
                    // Remove any existing preview
                    while (preview.firstChild) {
                        preview.removeChild(preview.firstChild);
                    }
                    
                    // Create and append new preview
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.style.maxWidth = '200px';
                    img.style.marginTop = '10px';
                    img.style.borderRadius = '4px';
                    img.style.border = '1px solid #ddd';
                    preview.appendChild(img);
                    
                    // Hide the remove image checkbox if showing a new image
                    if (removeImageCheckbox) {
                        removeImageCheckbox.checked = false;
                        const currentImage = document.querySelector('.current-image');
                        if (currentImage) {
                            currentImage.style.opacity = '1';
                        }
                    }
                };
                reader.readAsDataURL(file);
            } else if (preview) {
                // Clear preview if file input is cleared
                while (preview.firstChild) {
                    preview.removeChild(preview.firstChild);
                }
            }
        });
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        // The form validation will be handled by form-validation.js
        // This just ensures the form is properly initialized
    });
});
</script>

<style>
/* Additional styles for the update form */
.checkbox-label {
    display: flex;
    align-items: center;
    margin: 10px 0;
    cursor: pointer;
    font-size: 0.9em;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 8px;
}

.current-image {
    margin: 10px 0;
    padding: 10px;
    border: 1px dashed #ddd;
    border-radius: 4px;
    transition: opacity 0.3s ease;
}

.current-image p {
    margin: 0 0 8px 0;
    font-size: 0.9em;
    color: #666;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn-primary, .btn-secondary {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1em;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: var(--primary);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.btn-secondary {
    background-color: #f0f0f0;
    color: #333;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background-color: #e0e0e0;
}
</style>

<?php include '../../../includes/footer.php'; ?>