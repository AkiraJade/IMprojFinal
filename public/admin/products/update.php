<?php
include __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: read.php");
    exit();
}

$id = intval($_GET['id']);
$msg = "";

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: read.php");
    exit();
}

// Update processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $category = trim($_POST['category']);
    $size = trim($_POST['size']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $condition_type = trim($_POST['condition_type']);
    $imageName = $product['image']; // default: no image change

    // Validate inputs
    if (empty($name) || empty($brand) || empty($category) || $price <= 0 || $stock < 0) {
        $msg = "❌ Please fill all required fields with valid data.";
    } else {
        if (!empty($_FILES["image"]["name"]) && $_FILES["image"]["error"] == 0) {
            // Check file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($_FILES["image"]["type"], $allowed_types)) {
                $imageName = $_FILES["image"]["name"];
                $target = "../../uploads/" . basename($imageName);
                move_uploaded_file($_FILES["image"]["tmp_name"], $target);
            } else {
                $msg = "❌ Only JPG, PNG, GIF, and WEBP images are allowed.";
            }
        }

        if (!isset($msg)) {
            $stmt = $conn->prepare("UPDATE products SET name=?, brand=?, category=?, size=?, price=?, stock=?, condition_type=?, image=? WHERE id=?");
            $stmt->bind_param("ssssdiisi", $name, $brand, $category, $size, $price, $stock, $condition_type, $imageName, $id);

            if ($stmt->execute()) {
                $msg = "✅ Product updated!";
                header("refresh:1; url=read.php");
            } else {
                $msg = "❌ Update failed.";
            }
        }

        // Refresh product data
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
    }
}

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>Edit Product</h2>
        <p class="msg"><?= $msg ?></p>

        <form class="form-box" method="POST" enctype="multipart/form-data">
            <label>Product Name</label>
            <input type="text" name="name" value="<?= $product['name']; ?>" required>

            <label>Brand</label>
            <input type="text" name="brand" value="<?= $product['brand']; ?>">

            <label>Category</label>
            <input type="text" name="category" value="<?= $product['category']; ?>" required>

            <label>Size</label>
            <input type="text" name="size" value="<?= $product['size']; ?>" required>

            <label>Price (₱)</label>
            <input type="number" name="price" step="0.01" value="<?= $product['price']; ?>" required>

            <label>Stock Quantity</label>
            <input type="number" name="stock" value="<?= $product['stock']; ?>" required>

            <label>Condition</label>
            <select name="condition_type">
                <option <?= $product['condition_type']=="Like New"?"selected":""; ?>>Like New</option>
                <option <?= $product['condition_type']=="Good"?"selected":""; ?>>Good</option>
                <option <?= $product['condition_type']=="Slightly Used"?"selected":""; ?>>Slightly Used</option>
            </select>

            <label>Product Image (Upload only if you want to replace)</label>
            <img src="../../uploads/<?= $product['image']; ?>" height="80"><br><br>
            <input type="file" name="image">

            <button type="submit" class="btn-primary">Update Product</button>
        </form>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
