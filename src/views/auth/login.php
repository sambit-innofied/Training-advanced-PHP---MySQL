<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h2 class="text-center mb-4">Login</h2>
      
      <?php if (isset($errors['general'])): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($errors['general']) ?>
        </div>
      <?php endif; ?>
      
      <form method="post" action="/authenticate">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          <?php if (isset($errors['username'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['username']) ?></div>
          <?php endif; ?>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>">
          <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
          <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>
      
      <div class="text-center mt-3">
        <p>Don't have an account? <a href="/register">Register here</a></p>
      </div>
    </div>
  </div>
</body>
</html>