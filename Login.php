<?php
require 'inc.php';
if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $st = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $st->execute([$email]);
    $u = $st->fetch();
    if ($u && password_verify($pass, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
        header('Location: dashboard.php'); exit;
    }
    $error = 'Invalid email or password.';
}
page_header('Sign In');
?>
<div class="card" style="max-width:420px;margin:auto">
  <h2>Sign In</h2>
  <?php if ($error) echo "<div class='msg err'>" . e($error) . "</div>"; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Email</label><input type="email" name="email" required>
    <label>Password</label><input type="password" name="password" required>
    <button class="btn" type="submit">Sign In</button>
  </form>
  <p>No account yet? <a href="register.php">Register</a></p>
  <small>Default admin: admin@coffee.com / admin123</small>
</div>
<?php page_footer(); ?>
