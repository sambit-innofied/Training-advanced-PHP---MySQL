<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h2 class="text-center mb-4">Register</h2>
      
      <?php if (isset($errors['general'])): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($errors['general']) ?>
        </div>
      <?php endif; ?>
      
      <form method="post" action="/store-user">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($old['username'] ?? '') ?>">
          <?php if (isset($errors['username'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['username']) ?></div>
          <?php endif; ?>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
          <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
          <?php endif; ?>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>">
          <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
          <?php endif; ?>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="password_confirm" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>">
          <?php if (isset($errors['password_confirm'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirm']) ?></div>
          <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary w-100">Register</button>
      </form>
      
      <div class="text-center mt-3">
        <p>Already have an account? <a href="/login">Login here</a></p>
      </div>
    </div>
  </div>
</body>
</html>