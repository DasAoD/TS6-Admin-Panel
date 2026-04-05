<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

auth_start();

// Bereits eingeloggt?
if (!empty($_SESSION['ts6admin_logged_in'])) {
    header('Location: /');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (auth_login($user, $pass)) {
        header('Location: /');
        exit;
    }
    $error = t('login.error');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TS6 Admin — Anmelden</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body class="login-body">

<div class="login-wrap">
  <div class="login-logo">
    <div class="login-badge">TS6</div>
    <div class="login-title">Admin Panel</div>
  </div>

  <div class="login-card">
    <div class="login-card-title"><?= t('login.title') ?></div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/login.php" autocomplete="off">
      <div class="form-group">
        <label class="form-label"><?= t('login.user') ?></label>
        <input type="text" name="username" class="form-input"
               value="<?= e($_POST['username'] ?? '') ?>"
               autofocus autocomplete="username">
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('login.pass') ?></label>
        <input type="password" name="password" class="form-input"
               autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary btn-full">
        <?= t('login.btn') ?>
      </button>
    </form>
  </div>

  <div class="login-footer">
    ts6ctl v<?= file_exists(CONFIG_PATH . '/ts6ctl.conf')
        ? trim(shell_exec("grep TS6_INSTALLED_VERSION " . CONFIG_PATH . "/ts6ctl.conf | cut -d'\"' -f2") ?: '—')
        : '—' ?>
  </div>
</div>

</body>
</html>
