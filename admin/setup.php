<?php
require_once __DIR__ . '/../db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$error = '';
$done = false;
try { $pdo = db(); ensure_schema($pdo); $done = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0; } catch (Throwable $e) { $error = $e->getMessage(); }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done) {
    verify_csrf();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    if (!$email) $error = 'Enter a valid email address.';
    elseif (strlen($password) < 12) $error = 'Use a password with at least 12 characters.';
    else {
        try {
            try { $pdo = db(); } catch (PDOException $missingDatabase) {
                $name = env_value('DB_NAME', '');
                if (!$name || !preg_match('/^[A-Za-z0-9_]+$/', $name)) throw new RuntimeException('Set DB_NAME in .env first.');
                $pdoServer = db_server();
                $pdoServer->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $pdo = db();
            }
            ensure_schema($pdo);
            if ((int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) { $done = true; }
            else {
                $stmt = $pdo->prepare('INSERT INTO admins (email,password_hash) VALUES (?,?)');
                $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
                header('Location: /admin/login.php?created=1'); exit;
            }
        } catch (Throwable $e) { $error = $e instanceof PDOException ? 'Could not connect to or create the configured database. Check the DB settings in .env and ensure the database user can create databases.' : $e->getMessage(); }
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin setup — Cox Booking</title><link rel="stylesheet" href="/admin/admin.css"></head><body><main class="login-shell"><section class="login-card"><h1><?= $done ? 'Admin is already set up' : 'Set up your admin login' ?></h1><p><?= $done ? 'An administrator account already exists. Sign in to continue.' : 'Create the first administrator account. This page closes after setup.' ?></p><?php if ($error): ?><div class="admin-alert"><?=e($error)?></div><?php endif; ?><?php if ($done): ?><a class="admin-button" href="/admin/login.php">Go to sign in</a><?php else: ?><form class="admin-form" method="post"><?=csrf_field()?><div class="admin-form-grid"><label class="full">Admin email<input required type="email" name="email" autocomplete="email"></label><label class="full">Password (at least 12 characters)<input required type="password" name="password" minlength="12" autocomplete="new-password"></label></div><p><button class="admin-button" type="submit">Create admin &amp; database</button></p></form><?php endif; ?></section></main></body></html>
