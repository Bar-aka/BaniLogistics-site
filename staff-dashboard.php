<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';
bani_require_role('staff');
$user = bani_current_user();
$clientAccounts = array_slice(array_reverse(bani_list_users('client')), 0, 5);
$shipments = bani_fetch_shipments(null, 8);
$quotes = bani_fetch_quotes(null, 6);
$invoices = bani_fetch_invoices(null, 6);
$shipmentsPending = count(array_filter($shipments, static fn(array $shipment): bool => stripos((string) ($shipment['status'] ?? ''), 'customs') !== false));
$staffUsers = bani_staff_users();
$recordMessage = '';
$recordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update-shipment') {
        $shipmentId = (int) ($_POST['shipment_id'] ?? 0);
        $result = bani_update_shipment($shipmentId, $_POST);
        if (($result['success'] ?? false) === true) {
            $recordMessage = (string) $result['message'];
            $shipments = bani_fetch_shipments(null, 8);
            $shipmentsPending = count(array_filter($shipments, static fn(array $shipment): bool => stripos((string) ($shipment['status'] ?? ''), 'customs') !== false));
        } else {
            $recordError = (string) ($result['message'] ?? 'Unable to update shipment.');
        }
    } elseif ($action === 'update-invoice-status') {
        $invoiceId = (int) ($_POST['invoice_id'] ?? 0);
        $invoiceStatus = (string) ($_POST['invoice_status'] ?? '');
        $result = bani_update_invoice_status($invoiceId, $invoiceStatus);
        if (($result['success'] ?? false) === true) {
            $recordMessage = (string) $result['message'];
            $invoices = bani_fetch_invoices(null, 6);
        } else {
            $recordError = (string) ($result['message'] ?? 'Unable to update invoice.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Bani Global Logistics Limited</title>
    <meta name="description" content="Operational dashboard for staff managing shipments, customs, and customer handoffs.">
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
          <a class="active" href="staff-dashboard.php">Staff Dashboard</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Staff dashboard</div>
          <h1>Coordinate daily operations with cleaner shipment visibility.</h1>
          <p>
            Signed in as <strong><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></strong>.
            Manage active jobs, customs handoffs, route coordination, and customer-facing checkpoints from one secure operations view.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button primary" href="track.html">Open Tracking Page</a>
          <a class="button secondary" href="contact.html">Support Desk</a>
        </div>
      </section>

      <section class="dashboard-stats">
        <article class="dashboard-stat"><strong><?= count($shipments) ?></strong><span>Tracked shipments</span></article>
        <article class="dashboard-stat"><strong><?= $shipmentsPending ?></strong><span>Customs-related files</span></article>
        <article class="dashboard-stat"><strong><?= count($quotes) ?></strong><span>Open commercial records</span></article>
        <article class="dashboard-stat"><strong><?= count($invoices) ?></strong><span>Invoice records</span></article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Operations Queue</h2>
          <p class="dashboard-subtitle">Priority shipment actions requiring staff attention.</p>
          <?php if ($recordError !== ''): ?>
            <div class="result-box show"><strong>Action failed.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <?php if ($recordMessage !== ''): ?>
            <div class="result-box show"><strong>Action completed.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <table class="dashboard-table">
            <thead>
              <tr><th>Ref</th><th>Task</th><th>Owner</th><th>Priority</th></tr>
            </thead>
            <tbody>
              <?php if ($shipments === []): ?>
                <tr><td colspan="4">No shipment records are available yet.</td></tr>
              <?php else: ?>
                <?php foreach (array_slice($shipments, 0, 5) as $shipment): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($shipment['next_step'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($shipment['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= stripos((string) ($shipment['status'] ?? ''), 'customs') !== false ? 'badge-gold' : 'badge-blue' ?>"><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h3>Shipment Processing</h3>
          <table class="dashboard-table">
            <thead>
              <tr><th>Reference</th><th>Assigned</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if ($shipments === []): ?>
                <tr><td colspan="4">No shipment records are ready for processing yet.</td></tr>
              <?php else: ?>
                <?php foreach (array_slice($shipments, 0, 4) as $shipment): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($shipment['assigned_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <form method="post" action="staff-dashboard.php" class="inline-actions">
                        <input type="hidden" name="action" value="update-shipment">
                        <input type="hidden" name="shipment_id" value="<?= (int) ($shipment['id'] ?? 0) ?>">
                        <input type="hidden" name="assigned_to" value="<?= htmlspecialchars((string) ($shipment['assigned_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="status" value="<?= stripos((string) ($shipment['status'] ?? ''), 'customs') !== false ? 'Released from Customs' : 'Out for Delivery' ?>">
                        <input type="hidden" name="next_step" value="<?= stripos((string) ($shipment['status'] ?? ''), 'customs') !== false ? 'Dispatch scheduling after customs release' : 'Final client handoff in progress' ?>">
                        <input type="hidden" name="internal_notes" value="<?= htmlspecialchars((string) ($shipment['internal_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit"><?= stripos((string) ($shipment['status'] ?? ''), 'customs') !== false ? 'Release Customs' : 'Advance Status' ?></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </article>
      </section>

      <section class="dashboard-mini-grid">
        <article class="dashboard-card">
          <h3>Recent Client Accounts</h3>
          <ul class="dashboard-list">
            <?php foreach ($clientAccounts as $account): ?>
              <li>
                <span>
                  <?= htmlspecialchars((string) ($account['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
                  <small><?= htmlspecialchars((string) ($account['company'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                </span>
                <span class="badge <?= ($account['status'] ?? 'active') === 'active' ? 'badge-green' : 'badge-red' ?>"><?= htmlspecialchars((string) ($account['status'] ?? 'active'), ENT_QUOTES, 'UTF-8') ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Clearance Board</h3>
          <ul class="dashboard-list">
            <?php foreach (array_slice($shipments, 0, 3) as $shipment): ?>
              <li><span><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars((string) ($shipment['next_step'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></span><span class="badge <?= stripos((string) ($shipment['status'] ?? ''), 'customs') !== false ? 'badge-gold' : 'badge-green' ?>"><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <?php endforeach; ?>
            <?php if ($shipments === []): ?>
              <li><span>No clearance records yet<br><small>Create shipment records from admin to populate this queue.</small></span><span class="badge badge-blue">Awaiting</span></li>
            <?php endif; ?>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Assignments</h3>
          <ul class="dashboard-list">
            <?php foreach (array_slice($shipments, 0, 3) as $shipment): ?>
              <li><span><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars((string) ($shipment['assigned_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></small></span><span><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <?php endforeach; ?>
            <?php if ($shipments === []): ?>
              <li><span>No assignments yet<br><small>Assigned shipments will appear here.</small></span><span>Pending</span></li>
            <?php endif; ?>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Invoice Watchlist</h3>
          <ul class="dashboard-list">
            <?php foreach (array_slice($invoices, 0, 3) as $invoice): ?>
              <li>
                <span><?= htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars((string) ($invoice['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></span>
                <form method="post" action="staff-dashboard.php" class="inline-actions">
                  <input type="hidden" name="action" value="update-invoice-status">
                  <input type="hidden" name="invoice_id" value="<?= (int) ($invoice['id'] ?? 0) ?>">
                  <input type="hidden" name="invoice_status" value="<?= strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? 'Due' : 'Paid' ?>">
                  <button type="submit"><?= strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? 'Mark Due' : 'Mark Paid' ?></button>
                </form>
              </li>
            <?php endforeach; ?>
            <?php if ($invoices === []): ?>
              <li><span>No invoices yet<br><small>Invoice records will appear here after creation.</small></span><span class="badge badge-blue">Awaiting</span></li>
            <?php endif; ?>
          </ul>
        </article>
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
  </body>
</html>
