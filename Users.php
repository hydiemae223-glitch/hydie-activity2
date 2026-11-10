<?php
require 'inc.php';
require_role('admin');  

$me = current_user();
$roles = ['admin', 'staff', 'user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $pass = $_POST['password'] ?? '';

    if (!in_array($role, $roles, true)) $role = 'user';

    if ($action === 'create' || $action === 'update') {
        $st = $pdo->prepare("SELECT id FROM users WHERE email=? AND id<>?");
        $st->execute([$email, $id]);
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Valid name and email are required.', 'err');
        } elseif ($st->fetch()) {
            flash('Email is already used.', 'err');
        } elseif ($action === 'create') {
            if (strlen($pass) < 6) {
                flash('Password must be at least 6 characters.', 'err');
            } else {
                $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)")
                    ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
                flash('User added.');
            }
        } else {
            if ($id === (int)$me['id']) $role = 'admin'; 
            if ($pass !== '') {
                if (strlen($pass) < 6) { flash('Password must be at least 6 characters.', 'err'); header('Location: users.php'); exit; }
                $pdo->prepare("UPDATE users SET name=?, email=?, role=?, password=? WHERE id=?")
                    ->execute([$name, $email, $role, password_hash($pass, PASSWORD_DEFAULT), $id]);
            } else {
                $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?")
                    ->execute([$name, $email, $role, $id]);
            }
            if ($id === (int)$me['id']) { $_SESSION['user']['name'] = $name; $_SESSION['user']['email'] = $email; }
            flash('User updated.');
        }
    } elseif ($action === 'delete') {
        if ($id === (int)$me['id']) {
            flash('You cannot delete your own account.', 'err');
        } else {
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            flash('User deleted.');
        }
    }
    header('Location: users.php'); exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}
$rows = $pdo->query("SELECT * FROM users ORDER BY id")->fetchAll();

page_header('User Management');
?>
<div class="card">
  <h2><?= $edit ? 'Edit User' : 'Add User' ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
    <label>Full Name</label><input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required>
    <label>Email</label><input type="email" name="email" value="<?= e($edit['email'] ?? '') ?>" required>
    <label>Password <?= $edit ? '(leave blank to keep current)' : '' ?></label>
    <input type="password" name="password" <?= $edit ? '' : 'required' ?>>
    <label>Role</label>
    <select name="role">
      <?php foreach ($roles as $r): ?>
      <option value="<?= $r ?>" <?= (($edit['role'] ?? 'user') === $r) ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit"><?= $edit ? 'Update' : 'Add User' ?></button>
    <?php if ($edit): ?><a class="btn red" href="users.php">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <h2>All Users</h2>
  <div class="tbl"><table>
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= e($r['name']) ?></td>
      <td><?= e($r['email']) ?></td>
      <td><span class="badge"><?= e($r['role']) ?></span></td>
      <td><?= e($r['created_at']) ?></td>
      <td>
        <a class="btn sm" href="users.php?edit=<?= (int)$r['id'] ?>">Edit</a>
        <?php if ((int)$r['id'] !== (int)$me['id']): ?>
        <form class="inline" method="post" onsubmit="return confirm('Delete this user?')">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn sm red" type="submit">Delete</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php page_footer(); ?>
