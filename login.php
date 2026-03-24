<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

$allowedRoles = ['client', 'staff', 'admin'];
$role = $_GET['role'] ?? 'client';
$role = in_array($role, $allowedRoles, true) ? $role : 'client';

$labels = [
    'client' => 'Client Portal',
    'staff' => 'Staff Dashboard',
    'admin' => 'Admin Dashboard',
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'client';
    $role = in_array($role, $allowedRoles, true) ? $role : 'client';

    if (bani_login($email, $password, $role)) {
        header('Location: ' . bani_dashboard_url($role));
        exit;
    }

    $error = 'Invalid login details. Please confirm your email, password, and role.';
}

$currentUser = bani_current_user();
if ($currentUser) {
    header('Location: ' . bani_dashboard_url($currentUser['role']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login | Bani Global Logistics Limited</title>
    <meta name="description" content="Portal login for clients, staff, and administrators.">
    <link rel="stylesheet" href="/css/style.css">
  </head>
  <body class="dashboard-page">
    <header class="site-header">
      <div class="nav-wrap">
        <a class="brand" href="index.html" aria-label="Bani Global Logistics Limited home">
          <img src="/images/logo.png" alt="Bani Global Logistics Limited Logo">
        </a>
        <nav class="site-nav" aria-label="Main navigation">
          <a href="index.html">Home</a>
          <a href="about.html">About</a>
          <a href="quote.html">Request Quote</a>
          <a href="track.html">Track Shipment</a>
          <a class="active" href="login.php">Portal Login</a>
          <a href="contact.html">Contact</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Secure portal access</div>
          <h1>Sign in to the right workspace.</h1>
          <p>
            Access the client portal, staff workspace, or admin dashboard through
            one secure login point for the Bani system.
          </p>
        </div>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Portal Login</h2>
          <p class="dashboard-subtitle">Choose your role and continue to the correct workspace.</p>
          <?php if ($error !== ''): ?>
            <div class="result-box show"><strong>Login failed.</strong><p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <form method="post" action="login.php?role=<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-grid">
              <label>
                Email Address
                <input type="email" name="email" placeholder="you@example.com" required>
              </label>
              <label>
                Role
                <select name="role" required>
                  <option value="client" <?= $role === 'client' ? 'selected' : '' ?>>Client</option>
                  <option value="staff" <?= $role === 'staff' ? 'selected' : '' ?>>Staff</option>
                  <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
              </label>
            </div>
            <div class="form-grid">
              <label>
                Password
                <input type="password" name="password" placeholder="Enter password" required>
              </label>
              <label>
                Portal Destination
                <input type="text" value="<?= htmlspecialchars($labels[$role], ENT_QUOTES, 'UTF-8') ?>" readonly>
              </label>
            </div>
            <button type="submit">Sign In</button>
          </form>
        </article>

        <article class="dashboard-card">
          <h3>Access Guide</h3>
          <ul class="dashboard-list">
            <li><span>Client Access<br><small>client@banilogistics.co.ke</small></span><span class="badge badge-blue">Portal</span></li>
            <li><span>Staff Access<br><small>ops@banilogistics.co.ke</small></span><span class="badge badge-gold">Ops</span></li>
            <li><span>Admin Access<br><small>admin@banilogistics.co.ke</small></span><span class="badge badge-green">Control</span></li>
          </ul>
          <p class="dashboard-subtitle" style="margin-top:18px;">
            Default access credentials are configured for immediate system use and can be updated to match your internal team accounts.
          </p>
        </article>
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
  </body>
</html>
