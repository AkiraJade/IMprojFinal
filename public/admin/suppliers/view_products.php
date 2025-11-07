<?php
include __DIR__ . '/../../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Get all suppliers for dropdown
$suppliers_query = $conn->query("SELECT id, name FROM suppliers ORDER BY name");

// Get selected supplier
$selected_supplier = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;

$supplier_info = null;
$products = [];

if ($selected_supplier > 0) {
    // Get supplier info
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $selected_supplier);
    $stmt->execute();
    $supplier_info = $stmt->get_result()->fetch_assoc();
    
    if ($supplier_info) {
        // Get products supplied by this supplier
        $stmt = $conn->prepare("
            SELECT p.*, 
                   COALESCE(SUM(sd.quantity), 0) as total_supplied,
                   COALESCE(SUM(sd.cost), 0) as total_cost,
                   COUNT(sd.id) as delivery_count
            FROM products p
            LEFT JOIN supplier_deliveries sd ON p.id = sd.product_id AND sd.supplier_id = ?
            WHERE sd.supplier_id = ?
            GROUP BY p.id
            ORDER BY p.name
        ");
        $stmt->bind_param("ii", $selected_supplier, $selected_supplier);
        $stmt->execute();
        $products = $stmt->get_result();
    }
}

include '../../../includes/header.php';
?>

<div class="admin-container">
    <?php include '../sidebar.php'; ?>

    <main class="admin-content">
        <h2>🚚 Supplier Products</h2>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Select a supplier to view their products</p>

        <!-- Supplier Selection Form -->
        <form method="GET" style="margin: 2rem 0;">
            <div style="display: flex; gap: 1rem; align-items: flex-end; max-width: 600px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-weight: 600;">
                        Select Supplier
                    </label>
                    <select name="supplier_id" id="supplierSelect" 
                            style="width: 100%; padding: 0.75rem; background: var(--dark-light); border: 2px solid rgba(155, 77, 224, 0.2); 
                                   border-radius: var(--radius-md); color: var(--text-primary); font-size: 1rem;"
                            onchange="this.form.submit()">
                        <option value="">-- Choose a Supplier --</option>
                        <?php while($supplier = $suppliers_query->fetch_assoc()): ?>
                            <option value="<?= $supplier['id'] ?>" <?= $selected_supplier == $supplier['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($supplier['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">View Products</button>
            </div>
        </form>

        <?php if ($supplier_info): ?>
            <!-- Supplier Info Card -->
            <div style="background: var(--dark-light); padding: 1.5rem; border-radius: var(--radius-lg); margin: 2rem 0; border: 1px solid rgba(155, 77, 224, 0.2);">
                <h3 style="color: var(--primary-light); margin-bottom: 1rem;">Supplier Information</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Supplier Name</p>
                        <p style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars($supplier_info['name']) ?></p>
                    </div>
                    <div>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Contact Person</p>
                        <p style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars($supplier_info['contact_person']) ?></p>
                    </div>
                    <div>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Contact Number</p>
                        <p style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars($supplier_info['contact_number']) ?></p>
                    </div>
                    <div>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Email</p>
                        <p style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars($supplier_info['email']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <?php if ($products->num_rows > 0): ?>
                <h3 style="color: var(--primary-light); margin: 2rem 0 1rem 0;">📦 Products Supplied</h3>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Total Supplied</th>
                            <th>Total Cost</th>
                            <th>Deliveries</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total_supplied = 0;
                        $grand_total_cost = 0;
                        while($product = $products->fetch_assoc()): 
                            $grand_total_supplied += $product['total_supplied'];
                            $grand_total_cost += $product['total_cost'];
                        ?>
                            <tr>
                                <td><img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($product['image']) ?>" height="50" style="border-radius: 8px;"></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['brand']) ?></td>
                                <td><?= htmlspecialchars($product['category']) ?></td>
                                <td>
                                    <span style="padding: 0.5rem 0.75rem; border-radius: 8px; font-weight: 600;
                                                background: <?= $product['stock'] <= 5 ? 'rgba(255, 71, 87, 0.2)' : 'rgba(0, 217, 165, 0.2)' ?>;
                                                color: <?= $product['stock'] <= 5 ? '#FF4757' : '#00D9A5' ?>;">
                                        <?= $product['stock'] ?>
                                    </span>
                                </td>
                                <td><?= $product['total_supplied'] ?></td>
                                <td>₱<?= number_format($product['total_cost'], 2) ?></td>
                                <td><?= $product['delivery_count'] ?> times</td>
                                <td>
                                    <a href="../products/update.php?id=<?= $product['id'] ?>" class="btn-edit">✏ Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: rgba(155, 77, 224, 0.1); font-weight: 700;">
                            <td colspan="5" style="text-align: right;">TOTAL:</td>
                            <td><?= $grand_total_supplied ?></td>
                            <td>₱<?= number_format($grand_total_cost, 2) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>

                <div style="margin-top: 2rem;">
                    <a href="deliveries.php?supplier_id=<?= $selected_supplier ?>" class="btn-primary">📋 View All Deliveries</a>
                    <a href="add_delivery.php?supplier_id=<?= $selected_supplier ?>" class="btn-primary">➕ Add New Delivery</a>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; background: var(--dark-light); border-radius: var(--radius-lg); margin: 2rem 0;">
                    <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 1rem;">
                        📦 No products have been supplied by this supplier yet.
                    </p>
                    <a href="add_delivery.php?supplier_id=<?= $selected_supplier ?>" class="btn-primary">➕ Add First Delivery</a>
                </div>
            <?php endif; ?>

        <?php elseif ($selected_supplier > 0): ?>
            <div style="text-align: center; padding: 3rem; background: var(--dark-light); border-radius: var(--radius-lg); margin: 2rem 0;">
                <p style="color: var(--warning); font-size: 1.1rem;">⚠️ Supplier not found.</p>
                <a href="view_products.php" class="btn-secondary">Go Back</a>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; background: var(--dark-light); border-radius: var(--radius-lg); margin: 2rem 0;">
                <p style="color: var(--text-secondary); font-size: 1.1rem;">
                    👆 Please select a supplier from the dropdown above to view their products.
                </p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 2rem;">
            <a href="read.php" class="btn-secondary">⬅ Back to Suppliers List</a>
        </div>
    </main>
</div>

<?php include '../../../includes/footer.php'; ?>
