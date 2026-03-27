<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';
bani_require_role('admin');

$accountMessage = '';
$accountError = '';
$recordMessage = '';
$recordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update-account-status') {
        $accountEmail = (string) ($_POST['account_email'] ?? '');
        $accountStatus = (string) ($_POST['account_status'] ?? '');

        if (bani_update_user_status($accountEmail, $accountStatus)) {
            $accountMessage = 'Account status updated successfully.';
        } else {
            $accountError = 'Unable to update that account status.';
        }
    } elseif ($action === 'update-shipment') {
        $shipmentId = (int) ($_POST['shipment_id'] ?? 0);
        $result = bani_update_shipment($shipmentId, $_POST);
        if (($result['success'] ?? false) === true) {
            $recordMessage = (string) $result['message'];
        } else {
            $recordError = (string) ($result['message'] ?? 'Unable to update shipment.');
        }
    } elseif ($action === 'update-invoice-status') {
        $invoiceId = (int) ($_POST['invoice_id'] ?? 0);
        $invoiceStatus = (string) ($_POST['invoice_status'] ?? '');
        $result = bani_update_invoice_status($invoiceId, $invoiceStatus);
        if (($result['success'] ?? false) === true) {
            $recordMessage = (string) $result['message'];
        } else {
            $recordError = (string) ($result['message'] ?? 'Unable to update invoice.');
        }
    }
}

