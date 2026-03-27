<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';

$user = bani_require_roles(['admin', 'staff'], 'staff');
$clientAccounts = bani_list_users('client');
$recordError = '';
$recordMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = bani_create_invoice($_POST);

    if (($result['success'] ?? false) === true) {
        $invoiceId = (int) ($result['id'] ?? 0);

        if ($invoiceId > 0) {
            header('Location: invoice-view.php?id=' . urlencode((string) $invoiceId) . '&created=1');
            exit;
        }

        $recordMessage = (string) ($result['message'] ?? 'Invoice created successfully.');
    } else {
        $recordError = (string) ($result['message'] ?? 'Unable to create invoice.');
    }
}

$dashboardUrl = ($user['role'] ?? '') === 'admin' ? 'admin-dashboard.php' : 'staff-dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice | Bani Global Logistics Limited</title>
    <meta name="description" content="Create and route a client invoice from the secure operations portal.">
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
          <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
          <a href="create-shipment.php">Create Shipment</a>
          <a href="create-quote.php">Create Quote</a>
          <a class="active" href="create-invoice.php">Create Invoice</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Invoice workflow</div>
          <h1>Create a client invoice with a clear delivery trail.</h1>
          <p>
            Build the invoice on its own page, review the final format, and keep the delivery route visible for both the client and the accounts team.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button secondary" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Back to Dashboard</a>
          <a class="button primary" href="create-shipment.php">Create Shipment</a>
        </div>
      </section>

      <section class="workspace-grid">
        <article class="dashboard-card form-shell">
          <h2>Create Invoice</h2>
          <p class="dashboard-subtitle">The invoice will be linked to the selected client and immediately appear in that client's portal.</p>
          <?php if ($recordError !== ''): ?>
            <div class="result-box show"><strong>Action failed.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <?php if ($recordMessage !== ''): ?>
            <div class="result-box show"><strong>Action completed.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <form method="post" action="create-invoice.php">
            <label>
              Client Account
              <select name="client_email" required>
                <option value="">Select client</option>
                <?php foreach ($clientAccounts as $account): ?>
                  <?php $selected = ((string) ($_POST['client_email'] ?? '')) === (string) ($account['email'] ?? ''); ?>
                  <option value="<?= htmlspecialchars((string) ($account['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $selected ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) ($account['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($account['company'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>
              Invoice Description
              <input type="text" name="description" placeholder="Freight handling and customs support" value="<?= htmlspecialchars((string) ($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </label>
            <label>
              Tracking Number
              <input type="text" name="tracking_reference" placeholder="BG990027" value="<?= htmlspecialchars((string) ($_POST['tracking_reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <div class="form-grid">
              <label>
                Amount
                <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars((string) ($_POST['amount'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
              <label>
                Currency
                <input type="text" name="currency" value="<?= htmlspecialchars((string) ($_POST['currency'] ?? 'KES'), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
            </div>
            <div class="form-grid">
              <label>
                Status
                <input type="text" name="status" placeholder="Due" value="<?= htmlspecialchars((string) ($_POST['status'] ?? 'Due'), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
              <label>
                Due Date
                <input type="date" name="due_date" value="<?= htmlspecialchars((string) ($_POST['due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
            </div>
            <button type="submit">Create Invoice Record</button>
          </form>
        </article>

        <aside class="dashboard-card detail-card">
          <h3>Delivery Routing</h3>
          <ul class="dashboard-list">
            <li><span>Primary recipient<br><small>Selected client account email</small></span><span>Client portal</span></li>
            <li><span>Accounts copy<br><small>Internal billing follow-up</small></span><span><?= htmlspecialchars(bani_accounts_email(), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Tracking reference<br><small>Optional shipment number on the invoice</small></span><span>Visible on invoice</span></li>
            <li><span>Portal visibility<br><small>Client sees the invoice as soon as it is created</small></span><span>Immediate</span></li>
          </ul>
          <h3>What happens next</h3>
          <div class="timeline">
            <div class="timeline-item">
              <strong>Invoice record is created</strong>
              <p>The record becomes visible in the client portal and in the admin and staff billing views.</p>
            </div>
            <div class="timeline-item">
              <strong>Invoice format becomes available</strong>
              <p>You are redirected to the invoice view page where the full invoice layout can be reviewed.</p>
            </div>
            <div class="timeline-item">
              <strong>Accounts routing is ready</strong>
              <p>The invoice view includes the client recipient and the internal accounts copy destination for the next email integration step.</p>
            </div>
          </div>
        </aside>
      </section>
    </main>
  </body>
</html>
