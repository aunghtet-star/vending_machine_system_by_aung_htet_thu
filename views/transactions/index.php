<div class="row">
    <div class="col-12">
        <h1>
            <i class="bi bi-receipt"></i> Transaction History
        </h1>
        <p class="text-muted">View your purchase history</p>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?php if (empty($transactions)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No transactions found.
            <a href="/products" class="alert-link">Start shopping!</a>
        </div>
        <?php else: ?>
        <div class="card shadow">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td>#<?= $transaction->id ?></td>
                            <td>
                                <small><?= date('M d, Y H:i', strtotime($transaction->transactionDate)) ?></small>
                            </td>
                            <td>
                                <?php if ($transaction->product): ?>
                                <a href="/products/<?= $transaction->productId ?>">
                                    <?= htmlspecialchars($transaction->product->name) ?>
                                </a>
                                <?php else: ?>
                                Product #<?= $transaction->productId ?>
                                <?php endif; ?>
                            </td>
                            <td><?= $transaction->quantity ?></td>
                            <td>$<?= number_format($transaction->unitPrice, 2) ?></td>
                            <td class="text-success fw-bold">
                                $<?= number_format($transaction->totalAmount, 2) ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = match($transaction->status) {
                                    'completed' => 'bg-success',
                                    'pending' => 'bg-warning',
                                    'cancelled' => 'bg-secondary',
                                    'refunded' => 'bg-info',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $statusClass ?>">
                                    <?= ucfirst($transaction->status) ?>
                                </span>
                            </td>
                            <td>
                                <a href="/transactions/<?= $transaction->id ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if (isset($pagination) && $pagination['last_page'] > 1): ?>
        <nav aria-label="Transactions pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                </li>
                
                <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?= $pagination['current_page'] >= $pagination['last_page'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
