<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $category = trim($_POST['category']);
    $size = trim($_POST['size']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $condition_type = trim($_POST['condition_type']);

    // Validate inputs
    if (empty($name) || empty($brand) || empty($category) || $price <= 0 || $stock < 0) {
        $msg = "❌ Please fill all required fields with valid data.";
    } elseif (!isset($_FILES["image"]) || $_FILES["image"]["error"] != 0) {
        $msg = "❌ Please upload a valid image.";
    } else {
        $image = $_FILES["image"]["name"];
        $target = "../../uploads/" . basename($image);
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES["image"]["type"], $allowed_types)) {
            $msg = "❌ Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {
            $stmt = $conn->prepare("INSERT INTO products (name, brand, category, size, price, stock, condition_type, image)
                                    VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssdiis", $name, $brand, $category, $size, $price, $stock, $condition_type, $image);

            if ($stmt->execute()) {
                $msg = "✅ Product Added Successfully!";
            } else {
                $msg = "❌ Failed to add product.";
            }
        } else {
            $msg = "⚠ Image upload failed.";
        }
    }
}

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Add Product</h2>

        <p class="msg"><?= $msg ?></p>

        <form class="form-box" method="POST" enctype="multipart/form-data">
            <label>Product Name</label>
            <input type="text" name="name" required>

            <label>Brand</label>
            <input type="text" name="brand">

            <label>Category</label>
            <input type="text" name="category" required>

            <label>Size</label>
            <input type="text" name="size" required>

            <label>Price (₱)</label>
            <input type="number" name="price" step="0.01" required>

            <label>Stock Quantity</label>
            <input type="number" name="stock" required>

            <label>Condition Type</label>
            <select name="condition_type">
                <option value="Like New">Like New</option>
                <option value="Good">Good</option>
                <option value="Slightly Used">Slightly Used</option>
            </select>

            <label>Product Image</label>
            <input type="file" name="image" required>

            <button type="submit" class="btn-primary">Save Product</button>
        </form>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
