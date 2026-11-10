<?php
require 'inc.php';
if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$errors = [];
$name = $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $conf = $_POST['confirm'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($pass !== $conf) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $st = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $st->execute([$email]);
        if ($st->fetch()) {
            $errors[] = 'Email is already registered.';
        } else {
            $st = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'user')");
            $st->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            flash('Registration successful! You can now log in.');
            header('Location: login.php'); exit;
        }
    }
}
page_header('Register');
?>
<div class="card" style="max-width:420px;margin:auto">
  <h2>Create Account</h2>
  <?php foreach ($errors as $er) echo "<div class='msg err'>" . e($er) . "</div>"; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Full Name</label><input type="text" name="name" value="<?= e($name) ?>" required>
    <label>Email</label><input type="email" name="email" value="<?= e($email) ?>" required>
    <label>Password</label><input type="password" name="password" required>
    <label>Confirm Password</label><input type="password" name="confirm" required>
    <button class="btn" type="submit">Register</button>
  </form>
  <p>Already have an account? <a href="login.php">Sign In</a></p>
</div>
<?php page_footer(); ?>
