<div class="row justify-content-center">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/products">Products</a></li>
                <li class="breadcrumb-item active">Add New Product</li>
            </ol>
        </nav>
        
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="bi bi-plus-circle"></i> Add New Product
                </h4>
            </div>
            <div class="card-body">
                <?php 
                $errors = \App\Core\Session::getFlash('errors') ?? [];
                $old = \App\Core\Session::getFlash('old') ?? [];
                ?>
                
                <form action="/products" method="POST" id="productForm" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Product Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                               id="name" 
                               name="name" 
                               value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                               maxlength="100"
                               required>
                        <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                        <?php else: ?>
                        <div class="invalid-feedback">Product name is required</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  rows="3"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">
                                Price (USD) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>" 
                                       id="price" 
                                       name="price" 
                                       value="<?= htmlspecialchars($old['price'] ?? '') ?>"
                                       step="0.01"
                                       min="0.01"
                                       required>
                                <?php if (isset($errors['price'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['price']) ?></div>
                                <?php else: ?>
                                <div class="invalid-feedback">Price must be positive</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="quantity_available" class="form-label">
                                Quantity <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control <?= isset($errors['quantity_available']) ? 'is-invalid' : '' ?>" 
                                   id="quantity_available" 
                                   name="quantity_available" 
                                   value="<?= htmlspecialchars($old['quantity_available'] ?? '0') ?>"
                                   min="0"
                                   required>
                            <?php if (isset($errors['quantity_available'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['quantity_available']) ?></div>
                            <?php else: ?>
                            <div class="invalid-feedback">Quantity must be non-negative</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image_url" class="form-label">Image URL</label>
                        <input type="url" 
                               class="form-control" 
                               id="image_url" 
                               name="image_url" 
                               value="<?= htmlspecialchars($old['image_url'] ?? '') ?>"
                               placeholder="https://example.com/image.jpg">
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Create Product
                        </button>
                        <a href="/products" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('productForm').addEventListener('submit', function(e) {
    var name = document.getElementById('name');
    var price = document.getElementById('price');
    var quantity = document.getElementById('quantity_available');
    var valid = true;

    // Name validation
    if (!name.value.trim()) {
        name.classList.add('is-invalid');
        valid = false;
    } else {
        name.classList.remove('is-invalid');
    }

    // Price validation
    if (!price.value || parseFloat(price.value) <= 0) {
        price.classList.add('is-invalid');
        valid = false;
    } else {
        price.classList.remove('is-invalid');
    }

    // Quantity validation
    if (quantity.value === '' || parseInt(quantity.value) < 0) {
        quantity.classList.add('is-invalid');
        valid = false;
    } else {
        quantity.classList.remove('is-invalid');
    }

    if (!valid) {
        e.preventDefault();
    }
});
</script>
