<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';

bani_require_role('staff');
$user = bani_current_user();
$recordMessage = '';
$recordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update-shipment') {
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

$shipments = bani_fetch_shipments(null, 12);
$quotes = bani_fetch_quotes(null, 6);
$invoices = bani_fetch_invoices(null, 6);
$incomingRequests = bani_fetch_incoming_requests(null, 6);
$quoteRequests = bani_fetch_quote_requests(8);
$myEmail = (string) ($user['email'] ?? '');
$myShipments = array_values(array_filter(
    $shipments,
    static fn(array $shipment): bool => strtolower((string) ($shipment['assigned_to'] ?? '')) === strtolower($myEmail)
));
$customsShipments = array_values(array_filter(
    $shipments,
    static fn(array $shipment): bool => stripos((string) ($shipment['status'] ?? ''), 'customs') !== false
));
$pendingInvoices = array_values(array_filter(
    $invoices,
    static fn(array $invoice): bool => strtolower((string) ($invoice['status'] ?? '')) !== 'paid'
));
$myIncomingRequests = array_values(array_filter(
    $incomingRequests,
    static fn(array $request): bool => strtolower((string) ($request['assigned_to'] ?? '')) === strtolower($myEmail)
));
$myQuoteRequests = array_values(array_filter(
    $quoteRequests,
    static fn(array $request): bool => strtolower((string) ($request['assigned_to'] ?? '')) === strtolower($myEmail)
));
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
          <h1>Own your assigned shipments and keep milestones moving.</h1>
          <p>
            Signed in as <strong><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>.
            Review your shipment assignments, update milestones, and keep customs, delivery, and invoice follow-up aligned with what the client sees.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button primary" href="create-shipment.php">Create Shipment</a>
          <a class="button secondary" href="create-invoice.php">Create Invoice</a>
          <a class="button secondary" href="track.html">Open Tracking</a>
        </div>
      </section>

      <section class="dashboard-stats">
        <article class="dashboard-stat"><strong><?= count($myShipments) ?></strong><span>My assigned shipments</span></article>
        <article class="dashboard-stat"><strong><?= count($customsShipments) ?></strong><span>Customs queue</span></article>
        <article class="dashboard-stat"><strong><?= count($pendingInvoices) ?></strong><span>Invoices awaiting follow-up</span></article>
        <article class="dashboard-stat"><strong><?= count($myIncomingRequests) + count($myQuoteRequests) ?></strong><span>Assigned requests</span></article>
      </section>

      <?php if ($recordError !== ''): ?>
        <div class="result-box show result-error"><strong>Action failed.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>
      <?php if ($recordMessage !== ''): ?>
        <div class="result-box show result-success"><strong>Action completed.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>My Assignment Board</h2>
          <p class="dashboard-subtitle">Open each detail page to update status history, next step, and client-visible progress.</p>
          <table class="dashboard-table">
            <thead>
              <tr><th>Reference</th><th>Client</th><th>Status</th><th>Next Step</th></tr>
            </thead>
            <tbody>
              <?php if ($myShipments === []): ?>
                <tr><td colspan="4">No shipments are currently assigned to you.</td></tr>
              <?php else: ?>
                <?php foreach ($myShipments as $shipment): ?>
                  <tr>
                    <td><a href="shipment-detail.php?id=<?= (int) ($shipment['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td><?= htmlspecialchars((string) ($shipment['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= stripos((string) ($shipment['status'] ?? ''), 'customs') !== false ? 'badge-gold' : 'badge-blue' ?>"><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars((string) ($shipment['next_step'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h3>Commercial And Intake Queue</h3>
          <ul class="dashboard-list">
            <?php foreach (array_slice($myQuoteRequests, 0, 3) as $request): ?>
              <li>
                <span>
                  <?= htmlspecialchars((string) ($request['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
                  <small><?= htmlspecialchars(ucfirst((string) ($request['request_type'] ?? 'request')), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) ($request['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                </span>
                <span><?= htmlspecialchars((string) (($request['client_email'] ?? '') !== '' ? $request['client_email'] : ($request['phone'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
              </li>
            <?php endforeach; ?>
            <?php foreach (array_slice($myIncomingRequests, 0, 3) as $request): ?>
              <li>
                <span>
                  <?= htmlspecialchars((string) ($request['supplier_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
                  <small><?= htmlspecialchars((string) ($request['supplier_tracking_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) ($request['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                </span>
                <span><?= htmlspecialchars((string) ($request['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
              </li>
            <?php endforeach; ?>
            <?php if ($myQuoteRequests === [] && $myIncomingRequests === []): ?>
              <li><span>No quote or intake requests assigned yet<br><small>Requests assigned by admin will appear here.</small></span><span class="badge badge-green">Clear</span></li>
            <?php endif; ?>
          </ul>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h3>Customs And Delivery Queue</h3>
          <ul class="dashboard-list">
            <?php foreach (array_slice($customsShipments, 0, 5) as $shipment): ?>
              <li>
                <span>
                  <a href="shipment-detail.php?id=<?= (int) ($shipment['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a><br>
                  <small><?= htmlspecialchars((string) ($shipment['next_step'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                </span>
                <span class="badge badge-gold"><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
              </li>
            <?php endforeach; ?>
            <?php if ($customsShipments === []): ?>
              <li><span>No customs tasks queued<br><small>Shipment records in customs will appear here.</small></span><span class="badge badge-green">Clear</span></li>
            <?php endif; ?>
          </ul>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Shipment Processing Queue</h2>
          <table class="dashboard-table">
            <thead>
              <tr><th>Reference</th><th>Owner</th><th>Status</th><th>Quick Update</th></tr>
            </thead>
            <tbody>
              <?php if ($shipments === []): ?>
                <tr><td colspan="4">No shipment records are ready for processing yet.</td></tr>
              <?php else: ?>
                <?php foreach (array_slice($shipments, 0, 6) as $shipment): ?>
                  <tr>
                    <td><a href="shipment-detail.php?id=<?= (int) ($shipment['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td><?= htmlspecialchars((string) ($shipment['assigned_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <form method="post" action="staff-dashboard.php" class="inline-form-stack">
                        <input type="hidden" name="action" value="update-shipment">
                        <input type="hidden" name="shipment_id" value="<?= (int) ($shipment['id'] ?? 0) ?>">
                        <input type="hidden" name="assigned_to" value="<?= htmlspecialchars((string) ($shipment['assigned_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <select name="status">
                          <?php foreach (['In Transit', 'Customs Clearance', 'Released from Customs', 'Out for Delivery', 'Delivered'] as $statusOption): ?>
                            <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?>"<?= (($shipment['status'] ?? '') === $statusOption) ? ' selected' : '' ?>><?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?></option>
                          <?php endforeach; ?>
                        </select>
                        <input type="text" name="next_step" value="<?= htmlspecialchars((string) ($shipment['next_step'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Next step">
                        <textarea name="internal_notes" placeholder="Operational note"><?= htmlspecialchars((string) ($shipment['internal_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        <button type="submit">Save Update</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h2>Invoice Watchlist</h2>
          <table class="dashboard-table">
            <thead>
              <tr><th>Invoice</th><th>Client</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if ($invoices === []): ?>
                <tr><td colspan="4">No invoices available yet.</td></tr>
              <?php else: ?>
                <?php foreach ($invoices as $invoice): ?>
                  <tr>
                    <td><a href="invoice-view.php?id=<?= (int) ($invoice['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td><?= htmlspecialchars((string) ($invoice['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? 'badge-green' : 'badge-red' ?>"><?= htmlspecialchars((string) ($invoice['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                      <form method="post" action="staff-dashboard.php" class="inline-actions">
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
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
  </body>
</html>
