<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';

$user = bani_require_roles(['admin', 'staff'], 'staff');
$clientAccounts = bani_list_users('client');
$recordError = '';
$recordMessage = '';
$createdQuote = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = bani_create_quote($_POST);

    if (($result['success'] ?? false) === true) {
        $recordMessage = (string) ($result['message'] ?? 'Quote created successfully.');
        $createdQuote = bani_fetch_quote_by_id((int) ($result['id'] ?? 0));
        $_POST = [];
    } else {
        $recordError = (string) ($result['message'] ?? 'Unable to create quote.');
    }
}

$dashboardUrl = ($user['role'] ?? '') === 'admin' ? 'admin-dashboard.php' : 'staff-dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quote | Bani Global Logistics Limited</title>
    <meta name="description" content="Create a client quote from the secure operations portal.">
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
          <a class="active" href="create-quote.php">Create Quote</a>
          <a href="create-invoice.php">Create Invoice</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Quote workflow</div>
          <h1>Create a commercial quote tied to the right client record.</h1>
          <p>
            Quotes created here feed the commercial history in the client portal and give operations a clean trail from enquiry to shipment planning.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button secondary" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Back to Dashboard</a>
          <a class="button primary" href="create-invoice.php">Move to Invoice</a>
        </div>
      </section>

      <section class="workspace-grid">
        <article class="dashboard-card form-shell">
          <h2>Create Quote</h2>
          <p class="dashboard-subtitle">Tie the amount, route, and freight mode to a registered client account.</p>
          <?php if ($recordError !== ''): ?>
            <div class="result-box show"><strong>Action failed.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <?php if ($recordMessage !== ''): ?>
            <div class="result-box show"><strong>Action completed.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <form method="post" action="create-quote.php">
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
            <div class="form-grid">
              <label>
                Shipment Type
                <input type="text" name="shipment_type" placeholder="Import" value="<?= htmlspecialchars((string) ($_POST['shipment_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
              <label>
                Mode
                <input type="text" name="mode" placeholder="Sea Freight" value="<?= htmlspecialchars((string) ($_POST['mode'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
            </div>
            <div class="form-grid">
              <label>
                Origin
                <input type="text" name="origin" value="<?= htmlspecialchars((string) ($_POST['origin'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
              <label>
                Destination
                <input type="text" name="destination" value="<?= htmlspecialchars((string) ($_POST['destination'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
            </div>
            <div class="form-grid">
              <label>
                Amount
                <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars((string) ($_POST['amount'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
              <label>
                Status
                <input type="text" name="status" placeholder="Pending" value="<?= htmlspecialchars((string) ($_POST['status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
            </div>
            <label>
              Currency
              <input type="text" name="currency" value="<?= htmlspecialchars((string) ($_POST['currency'] ?? 'KES'), ENT_QUOTES, 'UTF-8') ?>" required>
            </label>
            <button type="submit">Create Quote Record</button>
          </form>
        </article>

        <aside class="dashboard-card detail-card">
          <h3>Quote Summary</h3>
          <?php if (is_array($createdQuote)): ?>
            <ul class="dashboard-list">
              <li><span>Quote Number<br><small>Generated reference</small></span><span><?= htmlspecialchars((string) ($createdQuote['quote_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Client<br><small>Commercial account</small></span><span><?= htmlspecialchars((string) ($createdQuote['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Route<br><small>Quoted movement</small></span><span><?= htmlspecialchars((string) (($createdQuote['origin'] ?? '') . ' to ' . ($createdQuote['destination'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Amount<br><small>Quoted total</small></span><span><?= htmlspecialchars((string) (($createdQuote['currency'] ?? 'KES') . ' ' . number_format((float) ($createdQuote['amount'] ?? 0), 2)), ENT_QUOTES, 'UTF-8') ?></span></li>
            </ul>
          <?php else: ?>
            <div class="timeline">
              <div class="timeline-item">
                <strong>Quote appears in the client portal</strong>
                <p>Clients see the quote history tied to their registered account, not as a separate disconnected record.</p>
              </div>
              <div class="timeline-item">
                <strong>Commercial and operations stay linked</strong>
                <p>The same client account can later receive shipment and invoice records against this commercial history.</p>
              </div>
            </div>
          <?php endif; ?>
        </aside>
      </section>
    </main>
    <script src="/js/script.js"></script>
  </body>
</html>
