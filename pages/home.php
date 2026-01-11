<!-- Hero Section -->
<section class="hero" id="heroSection">
    <!-- Video Background -->
    <video class="hero-video" autoplay muted loop playsinline>
        <source src="assets/images/herovid.mp4" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>
    
    <div class="container">
        <div class="row align-items-center justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="hero-content text-center">
                    <h1>Freshly Baked <span>Happiness</span> Made Daily</h1>
                    <p>Experience the art of artisan baking. From crusty sourdoughs to delicate pastries, every bite tells a story of passion and tradition.</p>
                    <div class="hero-buttons justify-content-center">
                        <a href="index.php?page=menu" class="btn btn-hero btn-hero-primary">
                            <i class="bi bi-basket"></i> Explore Menu
                        </a>
                        <a href="index.php?page=about" class="btn btn-hero btn-hero-outline">
                            <i class="bi bi-play-circle"></i> Our Story
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="section categories" id="categoriesSection">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Categories</span>
            <h2 class="section-title">Explore Our Selection</h2>
            <p class="section-subtitle">From artisan breads to decadent cakes, discover your next favorite treat</p>
        </div>
        <div class="categories-grid">
            <?php foreach (getAllCategories() as $slug => $category): ?>
            <a href="index.php?page=menu&category=<?php echo $slug; ?>" class="category-card">
                <div class="category-icon">
                    <i class="bi <?php echo $category['icon']; ?>"></i>
                </div>
                <h4><?php echo $category['name']; ?></h4>
                <p><?php echo getProductCountByCategory($slug); ?> Items</p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="section products" id="productsSection">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Featured</span>
            <h2 class="section-title">Our Bestsellers</h2>
            <p class="section-subtitle">Handcrafted with love, these are the treats our customers can't stop raving about</p>
        </div>
        <div class="products-grid">
            <?php foreach (getFeaturedProducts() as $product): ?>
            <div class="product-card" 
                 data-product-id="<?php echo $product['id']; ?>"
                 data-product-name="<?php echo sanitize($product['name']); ?>"
                 data-product-price="<?php echo $product['price']; ?>"
                 data-product-image="<?php echo getProductImage($product['image']); ?>">
                <div class="product-image">
                    <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>">
                    <span class="product-badge">Popular</span>
                    <?php if (($product['stock'] ?? 0) <= 0): ?>
                    <span class="product-badge out-of-stock">Out of Stock</span>
                    <?php endif; ?>
                </div>
                <div class="product-content">
                    <span class="product-category"><?php echo getCategoryName($product['category']); ?></span>
                    <h3 class="product-title">
                        <a href="index.php?page=product&id=<?php echo $product['id']; ?>"><?php echo sanitize($product['name']); ?></a>
                    </h3>
                    <p class="product-description"><?php echo sanitize($product['description']); ?></p>
                    <div class="product-stock-info">
                        <?php 
                        $stock = $product['stock'] ?? 0;
                        if ($stock > 5): ?>
                        <span class="stock-indicator in-stock"><i class="bi bi-check-circle-fill"></i> <?php echo $stock; ?> in stock</span>
                        <?php elseif ($stock > 0): ?>
                        <span class="stock-indicator low-stock"><i class="bi bi-exclamation-circle-fill"></i> Only <?php echo $stock; ?> left</span>
                        <?php else: ?>
                        <span class="stock-indicator out-of-stock"><i class="bi bi-x-circle-fill"></i> Out of stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-footer">
                        <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                        <button class="btn-add-cart" <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                            <i class="bi bi-cart-plus"></i> <?php echo $stock > 0 ? 'Add' : 'Sold Out'; ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a href="index.php?page=menu" class="btn btn-hero btn-hero-primary">
                View All Products <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="section features" id="featuresSection">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Why Choose Us</span>
            <h2 class="section-title">The Bake & Take Difference</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-sunrise"></i>
                    </div>
                    <h4>Fresh Daily</h4>
                    <p>Everything is baked fresh every morning using traditional recipes</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-flower1"></i>
                    </div>
                    <h4>Organic Ingredients</h4>
                    <p>We source only the finest organic and locally-sourced ingredients</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-shop"></i>
                    </div>
                    <h4>Easy Pickup</h4>
                    <p>Order online and pick up at your convenience from our store</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-heart"></i>
                    </div>
                    <h4>Made with Love</h4>
                    <p>Every item is crafted with passion by our skilled bakers</p>
                </div>
            </div>
        </div>
    </div>
</section>




