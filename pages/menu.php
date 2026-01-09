<?php
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
$products = $selectedCategory ? getProductsByCategory($selectedCategory) : $PRODUCTS;
$pageTitle = $selectedCategory ? getCategoryName($selectedCategory) : 'All Products';
?>

<!-- Page Header -->
<header class="page-header">
    <div class="container">
        <h1><?php echo $pageTitle; ?></h1>
        <div class="breadcrumb-custom">
            <a href="index.php">Home</a>
            <span>/</span>
            <span>Menu</span>
            <?php if ($selectedCategory): ?>
            <span>/</span>
            <span><?php echo getCategoryName($selectedCategory); ?></span>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Menu Section -->
<section class="section menu-section">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <h4 class="filter-title">Categories</h4>
                    <ul class="category-filter">
                        <li>
                            <a href="index.php?page=menu" class="<?php echo !$selectedCategory ? 'active' : ''; ?>">
                                <i class="bi bi-grid me-2"></i> All Products
                                <span class="count"><?php echo count($PRODUCTS); ?></span>
                            </a>
                        </li>
                        <?php foreach ($CATEGORIES as $slug => $category): ?>
                        <li>
                            <a href="index.php?page=menu&category=<?php echo $slug; ?>" 
                               class="<?php echo $selectedCategory === $slug ? 'active' : ''; ?>">
                                <i class="bi <?php echo $category['icon']; ?> me-2"></i>
                                <?php echo $category['name']; ?>
                                <span class="count"><?php echo count(array_filter($PRODUCTS, fn($p) => $p['category'] === $slug)); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <h4 class="filter-title mt-4">Price Range</h4>
                    <div class="price-filter">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="price1" checked>
                            <label class="form-check-label" for="price1">Under $10</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="price2" checked>
                            <label class="form-check-label" for="price2">$10 - $25</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="price3" checked>
                            <label class="form-check-label" for="price3">$25 - $50</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="price4" checked>
                            <label class="form-check-label" for="price4">Over $50</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-lg-9">
                <div class="menu-header mb-4">
                    <p class="results-count mb-0">Showing <strong><?php echo count($products); ?></strong> products</p>
                    <div class="sort-options">
                        <select class="form-select form-select-sm">
                            <option>Sort by: Featured</option>
                            <option>Price: Low to High</option>
                            <option>Price: High to Low</option>
                            <option>Name: A to Z</option>
                        </select>
                    </div>
                </div>
                
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card" 
                         data-product-id="<?php echo $product['id']; ?>"
                         data-product-name="<?php echo sanitize($product['name']); ?>"
                         data-product-price="<?php echo $product['price']; ?>"
                         data-product-image="<?php echo getProductImage($product['image']); ?>">
                        <div class="product-image">
                            <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>">
                            <?php if ($product['featured']): ?>
                            <span class="product-badge">Popular</span>
                            <?php endif; ?>
                            <div class="product-actions">
                                <button class="product-action-btn" title="Quick View">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="product-action-btn" title="Add to Wishlist">
                                    <i class="bi bi-heart"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-content">
                            <span class="product-category"><?php echo getCategoryName($product['category']); ?></span>
                            <h3 class="product-title">
                                <a href="index.php?page=product&id=<?php echo $product['id']; ?>"><?php echo sanitize($product['name']); ?></a>
                            </h3>
                            <p class="product-description"><?php echo sanitize($product['description']); ?></p>
                            <div class="product-footer">
                                <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                                <button class="btn-add-cart">
                                    <i class="bi bi-cart-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($products)): ?>
                <div class="empty-state text-center py-5">
                    <i class="bi bi-basket" style="font-size: 4rem; color: var(--text-light);"></i>
                    <h3 class="mt-3">No products found</h3>
                    <p class="text-muted">Try selecting a different category</p>
                    <a href="index.php?page=menu" class="btn btn-primary-custom mt-3">View All Products</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.filter-sidebar {
    background: var(--white);
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    position: sticky;
    top: 100px;
}

.filter-title {
    font-size: 1.1rem;
    color: var(--dark);
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--accent);
}

.category-filter {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-filter li { margin-bottom: 0.5rem; }

.category-filter a {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: var(--text-secondary);
    border-radius: var(--radius-sm);
    transition: var(--transition);
}

.category-filter a:hover,
.category-filter a.active {
    background: var(--accent);
    color: var(--primary-dark);
}

.category-filter .count {
    margin-left: auto;
    background: var(--cream-dark);
    padding: 0.15rem 0.5rem;
    border-radius: 50px;
    font-size: 0.8rem;
}

.category-filter a.active .count { background: var(--primary-light); }

.price-filter .form-check { margin-bottom: 0.5rem; }

.form-check-input:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

.menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.sort-options .form-select {
    border-color: var(--cream-dark);
    border-radius: var(--radius-sm);
}

.sort-options .form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.15);
}
</style>
