<div class="row justify-content-center">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/products">Products</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($product->name) ?></li>
            </ol>
        </nav>
        
        <div class="card shadow">
            <div class="row g-0">
                <div class="col-md-4 bg-light d-flex align-items-center justify-content-center p-4">
                    <?php if ($product->imageUrl): ?>
                    <img src="<?= htmlspecialchars($product->imageUrl) ?>" 
                         alt="<?= htmlspecialchars($product->name) ?>" 
                         class="img-fluid rounded">
                    <?php else: ?>
                    <i class="bi bi-box-seam display-1 text-primary"></i>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h2 class="card-title"><?= htmlspecialchars($product->name) ?></h2>
                            <?php if (!$product->isActive): ?>
                            <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="card-text text-muted">
                            <?= htmlspecialchars($product->description ?? 'No description available') ?>
                        </p>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-6">
                                <h6 class="text-muted">Price</h6>
                                <p class="h3 text-success">$<?= number_format($product->price, 2) ?></p>
                            </div>
                            <div class="col-6">
                                <h6 class="text-muted">Stock</h6>
                                <?php if ($product->quantityAvailable > 0): ?>
                                <p class="h3 text-primary">
                                    <?= $product->quantityAvailable ?> 
                                    <small class="text-success">
                                        <i class="bi bi-check-circle"></i> In Stock
                                    </small>
                                </p>
                                <?php else: ?>
                                <p class="h3 text-danger">
                                    <i class="bi bi-x-circle"></i> Out of Stock
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row text-muted small">
                            <div class="col-6">
                                <i class="bi bi-calendar"></i> Added: <?= date('M d, Y', strtotime($product->createdAt)) ?>
                            </div>
                            <div class="col-6">
                                <i class="bi bi-clock"></i> Updated: <?= date('M d, Y', strtotime($product->updatedAt)) ?>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex gap-2">
                            <?php if ($product->quantityAvailable > 0 && $product->isActive && \App\Core\Session::get('logged_in') && \App\Core\Session::get('role') !== 'admin'): ?>
                            <a href="/products/<?= $product->id ?>/purchase" class="btn btn-success btn-lg">
                                <i class="bi bi-cart-plus"></i> Buy Now
                            </a>
                            <?php elseif (!\App\Core\Session::get('logged_in')): ?>
                            <a href="/login" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Login to Purchase
                            </a>
                            <?php endif; ?>
                            
                            <?php if (\App\Core\Session::get('role') === 'admin'): ?>
                            <a href="/products/<?= $product->id ?>/edit" class="btn btn-warning btn-lg">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <?php endif; ?>
                            
                            <a href="/products" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
