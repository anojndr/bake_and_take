<?php
/**
 * Admin Categories Management
 */

$categories = getAllCategories();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Categories</h1>
        <p class="page-subtitle">Organize your products into categories</p>
    </div>
    <button class="btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-lg"></i> Add Category
    </button>
</div>

<!-- Categories Grid -->
<div class="row g-4">
    <?php foreach ($categories as $slug => $category): 
        $productCount = getProductCountByCategory($slug);
    ?>
    <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="admin-card h-100">
            <div class="admin-card-body text-center py-4">
                <div class="stat-icon mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.5rem;">
                    <i class="<?php echo $category['icon']; ?>"></i>
                </div>
                <h4 style="color: var(--admin-text); margin-bottom: 0.5rem;">
                    <?php echo sanitize($category['name']); ?>
                </h4>
                <p style="color: var(--admin-text-muted); font-size: 0.875rem; margin-bottom: 1rem;">
                    <?php echo $productCount; ?> products
                </p>
                <div class="action-buttons justify-content-center">
                    <button class="btn-action edit" title="Edit" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $category['id'] ?? $slug; ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form action="includes/manage_category.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $category['id'] ?? 0; ?>">
                        <button type="submit" class="btn-action delete" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($categories)): ?>
    <div class="col-12">
        <div class="empty-state">
            <i class="bi bi-tags"></i>
            <h3>No categories yet</h3>
            <p>Start by adding your first category.</p>
            <button class="btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-lg"></i> Add Category
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--admin-dark-secondary); border: 1px solid var(--admin-dark-tertiary);">
            <div class="modal-header" style="border-color: var(--admin-dark-tertiary);">
                <h5 class="modal-title" style="color: var(--admin-text);">Add New Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="includes/manage_category.php" method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="modal-body" style="color: var(--admin-text);">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" 
                               style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);" 
                               required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL Slug</label>
                        <input type="text" name="slug" class="form-control" 
                               style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);" 
                               placeholder="e.g., breads, pastries" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon (Bootstrap Icons class)</label>
                        <input type="text" name="icon" class="form-control" 
                               style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);" 
                               placeholder="e.g., bi-basket" value="bi-box">
                    </div>
                </div>
                <div class="modal-footer" style="border-color: var(--admin-dark-tertiary);">
                    <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-admin-primary">
                        <i class="bi bi-plus-lg"></i> Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modals -->
<?php foreach ($categories as $slug => $category): ?>
<div class="modal fade" id="editCategoryModal<?php echo $category['id'] ?? $slug; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--admin-dark-secondary); border: 1px solid var(--admin-dark-tertiary);">
            <div class="modal-header" style="border-color: var(--admin-dark-tertiary);">
                <h5 class="modal-title" style="color: var(--admin-text);">Edit Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="includes/manage_category.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $category['id'] ?? 0; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="modal-body" style="color: var(--admin-text);">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" 
                               value="<?php echo sanitize($category['name']); ?>"
                               style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);" 
                               required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon (Bootstrap Icons class)</label>
                        <input type="text" name="icon" class="form-control" 
                               value="<?php echo sanitize($category['icon']); ?>"
                               style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);">
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
