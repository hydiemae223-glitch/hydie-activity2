<?php
require 'inc.php';
require_login();
$u = current_user();
$products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
page_header('Dashboard');
?>
<div class="card">
  <h1>Welcome, <?= e($u['name']) ?>!</h1>
  <p>Role: <span class="badge"><?= e($u['role']) ?></span></p>
  <p>Total products: <b><?= (int)$products ?></b>
  <?php if (has_role('admin')): ?> | Total users: <b><?= (int)$users ?></b><?php endif; ?></p>
</div>
<div class="card">
  <h2>What you can do</h2>
  <ul>
    <li><b>Admin:</b> manage users (add/edit/delete/change role) + full product CRUD</li>
    <li><b>Staff:</b> add, view, and edit products (cannot delete)</li>
    <li><b>User:</b> view products only</li>
  </ul>
  <a class="btn" href="products.php">View Products</a>
  <?php if (has_role('admin')): ?><a class="btn" href="users.php">Manage Users</a><?php endif; ?>
</div>
<?php page_footer(); ?>
