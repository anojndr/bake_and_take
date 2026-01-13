<?php
/**
 * Admin Products Management
 */

$products = getAllProducts();
$categories = getAllCategories();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Products</h1>
        <p class="page-subtitle">Manage your bakery products and inventory</p>
    </div>
    <div class="d-flex gap-2">
        <form action="includes/delete_all.php" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to delete ALL products? This might affect existing orders.');">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="type" value="products">
            <button type="submit" class="btn-admin-danger" style="background: var(--admin-danger); color: white; padding: 0.5rem 1rem; border-radius: 8px; border: none; cursor: pointer;">
                <i class="bi bi-trash me-2"></i>Delete All
            </button>
        </form>
        <button class="btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-lg"></i> Add Product
        </button>
    </div>
</div>

<!-- Products Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">
            All Products
            <span class="badge bg-secondary ms-2"><?php echo count($products); ?></span>
        </h3>
    </div>
    <div class="admin-card-body p-0">
        <?php if (empty($products)): ?>
        <div class="empty-state">
            <i class="bi bi-box-seam"></i>
            <h3>No products yet</h3>
            <p>Start by adding your first product.</p>
            <button class="btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-lg"></i> Add Product
            </button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Featured</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>#<?php echo $product['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div style="width: 50px; height: 50px; background: var(--admin-dark); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <?php if ($product['image']): ?>
                                    <img src="../<?php echo getProductImage($product['image']); ?>" 
                                         alt="<?php echo sanitize($product['name']); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                    <i class="bi bi-image" style="color: var(--admin-text-muted);"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?php echo sanitize($product['name']); ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                        <?php echo sanitize(substr($product['description'], 0, 50)); ?>...
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge" style="background: var(--admin-dark); color: var(--admin-text-muted);">
                                <?php echo getCategoryName($product['category']); ?>
                            </span>
                        </td>
                        <td><strong><?php echo formatPrice($product['price']); ?></strong></td>
                        <td>
                            <span class="badge <?php echo ($product['stock'] ?? 0) > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $product['stock'] ?? 0; ?> in stock
                            </span>
                        </td>
                        <td>
                            <?php if ($product['featured']): ?>
                            <i class="bi bi-star-fill" style="color: var(--admin-accent);"></i>
                            <?php else: ?>
                            <i class="bi bi-star" style="color: var(--admin-text-muted);"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo (isset($product['active']) && $product['active']) || !isset($product['active']) ? 'ready' : 'cancelled'; ?>">
                                <?php echo (isset($product['active']) && $product['active']) || !isset($product['active']) ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action edit" title="Edit" data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $product['id']; ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="includes/manage_product.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn-action delete" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: var(--admin-dark-secondary); border: 1px solid var(--admin-dark-tertiary);">
            <div class="modal-header" style="border-color: var(--admin-dark-tertiary);">
                <h5 class="modal-title" style="color: var(--admin-text);">Add New Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="includes/manage_product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="modal-body" style="color: var(--admin-text);">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" 
                                   style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);" 
                                   required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" 
                                    style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);" 
                                    required>
                                <?php foreach ($categories as $slug => $cat): ?>
                                <option value="<?php echo $cat['id'] ?? $slug; ?>"><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                      style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);"></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Price (₱)</label>
                            <input type="number" name="price" step="0.01" min="0" class="form-control"
                                   style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);" 
                                   required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock" min="0" class="form-control"
                                   style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);" 
                                   placeholder="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*"
                                   style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Options</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="featured" id="featuredCheck">
                                <label class="form-check-label" for="featuredCheck">Featured Product</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-color: var(--admin-dark-tertiary);">
                    <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-admin-primary">
                        <i class="bi bi-plus-lg"></i> Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modals -->
<?php foreach ($products as $product): ?>
<div class="modal fade" id="editProductModal<?php echo $product['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: var(--admin-dark-secondary); border: 1px solid var(--admin-dark-tertiary);">
            <div class="modal-header" style="border-color: var(--admin-dark-tertiary);">
                <h5 class="modal-title" style="color: var(--admin-text);">Edit Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="includes/manage_product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="modal-body" style="color: var(--admin-text);">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo sanitize($product['name']); ?>"
                                   style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);" 
                                   required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" 
                                    style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);" 
                                    required>
                                <?php foreach ($categories as $slug => $cat): ?>
                                <option value="<?php echo $cat['id'] ?? $slug; ?>" <?php echo $product['category'] === $slug ? 'selected' : ''; ?>>
                                    <?php echo $cat['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                      style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);"><?php echo sanitize($product['description']); ?></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Price (₱)</label>
                            <input type="number" name="price" step="0.01" min="0" class="form-control"
                                   value="<?php echo $product['price']; ?>"
                                   style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);" 
                                   required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock" min="0" class="form-control"
                                   value="<?php echo $product['stock'] ?? 0; ?>"
                                   style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Options</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="featured" id="featuredCheck<?php echo $product['id']; ?>"
                                       <?php echo $product['featured'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="featuredCheck<?php echo $product['id']; ?>">Featured Product</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-color: var(--admin-dark-tertiary);">
                    <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-admin-primary">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
