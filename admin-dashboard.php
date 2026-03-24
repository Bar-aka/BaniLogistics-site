<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
bani_require_role('admin');

$accountMessage = '';
$accountError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountEmail = (string) ($_POST['account_email'] ?? '');
    $accountStatus = (string) ($_POST['account_status'] ?? '');

    if (bani_update_user_status($accountEmail, $accountStatus)) {
        $accountMessage = 'Account status updated successfully.';
    } else {
        $accountError = 'Unable to update that account status.';
    }
}

$user = bani_current_user();
$userCounts = bani_user_counts();
$clientAccounts = bani_list_users('client');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Bani Global Logistics Limited</title>
    <meta name="description" content="Administrative dashboard for visibility into revenue, quotes, operations, and staff workload.">
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
          <a href="client-dashboard.php">Client Portal</a>
          <a class="active" href="admin-dashboard.php">Admin Dashboard</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Admin dashboard</div>
          <h1>Monitor business performance, workload, and service health.</h1>
          <p>
            Signed in as <strong><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></strong>.
            Review quotes, invoicing, active jobs, staff productivity, and service performance from a secured management view.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button primary" href="staff-dashboard.php">Open Staff Dashboard</a>
          <a class="button secondary" href="client-dashboard.php">Open Client Portal</a>
        </div>
      </section>

      <section class="dashboard-stats">
        <article class="dashboard-stat"><strong>KES 4.2M</strong><span>Monthly billed volume</span></article>
        <article class="dashboard-stat"><strong><?= (int) $userCounts['client'] ?></strong><span>Client accounts</span></article>
        <article class="dashboard-stat"><strong><?= (int) $userCounts['active'] ?></strong><span>Active system users</span></article>
        <article class="dashboard-stat"><strong><?= (int) $userCounts['staff'] ?></strong><span>Operations team accounts</span></article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Management Snapshot</h2>
          <p class="dashboard-subtitle">Current commercial and operational picture.</p>
          <table class="dashboard-table">
            <thead>
              <tr><th>Area</th><th>Current</th><th>Direction</th></tr>
            </thead>
            <tbody>
              <tr><td>Open Quotes</td><td>13</td><td><span class="badge badge-blue">Steady</span></td></tr>
              <tr><td>Invoices Due</td><td>KES 184K</td><td><span class="badge badge-gold">Review</span></td></tr>
              <tr><td>Delayed Shipments</td><td>3</td><td><span class="badge badge-red">Attention</span></td></tr>
              <tr><td>Cleared Deliveries</td><td>22</td><td><span class="badge badge-green">Healthy</span></td></tr>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h3>Leadership Priorities</h3>
          <div class="timeline">
            <div class="timeline-item"><strong>Follow up overdue invoice queue</strong><p>Accounts to chase 4 customer balances above 14 days.</p></div>
            <div class="timeline-item"><strong>Resolve three delayed import files</strong><p>Operations to clear documentation blockers before noon.</p></div>
            <div class="timeline-item"><strong>Review quote response speed</strong><p>Average turnaround improved, but same-day target still needs tightening.</p></div>
          </div>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Client Account Management</h2>
          <p class="dashboard-subtitle">Manage customer access, registration volume, and account status from one control point.</p>
          <?php if ($accountError !== ''): ?>
            <div class="result-box show"><strong>Update failed.</strong><p><?= htmlspecialchars($accountError, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <?php if ($accountMessage !== ''): ?>
            <div class="result-box show"><strong>Update completed.</strong><p><?= htmlspecialchars($accountMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <table class="dashboard-table">
            <thead>
              <tr><th>Name</th><th>Company</th><th>Email</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php foreach ($clientAccounts as $account): ?>
                <tr>
                  <td><?= htmlspecialchars((string) ($account['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($account['company'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($account['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="badge <?= ($account['status'] ?? 'active') === 'active' ? 'badge-green' : 'badge-red' ?>"><?= htmlspecialchars((string) ($account['status'] ?? 'active'), ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td>
                    <form method="post" action="admin-dashboard.php" class="inline-actions">
                      <input type="hidden" name="account_email" value="<?= htmlspecialchars((string) ($account['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="account_status" value="<?= ($account['status'] ?? 'active') === 'active' ? 'suspended' : 'active' ?>">
                      <button type="submit"><?= ($account['status'] ?? 'active') === 'active' ? 'Suspend' : 'Activate' ?></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h3>Access Summary</h3>
          <ul class="dashboard-list">
            <li><span>Total Accounts<br><small>All roles across the system</small></span><span><?= (int) $userCounts['total'] ?></span></li>
            <li><span>Client Accounts<br><small>Customer portal registrations</small></span><span><?= (int) $userCounts['client'] ?></span></li>
            <li><span>Suspended Accounts<br><small>Temporarily blocked access</small></span><span><?= (int) $userCounts['suspended'] ?></span></li>
          </ul>
        </article>
      </section>

      <section class="dashboard-mini-grid">
        <article class="dashboard-card">
          <h3>Team Workload</h3>
          <ul class="dashboard-list">
            <li><span>Amina<br><small>Clearance and compliance</small></span><span>7 files</span></li>
            <li><span>Brian<br><small>Freight coordination</small></span><span>6 files</span></li>
            <li><span>Mercy<br><small>Delivery operations</small></span><span>5 files</span></li>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Commercial Pipeline</h3>
          <ul class="dashboard-list">
            <li><span>Importer accounts<br><small>3 proposals awaiting approval</small></span><span class="badge badge-blue">Open</span></li>
            <li><span>Export clients<br><small>2 renewals this week</small></span><span class="badge badge-green">Hot</span></li>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Control Center</h3>
          <ul class="dashboard-list">
            <li><span>Operations<br><small>ops@banilogistics.co.ke</small></span><span>Live</span></li>
            <li><span>Accounts<br><small>accounts@banilogistics.co.ke</small></span><span>Billing</span></li>
            <li><span>Support<br><small>support@banilogistics.co.ke</small></span><span>Follow-ups</span></li>
          </ul>
        </article>
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
  </body>
</html>
