<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </h4>
            </div>
            <div class="card-body">
                <?php 
                $errors = \App\Core\Session::getFlash('errors') ?? [];
                $old = \App\Core\Session::getFlash('old') ?? [];
                ?>
                
                <form action="/login" method="POST" id="loginForm" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username or Email</label>
                        <input type="text" 
                               class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                               id="username" 
                               name="username" 
                               value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                               required>
                        <?php if (isset($errors['username'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['username']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" 
                               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                               id="password" 
                               name="password" 
                               required>
                        <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">
                    Don't have an account? 
                    <a href="/register">Register here</a>
                </p>
            </div>
        </div>
        
        <!-- Demo Credentials -->
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="bi bi-info-circle"></i> Demo Credentials
                </h6>
                <p class="small text-muted mb-1">
                    <strong>Admin:</strong> admin / password
                </p>
                <p class="small text-muted mb-0">
                    <strong>User:</strong> user1 / password
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    var username = document.getElementById('username');
    var password = document.getElementById('password');
    var valid = true;

    // Client-side validation
    if (!username.value.trim()) {
        username.classList.add('is-invalid');
        valid = false;
    } else {
        username.classList.remove('is-invalid');
    }

    if (!password.value) {
        password.classList.add('is-invalid');
        valid = false;
    } else {
        password.classList.remove('is-invalid');
    }

    if (!valid) {
        e.preventDefault();
    }
});
</script>
