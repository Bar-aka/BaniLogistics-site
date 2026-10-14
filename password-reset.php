<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

$token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = bani_complete_staff_password_reset(
        $token,
        (string) ($_POST['password'] ?? ''),
        (string) ($_POST['confirm_password'] ?? '')
    );
    if (($result['success'] ?? false) === true) {
        header('Location: login.php?role=staff&reset=success');
        exit;
    }
    $error = (string) ($result['message'] ?? 'Unable to reset your password.');
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Reset Portal Password | Bani Global Logistics Limited</title>
    <link rel="stylesheet" href="/css/style.css">
  </head>
  <body class="dashboard-page">
    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Portal account security</div>
          <h1>Choose a new password</h1>
          <p>This one-time reset link expires after 60 minutes. Your new password must be at least 12 characters long.</p>
        </div>
      </section>

      <?php if ($error !== ''): ?>
        <div class="result-box show result-error"><strong>Password not changed.</strong><p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>

      <section class="dashboard-card detail-card">
        <form method="post" action="password-reset.php">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
          <label>
            New password
            <input type="password" name="password" minlength="12" autocomplete="new-password" required>
          </label>
          <label>
            Confirm new password
            <input type="password" name="confirm_password" minlength="12" autocomplete="new-password" required>
          </label>
          <button type="submit">Reset password</button>
        </form>
        <p><a href="login.php?role=staff">Return to staff sign in</a></p>
      </section>
    </main>
  </body>
</html>
