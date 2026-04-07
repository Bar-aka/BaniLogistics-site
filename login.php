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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'client';
    $role = in_array($role, $allowedRoles, true) ? $role : 'client';

    $result = bani_login($identifier, $password, $role);

    if (($result['success'] ?? false) === true) {
        header('Location: ' . bani_dashboard_url($role));
        exit;
    }

    $error = (string) ($result['message'] ?? 'Unable to sign in.');
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
          <div class="portal-grid" style="margin-bottom: 18px;">
            <article class="portal-card" data-role-card="client">
              <span class="portal-tag">Client</span>
              <h3>Client Portal</h3>
              <p>Track shipments, check quotes, and review account activity.</p>
            </article>
            <article class="portal-card" data-role-card="staff">
              <span class="portal-tag">Staff</span>
              <h3>Staff Workspace</h3>
              <p>Coordinate operations, customs handling, and delivery milestones.</p>
            </article>
            <article class="portal-card" data-role-card="admin">
              <span class="portal-tag">Admin</span>
              <h3>Admin Control</h3>
              <p>Manage users, oversight, and business performance from one place.</p>
            </article>
          </div>
          <form method="post" action="login.php?role=<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-grid">
              <label>
                Email Address Or WhatsApp Number
                <input type="text" name="identifier" placeholder="you@example.com or +2547..." required>
              </label>
              <label>
                Role
                <select name="role" id="portalRole" required>
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
                <input type="text" id="portalDestination" value="<?= htmlspecialchars($labels[$role], ENT_QUOTES, 'UTF-8') ?>" readonly>
              </label>
            </div>
            <button type="submit">Sign In</button>
          </form>
          <p class="dashboard-subtitle" style="margin-top:18px;">
            New customer? <a href="register.php">Create a client portal account</a>.
          </p>
        </article>

        <article class="dashboard-card">
          <h3>Access Guide</h3>
          <ul class="dashboard-list">
            <li><span>Client Access<br><small>Registered customers can sign in here</small></span><span class="badge badge-blue">Portal</span></li>
            <li><span>Staff Access<br><small>Internal team credentials</small></span><span class="badge badge-gold">Ops</span></li>
            <li><span>Admin Access<br><small>Management credentials</small></span><span class="badge badge-green">Control</span></li>
          </ul>
          <p class="dashboard-subtitle" style="margin-top:18px;">
            Clients can sign in using either their email address or WhatsApp number, while staff and admin accounts are maintained internally.
          </p>
        </article>
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
    <script>
      (function () {
        const roleSelect = document.getElementById("portalRole");
        const destinationInput = document.getElementById("portalDestination");
        const roleCards = document.querySelectorAll("[data-role-card]");
        const labels = {
          client: "Client Portal",
          staff: "Staff Dashboard",
          admin: "Admin Dashboard"
        };

        if (!roleSelect || !destinationInput) {
          return;
        }

        const syncDestination = function () {
          destinationInput.value = labels[roleSelect.value] || "Client Portal";
          roleCards.forEach(function (card) {
            const isActive = card.getAttribute("data-role-card") === roleSelect.value;
            card.style.borderColor = isActive ? "rgba(27, 116, 228, 0.45)" : "rgba(17, 17, 17, 0.08)";
            card.style.boxShadow = isActive ? "0 18px 34px rgba(27, 116, 228, 0.16)" : "";
            card.style.transform = isActive ? "translateY(-2px)" : "";
          });
        };

        roleCards.forEach(function (card) {
          card.style.cursor = "pointer";
          card.addEventListener("click", function () {
            const nextRole = card.getAttribute("data-role-card");
            if (!nextRole) {
              return;
            }

            roleSelect.value = nextRole;
            syncDestination();
          });
        });

        roleSelect.addEventListener("change", syncDestination);
        syncDestination();
      }());
    </script>
  </body>
</html>
