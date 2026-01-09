<?php
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$currentPage = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$itemsPerPage = 6;

// Get all categories and products from database
$allCategories = getAllCategories();
$allProducts = getAllProducts();

// Get products (by category if selected)
$products = $selectedCategory ? getProductsByCategory($selectedCategory) : $allProducts;

// Filter by search query
if (!empty($searchQuery)) {
    $products = array_filter($products, function($p) use ($searchQuery) {
        $search = strtolower($searchQuery);
        return strpos(strtolower($p['name']), $search) !== false || 
               strpos(strtolower($p['description']), $search) !== false;
    });
}

// Re-index array after filtering
$products = array_values($products);

// Pagination calculations
$totalProducts = count($products);
$totalPages = max(1, ceil($totalProducts / $itemsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $itemsPerPage;
$paginatedProducts = array_slice($products, $offset, $itemsPerPage);

$pageTitle = $selectedCategory ? getCategoryName($selectedCategory) : 'All Products';
if (!empty($searchQuery)) {
    $pageTitle = 'Search Results for "' . sanitize($searchQuery) . '"';
}

// Build query string for pagination links
function buildPaginationUrl($page, $category = null, $search = '') {
    $params = ['page' => 'menu', 'pg' => $page];
    if ($category) $params['category'] = $category;
    if (!empty($search)) $params['search'] = $search;
    return 'index.php?' . http_build_query($params);
}
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
            <?php if (!empty($searchQuery)): ?>
            <span>/</span>
            <span>Search</span>
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
                    <!-- Search Box -->
                    <h4 class="filter-title">Search Products</h4>
                    <form action="index.php" method="GET" class="search-form mb-4">
                        <input type="hidden" name="page" value="menu">
                        <?php if ($selectedCategory): ?>
                        <input type="hidden" name="category" value="<?php echo $selectedCategory; ?>">
                        <?php endif; ?>
                        <div class="search-input-wrapper">
                            <input type="text" 
                                   name="search" 
                                   class="form-control search-input" 
                                   placeholder="Search for breads, cakes..."
                                   value="<?php echo sanitize($searchQuery); ?>"
                                   id="menuSearchInput">
                            <button type="submit" class="search-btn">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <?php if (!empty($searchQuery)): ?>
                        <a href="index.php?page=menu<?php echo $selectedCategory ? '&category=' . $selectedCategory : ''; ?>" class="clear-search">
                            <i class="bi bi-x-circle me-1"></i>Clear search
                        </a>
                        <?php endif; ?>
                    </form>

                    <h4 class="filter-title">Categories</h4>
                    <ul class="category-filter">
                        <li>
                            <a href="index.php?page=menu<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="<?php echo !$selectedCategory ? 'active' : ''; ?>">
                                <i class="bi bi-grid me-2"></i> All Products
                                <span class="count"><?php echo getProductCountByCategory(); ?></span>
                            </a>
                        </li>
                        <?php foreach ($allCategories as $slug => $category): ?>
                        <li>
                            <a href="index.php?page=menu&category=<?php echo $slug; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                               class="<?php echo $selectedCategory === $slug ? 'active' : ''; ?>">
                                <i class="bi <?php echo $category['icon']; ?> me-2"></i>
                                <?php echo $category['name']; ?>
                                <span class="count"><?php echo getProductCountByCategory($slug); ?></span>
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
                    <p class="results-count mb-0">
                        Showing <strong><?php echo count($paginatedProducts); ?></strong> of <strong><?php echo $totalProducts; ?></strong> products
                        <?php if ($totalPages > 1): ?>
                        <span class="page-indicator">(Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>)</span>
                        <?php endif; ?>
                    </p>
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
                    <?php foreach ($paginatedProducts as $product): ?>
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
                
                <?php if (empty($paginatedProducts)): ?>
                <div class="empty-state text-center py-5">
                    <i class="bi bi-search" style="font-size: 4rem; color: var(--text-light);"></i>
                    <h3 class="mt-3">No products found</h3>
                    <p class="text-muted">
                        <?php if (!empty($searchQuery)): ?>
                        No products match "<?php echo sanitize($searchQuery); ?>"
                        <?php else: ?>
                        Try selecting a different category
                        <?php endif; ?>
                    </p>
                    <a href="index.php?page=menu" class="btn btn-primary-custom mt-3">View All Products</a>
                </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="pagination-wrapper mt-5" aria-label="Menu navigation">
                    <ul class="pagination">
                        <!-- Previous Button -->
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link page-nav" href="<?php echo buildPaginationUrl($currentPage - 1, $selectedCategory, $searchQuery); ?>" aria-label="Previous">
                                <i class="bi bi-chevron-left"></i>
                                <span>Previous</span>
                            </a>
                        </li>

                        <?php
                        // Determine page range to show
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        // Show first page if not in range
                        if ($startPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo buildPaginationUrl(1, $selectedCategory, $searchQuery); ?>">1</a>
                            </li>
                            <?php if ($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaginationUrl($i, $selectedCategory, $searchQuery); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php
                        // Show last page if not in range
                        if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo buildPaginationUrl($totalPages, $selectedCategory, $searchQuery); ?>"><?php echo $totalPages; ?></a>
                            </li>
                        <?php endif; ?>

                        <!-- Next Button -->
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link page-nav" href="<?php echo buildPaginationUrl($currentPage + 1, $selectedCategory, $searchQuery); ?>" aria-label="Next">
                                <span>Next</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
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

/* Search Box Styles */
.search-form {
    margin-top: 0;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    padding: 0.85rem 1rem;
    padding-right: 3rem;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-md);
    font-size: 0.95rem;
    background: var(--cream);
    transition: var(--transition);
    width: 100%;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(212, 165, 116, 0.15);
}

.search-input::placeholder {
    color: var(--text-light);
    font-size: 0.9rem;
}

.search-btn {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    background: var(--gradient-warm);
    border: none;
    width: 38px;
    height: 38px;
    border-radius: var(--radius-sm);
    color: var(--white);
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-btn:hover {
    transform: translateY(-50%) scale(1.05);
    box-shadow: var(--shadow-glow);
}

.search-btn i {
    font-size: 1rem;
}

.clear-search {
    display: inline-flex;
    align-items: center;
    margin-top: 0.75rem;
    color: var(--text-secondary);
    font-size: 0.85rem;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    background: var(--cream-dark);
    transition: var(--transition);
}

.clear-search:hover {
    color: var(--secondary);
    background: var(--accent);
}

/* Page Indicator */
.page-indicator {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-left: 0.5rem;
}

/* Pagination Styles */
.pagination-wrapper {
    display: flex;
    justify-content: center;
}

.pagination {
    display: flex;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
    flex-wrap: wrap;
    justify-content: center;
}

.page-item {
    margin: 0;
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 42px;
    height: 42px;
    padding: 0 0.75rem;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-sm);
    background: var(--white);
    color: var(--text-secondary);
    font-weight: 500;
    font-size: 0.95rem;
    transition: var(--transition);
    text-decoration: none;
}

.page-link:hover {
    border-color: var(--primary);
    color: var(--primary-dark);
    background: var(--accent);
    transform: translateY(-2px);
}

.page-item.active .page-link {
    background: var(--gradient-warm);
    border-color: var(--primary);
    color: var(--white);
    box-shadow: var(--shadow-glow);
}

.page-item.disabled .page-link {
    opacity: 0.5;
    pointer-events: none;
    cursor: not-allowed;
    transform: none;
}

.page-nav {
    gap: 0.5rem;
    padding: 0 1rem;
}

.page-nav i {
    font-size: 0.85rem;
}

/* Responsive adjustments for pagination */
@media (max-width: 576px) {
    .page-nav span {
        display: none;
    }
    
    .page-nav {
        padding: 0 0.75rem;
        min-width: 42px;
    }
    
    .page-link {
        min-width: 38px;
        height: 38px;
        font-size: 0.9rem;
    }
}

/* Animation for products grid */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-card {
    animation: fadeIn 0.4s ease-out;
}

.products-grid .product-card:nth-child(1) { animation-delay: 0s; }
.products-grid .product-card:nth-child(2) { animation-delay: 0.05s; }
.products-grid .product-card:nth-child(3) { animation-delay: 0.1s; }
.products-grid .product-card:nth-child(4) { animation-delay: 0.15s; }
.products-grid .product-card:nth-child(5) { animation-delay: 0.2s; }
.products-grid .product-card:nth-child(6) { animation-delay: 0.25s; }
</style>
