<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="bi bi-grid"></i> Products
            </h1>
            <?php if (\App\Core\Session::get('role') === 'admin'): ?>
            <a href="/products/create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Product
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Search and Sort -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="/products" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" 
                                   class="form-control" 
                                   name="search" 
                                   placeholder="Search products..."
                                   value="<?= htmlspecialchars($search ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="sort_by">
                            <option value="name" <?= ($sortBy ?? '') === 'name' ? 'selected' : '' ?>>Sort by Name</option>
                            <option value="price" <?= ($sortBy ?? '') === 'price' ? 'selected' : '' ?>>Sort by Price</option>
                            <option value="quantity_available" <?= ($sortBy ?? '') === 'quantity_available' ? 'selected' : '' ?>>Sort by Stock</option>
                            <option value="created_at" <?= ($sortBy ?? '') === 'created_at' ? 'selected' : '' ?>>Sort by Date</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="sort_order">
                            <option value="ASC" <?= ($sortOrder ?? '') === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                            <option value="DESC" <?= ($sortOrder ?? '') === 'DESC' ? 'selected' : '' ?>>Descending</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="per_page">
                            <option value="10" <?= ($pagination['per_page'] ?? 10) == 10 ? 'selected' : '' ?>>10 per page</option>
                            <option value="25" <?= ($pagination['per_page'] ?? 10) == 25 ? 'selected' : '' ?>>25 per page</option>
                            <option value="50" <?= ($pagination['per_page'] ?? 10) == 50 ? 'selected' : '' ?>>50 per page</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Products List -->
<div class="row">
    <?php if (empty($products)): ?>
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No products found.
        </div>
    </div>
    <?php else: ?>
        <?php if (\App\Core\Session::get('role') === 'admin'): ?>
            <!-- Admin Table View -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Product</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="product-icon-sm me-3 bg-light rounded p-2">
                                                    <i class="bi bi-box-seam text-primary"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($product->name) ?></div>
                                                    <div class="text-muted small text-truncate" style="max-width: 300px;">
                                                        <?= htmlspecialchars($product->description ?? 'No description') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">$<?= number_format($product->price, 2) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($product->quantityAvailable > 0): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <?= $product->quantityAvailable ?> units
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Out of stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($product->isActive): ?>
                                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i> Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <a href="/products/<?= $product->id ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="/products/<?= $product->id ?>/edit" class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                                        onclick="deleteProduct(<?= $product->id ?>, '<?= htmlspecialchars($product->name) ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- User Card View -->
            <?php foreach ($products as $product): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card product-card h-100 <?= !$product->isActive ? 'border-secondary' : '' ?>">
                    <?php if (!$product->isActive): ?>
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge bg-secondary">Inactive</span>
                    </div>
                    <?php endif; ?>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-box-seam display-4 text-primary"></i>
                        </div>
                        <h5 class="card-title"><?= htmlspecialchars($product->name) ?></h5>
                        <p class="card-text text-muted small">
                            <?= htmlspecialchars(substr($product->description ?? 'No description', 0, 50)) ?>...
                        </p>
                        <p class="h4 text-success mb-3">
                            $<?= number_format($product->price, 2) ?>
                        </p>
                        <p class="text-muted mb-0">
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
                            <?php if ($product->quantityAvailable > 0 && $product->isActive && \App\Core\Session::get('logged_in')): ?>
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
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if (isset($pagination) && $pagination['last_page'] > 1): ?>
<div class="row">
    <div class="col-12">
        <nav aria-label="Products pagination">
            <ul class="pagination justify-content-center">
                <!-- Previous -->
                <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>&sort_by=<?= $sortBy ?? 'name' ?>&sort_order=<?= $sortOrder ?? 'ASC' ?>&per_page=<?= $pagination['per_page'] ?>">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                </li>
                
                <!-- Page Numbers -->
                <?php 
                $start = max(1, $pagination['current_page'] - 2);
                $end = min($pagination['last_page'], $pagination['current_page'] + 2);
                ?>
                
                <?php if ($start > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1&sort_by=<?= $sortBy ?? 'name' ?>&sort_order=<?= $sortOrder ?? 'ASC' ?>&per_page=<?= $pagination['per_page'] ?>">1</a>
                </li>
                <?php if ($start > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&sort_by=<?= $sortBy ?? 'name' ?>&sort_order=<?= $sortOrder ?? 'ASC' ?>&per_page=<?= $pagination['per_page'] ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($end < $pagination['last_page']): ?>
                <?php if ($end < $pagination['last_page'] - 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $pagination['last_page'] ?>&sort_by=<?= $sortBy ?? 'name' ?>&sort_order=<?= $sortOrder ?? 'ASC' ?>&per_page=<?= $pagination['per_page'] ?>"><?= $pagination['last_page'] ?></a>
                </li>
                <?php endif; ?>
                
                <!-- Next -->
                <li class="page-item <?= $pagination['current_page'] >= $pagination['last_page'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>&sort_by=<?= $sortBy ?? 'name' ?>&sort_order=<?= $sortOrder ?? 'ASC' ?>&per_page=<?= $pagination['per_page'] ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <p class="text-center text-muted">
            Showing <?= $pagination['from'] ?? 0 ?> to <?= $pagination['to'] ?? 0 ?> of <?= $pagination['total'] ?> products
        </p>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteProductName"></strong>?</p>
                <p class="text-muted small">This action will deactivate the product.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteProduct(id, name) {
    document.getElementById('deleteProductName').textContent = name;
    document.getElementById('deleteForm').action = '/products/' + id;
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
