<div class="row">
    <div class="col-12">
        <div class="jumbotron bg-white p-5 rounded shadow-sm mb-4">
            <h1 class="display-4">
                <i class="bi bi-box-seam text-primary"></i> 
                Welcome to Vending Machine
            </h1>
            <p class="lead">Your one-stop shop for refreshing beverages and snacks!</p>
            <hr class="my-4">
            <?php if (!isset($user)): ?>
            <p>Sign up today and get $100 starting balance!</p>
            <a class="btn btn-primary btn-lg" href="/register" role="button">
                <i class="bi bi-person-plus"></i> Get Started
            </a>
            <?php else: ?>
            <p>Browse our selection and make a purchase today!</p>
            <a class="btn btn-primary btn-lg" href="/products" role="button">
                <i class="bi bi-cart"></i> Browse Products
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Featured Products -->
<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-star text-warning"></i> Featured Products
        </h2>
    </div>
</div>

<div class="row">
    <?php if (empty($products)): ?>
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No products available at the moment.
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($products as $product): ?>
    <div class="col-md-4 col-lg-3 mb-4">
        <div class="card product-card h-100">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-box-seam display-4 text-primary"></i>
                </div>
                <h5 class="card-title"><?= htmlspecialchars($product->name) ?></h5>
                <p class="card-text text-muted small">
                    <?= htmlspecialchars($product->description ?? 'No description available') ?>
                </p>
                <p class="h4 text-success mb-3">
                    $<?= number_format($product->price, 2) ?>
                </p>
                <p class="text-muted">
                    <small>
                        <?php if ($product->quantityAvailable > 0): ?>
                        <span class="text-success">
                            <i class="bi bi-check-circle"></i> In Stock (<?= $product->quantityAvailable ?>)
                        </span>
                        <?php else: ?>
                        <span class="text-danger">
                            <i class="bi bi-x-circle"></i> Out of Stock
                        </span>
                        <?php endif; ?>
                    </small>
                </p>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-grid gap-2">
                    <a href="/products/<?= $product->id ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye"></i> View Details
                    </a>
                    <?php if ($product->quantityAvailable > 0 && \App\Core\Session::get('logged_in')): ?>
                    <a href="/products/<?= $product->id ?>/purchase" class="btn btn-success btn-sm">
                        <i class="bi bi-cart-plus"></i> Buy Now
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-12 text-center">
        <a href="/products" class="btn btn-outline-primary btn-lg">
            <i class="bi bi-grid"></i> View All Products
        </a>
    </div>
</div>
