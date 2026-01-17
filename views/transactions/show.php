<div class="row justify-content-center">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/transactions">Transactions</a></li>
                <li class="breadcrumb-item active">Transaction #<?= $transaction->id ?></li>
            </ol>
        </nav>
        
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="bi bi-receipt"></i> Transaction Details
                </h4>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h6 class="text-muted">Transaction ID</h6>
                        <p class="h5">#<?= $transaction->id ?></p>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <h6 class="text-muted">Status</h6>
                        <?php
                        $statusClass = match($transaction->status) {
                            'completed' => 'bg-success',
                            'pending' => 'bg-warning',
                            'cancelled' => 'bg-secondary',
                            'refunded' => 'bg-info',
                            default => 'bg-secondary'
                        };
                        ?>
                        <span class="badge <?= $statusClass ?> fs-6">
                            <?= ucfirst($transaction->status) ?>
                        </span>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>
                            <i class="bi bi-box-seam text-primary"></i> Product Information
                        </h5>
                        <table class="table table-borderless">
                            <tr>
                                <td class="text-muted">Product:</td>
                                <td>
                                    <?php if ($transaction->product): ?>
                                    <a href="/products/<?= $transaction->productId ?>">
                                        <?= htmlspecialchars($transaction->product->name) ?>
                                    </a>
                                    <?php else: ?>
                                    Product #<?= $transaction->productId ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Unit Price:</td>
                                <td>$<?= number_format($transaction->unitPrice, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Quantity:</td>
                                <td><?= $transaction->quantity ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>
                            <i class="bi bi-person text-primary"></i> Customer Information
                        </h5>
                        <table class="table table-borderless">
                            <?php if ($transaction->user): ?>
                            <tr>
                                <td class="text-muted">Customer:</td>
                                <td><?= htmlspecialchars($transaction->user->username) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email:</td>
                                <td><?= htmlspecialchars($transaction->user->email) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="text-muted">Payment Method:</td>
                                <td><?= ucfirst($transaction->paymentMethod ?? 'Balance') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-12">
                        <h5>
                            <i class="bi bi-calculator text-primary"></i> Order Summary
                        </h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <td>Subtotal (<?= $transaction->quantity ?> x $<?= number_format($transaction->unitPrice, 2) ?>):</td>
                                        <td class="text-end">$<?= number_format($transaction->totalAmount, 2) ?></td>
                                    </tr>
                                    <tr class="border-top">
                                        <td><strong>Total Amount:</strong></td>
                                        <td class="text-end h4 text-success">
                                            $<?= number_format($transaction->totalAmount, 2) ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row text-muted small">
                    <div class="col-12">
                        <i class="bi bi-calendar"></i> 
                        Transaction Date: <?= date('F d, Y \a\t H:i:s', strtotime($transaction->transactionDate)) ?>
                    </div>
                </div>
                
                <?php if ($transaction->notes): ?>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-muted">Notes</h6>
                        <p><?= htmlspecialchars($transaction->notes) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="/transactions" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Transactions
                </a>
            </div>
        </div>
    </div>
</div>
