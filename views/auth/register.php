<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0">
                    <i class="bi bi-person-plus"></i> Create Account
                </h4>
            </div>
            <div class="card-body">
                <?php 
                $errors = \App\Core\Session::getFlash('errors') ?? [];
                $old = \App\Core\Session::getFlash('old') ?? [];
                ?>
                
                <form action="/register" method="POST" id="registerForm" novalidate>
                    <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                               id="username" 
                               name="username" 
                               value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                               pattern="[a-zA-Z0-9_]+"
                               minlength="3"
                               maxlength="50"
                               required>
                        <div class="form-text">3-50 characters. Letters, numbers, and underscores only.</div>
                        <?php if (isset($errors['username'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['username']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" 
                               class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                               id="email" 
                               name="email" 
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                               required>
                        <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                               id="password" 
                               name="password" 
                               minlength="8"
                               required>
                        <div class="form-text">Minimum 8 characters.</div>
                        <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               required>
                        <?php if (isset($errors['password_confirmation'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirmation']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-gift"></i>
                        <strong>Welcome Bonus!</strong> New users receive $100 starting balance.
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Create Account
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">
                    Already have an account? 
                    <a href="/login">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    var username = document.getElementById('username');
    var email = document.getElementById('email');
    var password = document.getElementById('password');
    var passwordConfirmation = document.getElementById('password_confirmation');
    var valid = true;

    // Username validation
    if (!username.value.trim()) {
        username.classList.add('is-invalid');
        valid = false;
    } else if (username.value.length < 3) {
        username.classList.add('is-invalid');
        valid = false;
    } else if (!/^[a-zA-Z0-9_]+$/.test(username.value)) {
        username.classList.add('is-invalid');
        valid = false;
    } else {
        username.classList.remove('is-invalid');
    }

    // Email validation
    if (!email.value.trim()) {
        email.classList.add('is-invalid');
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        email.classList.add('is-invalid');
        valid = false;
    } else {
        email.classList.remove('is-invalid');
    }

    // Password validation
    if (!password.value) {
        password.classList.add('is-invalid');
        valid = false;
    } else if (password.value.length < 8) {
        password.classList.add('is-invalid');
        valid = false;
    } else {
        password.classList.remove('is-invalid');
    }

    // Password confirmation validation
    if (password.value !== passwordConfirmation.value) {
        passwordConfirmation.classList.add('is-invalid');
        valid = false;
    } else {
        passwordConfirmation.classList.remove('is-invalid');
    }

    if (!valid) {
        e.preventDefault();
    }
});
</script>
