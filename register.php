<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

$error = '';
$success = '';
$form = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'company' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = trim((string) ($_POST['name'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $form['company'] = trim((string) ($_POST['company'] ?? ''));

    $result = bani_register_client($_POST);

    if (($result['success'] ?? false) === true) {
        $success = (string) ($result['message'] ?? 'Account created successfully.');
        $form = ['name' => '', 'email' => '', 'phone' => '', 'company' => ''];
    } else {
        $error = (string) ($result['message'] ?? 'Unable to create account.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Client Account | Bani Global Logistics Limited</title>
    <meta name="description" content="Create a client portal account for shipment tracking, quotes, and account updates.">
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
          <a href="login.php">Portal Login</a>
          <a class="active" href="register.php">Create Account</a>
          <a href="contact.html">Contact</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Client registration</div>
          <h1>Create your client portal account.</h1>
          <p>
            Register for secure access to shipment visibility, quote history, and account communication
            through the Bani client portal.
          </p>
        </div>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Client Account Setup</h2>
          <p class="dashboard-subtitle">Complete the form below to activate your portal access using email, WhatsApp number, or both.</p>
          <?php if ($error !== ''): ?>
            <div class="result-box show"><strong>Registration failed.</strong><p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <?php if ($success !== ''): ?>
            <div class="result-box show"><strong>Account created.</strong><p><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <form method="post" action="register.php">
            <div class="form-grid">
              <label>
                Full Name
                <input type="text" name="name" value="<?= htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
              <label>
                Email Address
                <input type="email" name="email" value="<?= htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Optional">
              </label>
            </div>
            <div class="form-grid">
              <label>
                WhatsApp Number
                <input type="text" name="phone" value="<?= htmlspecialchars($form['phone'], ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
              <label>
                Company Name
                <input type="text" name="company" value="<?= htmlspecialchars($form['company'], ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
            </div>
            <div class="form-grid">
              <label>
                Password
                <input type="password" name="password" placeholder="Minimum 8 characters" required>
              </label>
              <label>
                Confirm Password
                <input type="password" name="confirm_password" placeholder="Re-enter password" required>
              </label>
            </div>
            <button type="submit">Create Client Account</button>
          </form>
          <p class="dashboard-subtitle" style="margin-top:18px;">
            Already registered? <a href="login.php?role=client">Sign in to the client portal</a> with your email or WhatsApp number.
          </p>
        </article>

        <article class="dashboard-card">
          <h3>Portal Access Includes</h3>
          <ul class="dashboard-list">
            <li><span>Shipment Visibility<br><small>Track current cargo movement and milestones</small></span><span class="badge badge-blue">Live</span></li>
            <li><span>Quote History<br><small>View submitted requests and active pricing discussions</small></span><span class="badge badge-green">Ready</span></li>
            <li><span>Account Support<br><small>Reach operations, accounts, and support teams faster</small></span><span class="badge badge-gold">Direct</span></li>
          </ul>
        </article>
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
  </body>
</html>
