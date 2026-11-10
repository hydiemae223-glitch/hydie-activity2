<?php
session_start();

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'coffee_shop';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME`");
    $pdo->exec("USE `$DB_NAME`");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','staff','user') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    if ($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() == 0) {
        $st = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'admin')");
        $st->execute(['Administrator', 'admin@coffee.com', password_hash('admin123', PASSWORD_DEFAULT)]);
    }
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function flash($msg = null, $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return; }
    if (!empty($_SESSION['flash'])) {
        [$m, $t] = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return "<div class='msg $t'>" . e($m) . "</div>";
    }
    return '';
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return "<input type='hidden' name='csrf' value='" . csrf_token() . "'>"; }
function check_csrf() {
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(403);
        die("Invalid CSRF token.");
    }
}


function is_logged_in() { return !empty($_SESSION['user']); }
function current_user() { return $_SESSION['user'] ?? null; }
function has_role(...$roles) { return is_logged_in() && in_array($_SESSION['user']['role'], $roles, true); }

function require_login() {
    if (!is_logged_in()) {
        flash('Please log in first.', 'err');
        header('Location: login.php'); exit;
    }
}
function require_role(...$roles) {
    require_login();
    if (!has_role(...$roles)) {
        http_response_code(403);
        page_header('Access Denied');
        echo "<div class='card'><h2>403 - Access Denied</h2><p>Wala kang permission para ma-access ang page na ito.</p><a class='btn' href='dashboard.php'>Back to Dashboard</a></div>";
        page_footer(); exit;
    }
}

function page_header($title) {
    $u = current_user();
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<title>" . e($title) . " | Coffee Shop</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#f4ece4;color:#3b2a20}
nav{background:#4b2e1e;padding:12px 20px;display:flex;flex-wrap:wrap;gap:14px;align-items:center}
nav a{color:#fff;text-decoration:none;font-weight:bold}nav .sp{flex:1}nav span{color:#e8d5c4;font-size:14px}
.wrap{max-width:900px;margin:25px auto;padding:0 15px}
.card{background:#fff;border-radius:10px;padding:22px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.1)}
h1,h2{margin-top:0}label{display:block;margin:10px 0 4px;font-weight:bold}
input,select,textarea{width:100%;padding:10px;border:1px solid #ccb8a6;border-radius:6px;font-size:15px}
.btn{display:inline-block;background:#6f4e37;color:#fff;border:0;padding:9px 16px;border-radius:6px;cursor:pointer;text-decoration:none;font-size:14px;margin-top:10px}
.btn:hover{background:#4b2e1e}.btn.red{background:#b3372b}.btn.sm{padding:5px 10px;margin:0}
table{width:100%;border-collapse:collapse}th,td{padding:9px;border-bottom:1px solid #eadfd5;text-align:left;font-size:14px}
th{background:#f4ece4}.tbl{overflow-x:auto}
.msg{padding:10px 14px;border-radius:6px;margin-bottom:15px}.ok{background:#dff3e0;color:#1d6b2a}.err{background:#fbe0dd;color:#9c2a1f}
.badge{background:#6f4e37;color:#fff;padding:2px 8px;border-radius:10px;font-size:12px}
form.inline{display:inline}
</style></head><body><nav><a href='dashboard.php'>&#9749; Coffee Shop</a>";
    if ($u) {
        echo "<a href='dashboard.php'>Dashboard</a><a href='
