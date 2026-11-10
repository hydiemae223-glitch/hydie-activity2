<?php
require 'inc.php';
require_login();

$canWrite  = has_role('admin', 'staff');   // create + update
$canDelete = has_role('admin');            // delete

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';

    if ($action === 'create' || $action === 'update') {
        if (!$canWrite) { http_response_code(403); die('Access denied.'); }
        if ($name === '' || !is_numeric($price) || $price < 0 || !ctype_digit((string)$stock)) {
            flash('Please enter a valid name, price, and stock.', 'err');
        } elseif ($action === 'create') {
            $pdo->prepare("INSERT INTO products (name,description,price,stock) VALUES (?,?,?,?)")
                ->execute([$name, $desc, $price, $stock]);
            flash('Product added.');
        } else {
            $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=? WHERE id=?")
                ->execute([$name, $desc, $price, $stock, $id]);
            flash('Product updated.');
        }
    } elseif ($action === 'delete') {
        if (!$canDelete) { http_response_code(403); die('Access denied.'); }
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
        flash('Product deleted.');
    }
    header('Location: products.php'); exit;
}

// READ
$edit = null;
if ($canWrite && isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}
$rows = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();

page_header('Products');
?>
<?php if ($canWrite): ?>
<div class="card">
  <h2><?= $edit ? 'Edit Product' : 'Add Product' ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
    <label>Name</label><input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required>
    <label>Description</label><textarea name="description" rows="2"><?= e($edit['description'] ?? '') ?></textarea>
    <label>Price</label><input type="number" step="0.01" min="0" name="price" value="<?= e($edit['price'] ?? '') ?>" required>
    <label>Stock</label><input type="number" min="0" name="stock" value="<?= e($edit['stock'] ?? 0) ?>" required>
    <button class="btn" type="submit"><?= $edit ? 'Update' : 'Add' ?></button>
    <?php if ($edit): ?><a class="btn red" href="products.php">Cancel</a><?php endif; ?>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <h2>Product List</h2>
  <div class="tbl"><table>
    <tr><th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Stock</th><?php if ($canWrite) echo '<th>Actions</th>'; ?></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= e($r['name']) ?></td>
      <td><?= e($r['description']) ?></td>
      <td>&#8369;<?= number_format($r['price'], 2) ?></td>
      <td><?= (int)$r['stock'] ?></td>
      <?php if ($canWrite): ?>
      <td>
        <a class="btn sm" href="products.php?edit=<?= (int)$r['id'] ?>">Edit</a>
        <?php if ($canDelete): ?>
        <form class="inline" method="post" onsubmit="return confirm('Delete this product?')">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn sm red" type="submit">Delete</button>
        </form>
        <?php endif; ?>
      </td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows) echo "<tr><td colspan='6'>No products yet.</td></tr>"; ?>
  </table></div>
</div>
<?php page_footer(); ?>
