<div class="row justify-content-center">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/products">Products</a></li>
                <li class="breadcrumb-item"><a href="/products/<?= $product->id ?>"><?= htmlspecialchars($product->name) ?></a></li>
                <li class="breadcrumb-item active">Purchase</li>
            </ol>
        </nav>
        
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">
                    <i class="bi bi-cart-check"></i> Purchase Product
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Product Info -->
                    <div class="col-md-6 border-end">
                        <div class="text-center mb-3">
                            <?php if ($product->imageUrl): ?>
                            <img src="<?= htmlspecialchars($product->imageUrl) ?>" 
                                 alt="<?= htmlspecialchars($product->name) ?>" 
                                 class="img-fluid rounded"
                                 style="max-height: 150px;">
                            <?php else: ?>
                            <i class="bi bi-box-seam display-1 text-primary"></i>
                            <?php endif; ?>
                        </div>
                        
                        <h3><?= htmlspecialchars($product->name) ?></h3>
                        <p class="text-muted"><?= htmlspecialchars($product->description ?? 'No description') ?></p>
                        
                        <table class="table table-borderless">
                            <tr>
                                <td class="text-muted">Unit Price:</td>
                                <td class="h4 text-success">$<?= number_format($product->price, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">In Stock:</td>
                                <td>
                                    <span class="badge bg-primary"><?= $product->quantityAvailable ?> units</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Purchase Form -->
                    <div class="col-md-6">
                        <h5 class="mb-3">
                            <i class="bi bi-wallet2"></i> Your Balance: 
                            <span class="text-success">$<?= number_format($balance, 2) ?></span>
                        </h5>
                        
                        <form action="/products/<?= $product->id ?>/purchase" method="POST" id="purchaseForm">
                            <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="decreaseQty()">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="number" 
                                           class="form-control text-center" 
                                           id="quantity" 
                                           name="quantity" 
                                           value="1"
                                           min="1"
                                           max="<?= min($product->quantityAvailable, floor($balance / $product->price)) ?>"
                                           required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="increaseQty()">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    Max: <?= min($product->quantityAvailable, floor($balance / $product->price)) ?> 
                                    (based on stock and balance)
                                </div>
                            </div>
                            
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6>Order Summary</h6>
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td>Unit Price:</td>
                                            <td class="text-end">$<?= number_format($product->price, 2) ?></td>
                                        </tr>
                                        <tr>
                                            <td>Quantity:</td>
                                            <td class="text-end" id="displayQty">1</td>
                                        </tr>
                                        <tr class="border-top">
                                            <td><strong>Total:</strong></td>
                                            <td class="text-end h5 text-success" id="totalAmount">
                                                $<?= number_format($product->price, 2) ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted small">Balance After:</td>
                                            <td class="text-end text-muted small" id="balanceAfter">
                                                $<?= number_format($balance - $product->price, 2) ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <?php if ($balance < $product->price): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                Insufficient balance! You need at least $<?= number_format($product->price, 2) ?>.
                            </div>
                            <?php else: ?>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-cart-check"></i> Confirm Purchase
                                </button>
                            </div>
                            <?php endif; ?>
                        </form>
                        
                        <div class="mt-3">
                            <a href="/products/<?= $product->id ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Product
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var unitPrice = <?= $product->price ?>;
var balance = <?= $balance ?>;
var maxQty = <?= min($product->quantityAvailable, floor($balance / $product->price)) ?>;

function updateTotal() {
    var qty = parseInt(document.getElementById('quantity').value) || 1;
    var total = qty * unitPrice;
    var balanceAfter = balance - total;
    
    document.getElementById('displayQty').textContent = qty;
    document.getElementById('totalAmount').textContent = '$' + total.toFixed(2);
    document.getElementById('balanceAfter').textContent = '$' + balanceAfter.toFixed(2);
    
    if (balanceAfter < 0) {
        document.getElementById('balanceAfter').classList.add('text-danger');
    } else {
        document.getElementById('balanceAfter').classList.remove('text-danger');
    }
}

function increaseQty() {
    var input = document.getElementById('quantity');
    var current = parseInt(input.value) || 0;
    if (current < maxQty) {
        input.value = current + 1;
        updateTotal();
    }
}

function decreaseQty() {
    var input = document.getElementById('quantity');
    var current = parseInt(input.value) || 0;
    if (current > 1) {
        input.value = current - 1;
        updateTotal();
    }
}

document.getElementById('quantity').addEventListener('change', updateTotal);
document.getElementById('quantity').addEventListener('input', updateTotal);

document.getElementById('purchaseForm').addEventListener('submit', function(e) {
    var qty = parseInt(document.getElementById('quantity').value) || 0;
    var total = qty * unitPrice;
    
    if (qty < 1 || qty > maxQty) {
        e.preventDefault();
        alert('Invalid quantity. Please select between 1 and ' + maxQty);
        return;
    }
    
    if (total > balance) {
        e.preventDefault();
        alert('Insufficient balance!');
        return;
    }
    
    if (!confirm('Confirm purchase of ' + qty + ' x <?= htmlspecialchars($product->name) ?> for $' + total.toFixed(2) + '?')) {
        e.preventDefault();
    }
});
</script>
