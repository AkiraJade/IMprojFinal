<?php
require_once __DIR__ . '/../../../includes/config.php';

// Check admin access
checkAdmin();

$msg = "";

// Fetch all active suppliers
$suppliers = [];
try {
    $supplierQuery = "SELECT id, name FROM suppliers ORDER BY name";
    $supplierResult = $conn->query($supplierQuery);
    if ($supplierResult) {
        $suppliers = $supplierResult->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching suppliers: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $category = trim($_POST['category']);
    $size = trim($_POST['size']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $condition_type = trim($_POST['condition_type']);
    $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;

    // Validate inputs
    if (empty($name) || empty($brand) || empty($category) || $price <= 0 || $stock < 0) {
        $msg = "❌ Please fill all required fields with valid data.";
    } elseif (!isset($_FILES["images"]) || count(array_filter($_FILES["images"]["name"])) === 0) {
        $msg = "❌ Please upload at least one image.";
    } else {
        $images = $_FILES["images"];
        $image_paths = [];
        $has_errors = false;
        
        // Check all files first
        foreach ($images["tmp_name"] as $key => $tmp_name) {
            if ($images["error"][$key] === UPLOAD_ERR_OK) {
                $file_type = $images["type"][$key];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($file_type, $allowed_types)) {
                    $msg = "❌ Only JPG, PNG, GIF, and WEBP images are allowed.";
                    $has_errors = true;
                    break;
                }
            }
        }
        
        if (!$has_errors) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Create uploads directory if it doesn't exist
                $upload_dir = __DIR__ . '/../../../public/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Insert product (without image in the products table)
                $stmt = $conn->prepare("INSERT INTO products (name, brand, category, size, price, stock, condition_type) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssdis", $name, $brand, $category, $size, $price, $stock, $condition_type);
                
                if ($stmt->execute()) {
                    $product_id = $conn->insert_id;
                    
                    // Process each uploaded file
                    foreach ($images["tmp_name"] as $key => $tmp_name) {
                        if ($images["error"][$key] === UPLOAD_ERR_OK) {
                            $file_extension = pathinfo($images["name"][$key], PATHINFO_EXTENSION);
                            $unique_filename = uniqid() . '.' . $file_extension;
                            $target_path = $upload_dir . $unique_filename;
                            
                            // Log upload attempt
                            error_log("Attempting to move uploaded file to: " . $target_path);
                            
                            if (move_uploaded_file($tmp_name, $target_path)) {
                                error_log("File moved successfully: " . $target_path);
                                
                                // First image is primary
                                $is_primary = ($key === 0) ? 1 : 0;
                                
                                // Log database insertion
                                error_log("Inserting into product_images - Product ID: $product_id, Image: $unique_filename, Primary: $is_primary");
                                
                                $imageStmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
                                if (!$imageStmt) {
                                    error_log("Prepare failed: " . $conn->error);
                                    throw new Exception("Database error: " . $conn->error);
                                }
                                
                                $imageStmt->bind_param("isi", $product_id, $unique_filename, $is_primary);
                                if (!$imageStmt->execute()) {
                                    error_log("Execute failed: " . $imageStmt->error);
                                    throw new Exception("Failed to save image record: " . $imageStmt->error);
                                }
                                $imageStmt->close();
                                
                                $image_paths[] = $target_path;
                                
                                // For backward compatibility, update the products table with the first image
                                if ($is_primary) {
                                    $updateStmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
                                    if (!$updateStmt) {
                                        error_log("Prepare failed: " . $conn->error);
                                        throw new Exception("Database error: " . $conn->error);
                                    }
                                    
                                    $updateStmt->bind_param("si", $unique_filename, $product_id);
                                    if (!$updateStmt->execute()) {
                                        error_log("Update failed: " . $updateStmt->error);
                                        throw new Exception("Failed to update product image: " . $updateStmt->error);
                                    }
                                    $updateStmt->close();
                                }
                            } else {
                                throw new Exception("❌ Error uploading file: " . $images["name"][$key]);
                            }
                        }
                    }
                    
                    // If supplier is selected, add to supplier_products
                    if ($supplier_id > 0) {
                        $supplierStmt = $conn->prepare("INSERT INTO supplier_products (supplier_id, product_id, is_primary) VALUES (?, ?, 1)");
                        $supplierStmt->bind_param("ii", $supplier_id, $product_id);
                        $supplierStmt->execute();
                        $supplierStmt->close();
                    }
                    
                    $conn->commit();
                    $msg = "✅ Product Added Successfully!";
                    
                    // Clear the form
                    $name = $brand = $category = $size = '';
                    $price = $stock = 0;
                } else {
                    throw new Exception("Failed to add product");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "❌ Failed to add product: " . $e->getMessage();
                
                // Delete any uploaded files if transaction fails
                foreach ($image_paths as $image_path) {
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
            }
        } else {
            $msg = "❌ Error uploading image. Please try again.";
        }
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="admin-container">
    <?php require_once __DIR__ . '/../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Add Product</h2>

        <p class="msg"><?= $msg ?></p>

        <form class="form-box" method="POST" enctype="multipart/form-data" id="productForm" novalidate>
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       class="form-input"
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
                       data-required="true"
                       data-pattern="^\d+$"
                       data-pattern-message="Please enter a valid stock quantity">
            </div>

            <div class="form-group">
                <label for="condition_type">Condition Type</label>
                <select name="condition_type" 
                        id="condition_type" 
                        class="form-input"
                        data-required="true">
                    <option value="">-- Select Condition --</option>
                    <option value="Like New">Like New</option>
                    <option value="Good">Good</option>
                    <option value="Slightly Used">Slightly Used</option>
                </select>
            </div>

            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select name="supplier_id" 
                        id="supplier_id" 
                        class="form-input"
                        data-required="true"
                        data-pattern-message="Please select a supplier">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= htmlspecialchars($supplier['id']) ?>">
                            <?= htmlspecialchars($supplier['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($suppliers)): ?>
                    <p class="text-warning">No suppliers available. <a href="../suppliers/create.php">Add a supplier first</a>.</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="images">Product Images</label>
                <input type="file" 
                       id="images" 
                       name="images[]" 
                       class="form-input"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       multiple
                       data-required="true"
                       data-file-type="image/jpeg,image/png,image/gif,image/webp"
                       data-max-size="2MB"
                       data-pattern-message="Please upload at least one valid image (JPG, PNG, GIF, or WebP, max 2MB each)">
                <small class="text-muted">Allowed formats: JPG, PNG, GIF, WebP. Max size: 2MB per image. First image will be used as the main product image.</small>
                <div id="imagePreviews" class="image-previews"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    💾 Save Product
                </button>
                <a href="index.php" class="btn-secondary">
                    ❌ Cancel
                </a>
            </div>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

<style>
.image-previews {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}
.image-preview {
    position: relative;
    width: 100px;
    height: 100px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}
.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.image-preview-remove {
    position: absolute;
    top: 2px;
    right: 2px;
    background: rgba(0,0,0,0.5);
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('productForm');
    const imageInput = document.getElementById('images');
    const previewsContainer = document.getElementById('imagePreviews');
    let filesArray = [];

    // Handle file selection
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const files = e.target.files;
            
            // Clear existing previews
            previewsContainer.innerHTML = '';
            filesArray = [];
            
            // Process each file
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                // Check file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert(`File "${file.name}" exceeds the 2MB size limit.`);
                    continue;
                }
                
                // Check file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert(`File "${file.name}" is not a valid image type.`);
                    continue;
                }
                
                // Add to files array
                filesArray.push(file);
                
                // Create preview
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.createElement('div');
                    preview.className = 'image-preview';
                    preview.innerHTML = `
                        <img src="${event.target.result}" alt="Preview">
                        <button type="button" class="image-preview-remove" data-index="${filesArray.length - 1}">&times;</button>
                    `;
                    previewsContainer.appendChild(preview);
                };
                reader.readAsDataURL(file);
            }
            
            // Update the file input with valid files
            const dataTransfer = new DataTransfer();
            filesArray.forEach(file => dataTransfer.items.add(file));
            imageInput.files = dataTransfer.files;
        });
    }
    
    // Handle preview removal
    previewsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('image-preview-remove')) {
            const index = parseInt(e.target.getAttribute('data-index'));
            filesArray.splice(index, 1);
            
            // Update file input
            const dataTransfer = new DataTransfer();
            filesArray.forEach(file => dataTransfer.items.add(file));
            imageInput.files = dataTransfer.files;
            
            // Update previews
            previewsContainer.innerHTML = '';
            filesArray.forEach((file, i) => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.createElement('div');
                    preview.className = 'image-preview';
                    preview.innerHTML = `
                        <img src="${event.target.result}" alt="Preview">
                        <button type="button" class="image-preview-remove" data-index="${i}">&times;</button>
                    `;
                    previewsContainer.appendChild(preview);
                };
                reader.readAsDataURL(file);
            });
            
            e.stopPropagation();
        }
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        if (filesArray.length === 0) {
            e.preventDefault();
            alert('Please upload at least one image.');
            return false;
        }
        return true;
    });
});
</script>