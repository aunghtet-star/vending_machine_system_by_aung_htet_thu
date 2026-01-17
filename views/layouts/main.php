<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Vending Machine') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
        }
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .product-card img {
            height: 150px;
            object-fit: contain;
        }
        .pagination {
            justify-content: center;
        }
        .alert {
            margin-bottom: 0;
        }
        .flash-container {
            position: fixed;
            top: 70px;
            right: 20px;
            z-index: 1050;
            max-width: 350px;
        }
        .balance-badge {
            font-size: 1rem;
        }
        footer {
            margin-top: auto;
        }
    </style>
</head>
<body class="d-flex flex-column">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="bi bi-box-seam"></i> Vending Machine
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/products">
                            <i class="bi bi-grid"></i> Products
                        </a>
                    </li>
                    <?php if (\App\Core\Session::get('logged_in')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/transactions">
                            <i class="bi bi-receipt"></i> My Transactions
                        </a>
                    </li>
                    <?php if (\App\Core\Session::get('role') === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/products/create">
                            <i class="bi bi-plus-circle"></i> Add Product
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (\App\Core\Session::get('logged_in')): ?>
                    <?php if (\App\Core\Session::get('role') !== 'admin'): ?>
                    <li class="nav-item">
                        <span class="nav-link">
                            <span class="badge bg-success balance-badge">
                                <i class="bi bi-wallet2"></i> $<?= number_format(\App\Core\Session::get('balance', 0), 2) ?>
                            </span>
                        </span>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?= htmlspecialchars(\App\Core\Session::get('username')) ?>
                            <?php if (\App\Core\Session::get('role') === 'admin'): ?>
                            <span class="badge bg-warning text-dark">Admin</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form action="/logout" method="POST" class="d-inline">
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/register">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="flash-container">
        <?php if (\App\Core\Session::hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i>
            <?= htmlspecialchars(\App\Core\Session::getFlash('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (\App\Core\Session::hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <?= htmlspecialchars(\App\Core\Session::getFlash('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1 py-4">
        <div class="container">
            <?= $content ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-3 mt-4">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> Vending Machine System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss flash messages after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.flash-container .alert').forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