$user = bani_current_user();
$userCounts = bani_user_counts();
$clientAccounts = bani_list_users('client');
$storageMode = bani_db_ready() && bani_db() instanceof PDO ? 'MySQL Database' : 'Local Account Storage';
$shipments = bani_fetch_shipments(null, 8);
$quotes = bani_fetch_quotes(null, 8);
$invoices = bani_fetch_invoices(null, 8);
$staffUsers = bani_staff_users();
$dueInvoices = count(array_filter($invoices, static fn(array $invoice): bool => strtolower((string) ($invoice['status'] ?? '')) !== 'paid'));
$paidInvoices = count($invoices) - $dueInvoices;
$customsShipments = bani_count_shipments_by_status($shipments, 'customs');
$deliveredShipments = bani_count_shipments_by_status($shipments, 'delivered');
$outstandingValue = array_reduce($invoices, static function (float $carry, array $invoice): float {
    return strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? $carry : $carry + (float) ($invoice['amount'] ?? 0);
}, 0.0);
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
          <a class="button primary" href="create-invoice.php">Create Invoice</a>
          <a class="button secondary" href="create-shipment.php">Create Shipment</a>
          <a class="button secondary" href="create-quote.php">Create Quote</a>
        </div>
        <div class="dashboard-actions">
          <a class="button primary" href="staff-dashboard.php">Open Staff Dashboard</a>
          <a class="button secondary" href="client-dashboard.php">Open Client Portal</a>
        </div>
      </section>

      <section class="dashboard-stats">
        <article class="dashboard-stat"><strong>KES <?= number_format($outstandingValue, 2) ?></strong><span>Outstanding invoice value</span></article>
        <article class="dashboard-stat"><strong><?= (int) $userCounts['client'] ?></strong><span>Client accounts</span></article>
        <article class="dashboard-stat"><strong><?= count($shipments) ?></strong><span>Tracked shipments</span></article>
        <article class="dashboard-stat"><strong><?= $dueInvoices ?></strong><span>Invoices awaiting payment</span></article>
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
              <tr><td>Open Quotes</td><td><?= count($quotes) ?></td><td><span class="badge badge-blue">Live</span></td></tr>
              <tr><td>Invoices Due</td><td>KES <?= number_format($outstandingValue, 2) ?></td><td><span class="badge <?= $dueInvoices > 0 ? 'badge-gold' : 'badge-green' ?>"><?= $dueInvoices > 0 ? 'Review' : 'Clear' ?></span></td></tr>
              <tr><td>Shipments in Customs</td><td><?= $customsShipments ?></td><td><span class="badge <?= $customsShipments > 0 ? 'badge-gold' : 'badge-green' ?>"><?= $customsShipments > 0 ? 'Active' : 'Clear' ?></span></td></tr>
              <tr><td>Delivered Shipments</td><td><?= $deliveredShipments ?></td><td><span class="badge badge-green">Completed</span></td></tr>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h3>Leadership Priorities</h3>
          <div class="timeline">
            <div class="timeline-item"><strong><?= $dueInvoices ?> invoices need follow-up</strong><p>Outstanding billing records remain visible until they are marked paid.</p></div>
            <div class="timeline-item"><strong><?= $customsShipments ?> shipment records are in customs</strong><p>Operations teams can move them forward by updating status and next step.</p></div>
            <div class="timeline-item"><strong><?= count($quotes) ?> quotes are currently logged</strong><p>Commercial activity is now tied directly to registered client accounts.</p></div>
          </div>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Operations Workflow</h2>
          <p class="dashboard-subtitle">Open the dedicated pages for shipments, quotes, and invoices instead of creating everything inside one screen.</p>
          <?php if ($recordError !== ''): ?>
            <div class="result-box show"><strong>Action failed.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <?php if ($recordMessage !== ''): ?>
            <div class="result-box show"><strong>Action completed.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <?php if (!bani_records_ready()): ?>
            <div class="result-box show"><strong>Database tables pending.</strong><p>Run the updated SQL schema import first so shipment, quote, and invoice records can be stored.</p></div>
          <?php endif; ?>
          <div class="workflow-grid">
            <article class="dashboard-card workflow-card">
              <h3>Create Shipment</h3>
              <p class="muted">Open a dedicated shipment page to key in routing, assignment, next step, and internal notes.</p>
              <a class="button primary" href="create-shipment.php">Open Shipment Page</a>
            </article>
            <article class="dashboard-card workflow-card">
              <h3>Create Quote</h3>
              <p class="muted">Prepare a client-linked commercial quote on its own page and keep it tied to the same account history.</p>
              <a class="button primary" href="create-quote.php">Open Quote Page</a>
            </article>
            <article class="dashboard-card workflow-card">
              <h3>Create Invoice</h3>
              <p class="muted">Build an invoice, then review the final invoice format and the accounts-team copy route before delivery.</p>
              <a class="button primary" href="create-invoice.php">Open Invoice Page</a>
            </article>
          </div>
        </article>

        <article class="dashboard-card">
          <h3>Recent Operations Records</h3>
          <ul class="dashboard-list">
            <li><span>Shipments Logged<br><small>Current stored shipment records</small></span><span><?= count($shipments) ?></span></li>
            <li><span>Quotes Logged<br><small>Current stored quote records</small></span><span><?= count($quotes) ?></span></li>
            <li><span>Invoices Logged<br><small>Current stored invoice records</small></span><span><?= count($invoices) ?></span></li>
            <li><span>Paid Invoices<br><small>Billing records marked settled</small></span><span><?= $paidInvoices ?></span></li>
          </ul>
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
              <tr><th>Name</th><th>Company</th><th>Email</th><th>Status</th><th>Created</th><th>Last Login</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php foreach ($clientAccounts as $account): ?>
                <tr>
                  <td><?= htmlspecialchars((string) ($account['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($account['company'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($account['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="badge <?= ($account['status'] ?? 'active') === 'active' ? 'badge-green' : 'badge-red' ?>"><?= htmlspecialchars((string) ($account['status'] ?? 'active'), ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td><?= htmlspecialchars(bani_format_datetime($account['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars(bani_format_datetime($account['last_login_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <form method="post" action="admin-dashboard.php" class="inline-actions">
                      <input type="hidden" name="action" value="update-account-status">
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
            <li><span>Latest Registration<br><small>Most recent client account created</small></span><span><?= htmlspecialchars(isset($clientAccounts[0]) ? bani_format_datetime($clientAccounts[array_key_last($clientAccounts)]['created_at'] ?? null) : 'Not available', ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Storage Mode<br><small>Current account backend in use</small></span><span><?= htmlspecialchars($storageMode, ENT_QUOTES, 'UTF-8') ?></span></li>
          </ul>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Latest Shipments</h2>
          <table class="dashboard-table">
            <thead>
              <tr><th>Reference</th><th>Client</th><th>Route</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php if ($shipments === []): ?>
                <tr><td colspan="4">No shipment records available yet.</td></tr>
              <?php else: ?>
                <?php foreach ($shipments as $shipment): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($shipment['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) (($shipment['origin'] ?? '') . ' to ' . ($shipment['destination'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h2>Latest Quotes & Invoices</h2>
          <div class="timeline">
            <?php foreach (array_slice($quotes, 0, 4) as $quote): ?>
              <div class="timeline-item">
                <strong><?= htmlspecialchars((string) ($quote['quote_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars((string) ($quote['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                <p><?= htmlspecialchars((string) (($quote['currency'] ?? 'KES') . ' ' . number_format((float) ($quote['amount'] ?? 0), 2) . ' • ' . ($quote['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            <?php endforeach; ?>
            <?php foreach (array_slice($invoices, 0, 4) as $invoice): ?>
              <div class="timeline-item">
                <strong><?= htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars((string) ($invoice['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                <p><?= htmlspecialchars((string) (($invoice['currency'] ?? 'KES') . ' ' . number_format((float) ($invoice['amount'] ?? 0), 2) . ' • Due ' . ($invoice['due_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            <?php endforeach; ?>
            <?php if ($quotes === [] && $invoices === []): ?>
              <div class="timeline-item"><strong>No records yet</strong><p>Quotes and invoices will appear here after creation.</p></div>
            <?php endif; ?>
          </div>
        </article>
      </section>

      <section class="dashboard-mini-grid">
        <article class="dashboard-card">
          <h3>Shipment Processing</h3>
          <table class="dashboard-table">
            <thead>
              <tr><th>Reference</th><th>Assigned</th><th>Status</th><th>Update</th></tr>
            </thead>
            <tbody>
              <?php if ($shipments === []): ?>
                <tr><td colspan="4">No shipments available for processing yet.</td></tr>
              <?php else: ?>
                <?php foreach (array_slice($shipments, 0, 4) as $shipment): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($shipment['assigned_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <form method="post" action="admin-dashboard.php" class="inline-actions">
                        <input type="hidden" name="action" value="update-shipment">
                        <input type="hidden" name="shipment_id" value="<?= (int) ($shipment['id'] ?? 0) ?>">
                        <input type="hidden" name="status" value="Delivered">
                        <input type="hidden" name="next_step" value="Customer delivery confirmation completed">
                        <input type="hidden" name="assigned_to" value="<?= htmlspecialchars((string) ($shipment['assigned_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="internal_notes" value="<?= htmlspecialchars((string) ($shipment['internal_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Mark Delivered</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </article>
        <article class="dashboard-card">
          <h3>Invoice Actions</h3>
          <table class="dashboard-table">
            <thead>
              <tr><th>Invoice</th><th>Client</th><th>Status</th><th>Update</th></tr>
            </thead>
            <tbody>
              <?php if ($invoices === []): ?>
                <tr><td colspan="4">No invoices available yet.</td></tr>
              <?php else: ?>
                <?php foreach (array_slice($invoices, 0, 4) as $invoice): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($invoice['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($invoice['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <form method="post" action="admin-dashboard.php" class="inline-actions">
                        <input type="hidden" name="action" value="update-invoice-status">
                        <input type="hidden" name="invoice_id" value="<?= (int) ($invoice['id'] ?? 0) ?>">
                        <input type="hidden" name="invoice_status" value="<?= strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? 'Due' : 'Paid' ?>">
                        <button type="submit"><?= strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? 'Mark Due' : 'Mark Paid' ?></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </article>
        <article class="dashboard-card">
          <h3>Operations Team</h3>
          <ul class="dashboard-list">
            <?php foreach ($staffUsers as $staffUser): ?>
              <li><span><?= htmlspecialchars((string) ($staffUser['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars((string) ($staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></span><span><?= htmlspecialchars((string) ($staffUser['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <?php endforeach; ?>
          </ul>
        </article>
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
  </body>
</html>
