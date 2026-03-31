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
    } elseif ($action === 'update-incoming-status') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $incomingStatus = (string) ($_POST['incoming_status'] ?? '');
        $result = bani_update_incoming_request_status($requestId, $incomingStatus);
        if (($result['success'] ?? false) === true) {
            $recordMessage = (string) $result['message'];
        } else {
            $recordError = (string) ($result['message'] ?? 'Unable to update incoming cargo request.');
        }
    } elseif ($action === 'convert-incoming') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $result = bani_convert_incoming_request_to_shipment($requestId, $_POST);
        if (($result['success'] ?? false) === true) {
            $recordMessage = (string) $result['message'];
        } else {
            $recordError = (string) ($result['message'] ?? 'Unable to convert incoming request.');
        }
    }
}

$user = bani_current_user();
$userCounts = bani_user_counts();
$clientAccounts = bani_list_users('client');
$storageMode = bani_db_ready() && bani_db() instanceof PDO ? 'MySQL Database' : 'Local Account Storage';
$shipments = bani_fetch_shipments(null, 12);
$quotes = bani_fetch_quotes(null, 8);
$invoices = bani_fetch_invoices(null, 8);
$incomingRequests = bani_fetch_incoming_requests(null, 8);
$staffUsers = bani_staff_users();
$dueInvoices = count(array_filter($invoices, static fn(array $invoice): bool => strtolower((string) ($invoice['status'] ?? '')) !== 'paid'));
$customsShipments = bani_count_shipments_by_status($shipments, 'customs');
$deliveredShipments = bani_count_shipments_by_status($shipments, 'delivered');
$outstandingValue = array_reduce($invoices, static function (float $carry, array $invoice): float {
    return strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? $carry : $carry + (float) ($invoice['amount'] ?? 0);
}, 0.0);
$activeIncoming = count(array_filter($incomingRequests, static fn(array $request): bool => strtolower((string) ($request['status'] ?? 'submitted')) !== 'closed'));
$assignedShipments = count(array_filter($shipments, static fn(array $shipment): bool => trim((string) ($shipment['assigned_to'] ?? '')) !== ''));
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
          <h1>Run shipments, billing, and team ownership from one control center.</h1>
          <p>
            Signed in as <strong><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>.
            Manage client-submitted incoming cargo details, open shipment records, and move every milestone forward with full operational visibility.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button primary" href="create-shipment.php">Create Shipment</a>
          <a class="button secondary" href="create-invoice.php">Create Invoice</a>
          <a class="button secondary" href="create-quote.php">Create Quote</a>
        </div>
      </section>

      <section class="dashboard-stats">
        <article class="dashboard-stat"><strong>KES <?= number_format($outstandingValue, 2) ?></strong><span>Outstanding invoice value</span></article>
        <article class="dashboard-stat"><strong><?= (int) $userCounts['client'] ?></strong><span>Client accounts</span></article>
        <article class="dashboard-stat"><strong><?= $activeIncoming ?></strong><span>Incoming cargo alerts</span></article>
        <article class="dashboard-stat"><strong><?= $assignedShipments ?></strong><span>Assigned shipments</span></article>
      </section>

      <?php if ($recordError !== ''): ?>
        <div class="result-box show result-error"><strong>Action failed.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>
      <?php if ($recordMessage !== ''): ?>
        <div class="result-box show result-success"><strong>Action completed.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>

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
              <tr><td>Invoices Due</td><td><?= $dueInvoices ?></td><td><span class="badge <?= $dueInvoices > 0 ? 'badge-gold' : 'badge-green' ?>"><?= $dueInvoices > 0 ? 'Review' : 'Clear' ?></span></td></tr>
              <tr><td>Shipments in Customs</td><td><?= $customsShipments ?></td><td><span class="badge <?= $customsShipments > 0 ? 'badge-gold' : 'badge-green' ?>"><?= $customsShipments > 0 ? 'Active' : 'Clear' ?></span></td></tr>
              <tr><td>Delivered Shipments</td><td><?= $deliveredShipments ?></td><td><span class="badge badge-green">Completed</span></td></tr>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h3>Leadership Priorities</h3>
          <div class="timeline">
            <div class="timeline-item"><strong><?= $activeIncoming ?> incoming alerts need intake review</strong><p>Convert client supplier references into shipment records as soon as origin, destination, and mode are confirmed.</p></div>
            <div class="timeline-item"><strong><?= $assignedShipments ?> shipments already have owners</strong><p>Use the shipment detail pages to keep status history and ownership accurate.</p></div>
            <div class="timeline-item"><strong><?= $dueInvoices ?> invoices remain open</strong><p>Accounts follow-up stays easier when invoice status is updated promptly.</p></div>
          </div>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Incoming Requests To Convert</h2>
          <p class="dashboard-subtitle">Turn client-submitted supplier details into live shipment records without retyping everything later.</p>
          <table class="dashboard-table">
            <thead>
              <tr><th>Supplier Tracking</th><th>Client</th><th>Item</th><th>Status</th><th>Convert</th></tr>
            </thead>
            <tbody>
              <?php if ($incomingRequests === []): ?>
                <tr><td colspan="5">No incoming supplier requests have been submitted yet.</td></tr>
              <?php else: ?>
                <?php foreach ($incomingRequests as $incoming): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) ($incoming['supplier_tracking_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($incoming['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($incoming['item_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge-blue"><?= htmlspecialchars((string) ($incoming['status'] ?? 'Submitted'), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                      <form method="post" action="admin-dashboard.php" class="inline-form-stack">
                        <input type="hidden" name="action" value="convert-incoming">
                        <input type="hidden" name="request_id" value="<?= (int) ($incoming['id'] ?? 0) ?>">
                        <input type="text" name="destination" placeholder="Destination" required>
                        <select name="mode">
                          <option value="Air Freight">Air Freight</option>
                          <option value="Sea Freight">Sea Freight</option>
                          <option value="Road Delivery">Road Delivery</option>
                        </select>
                        <select name="assigned_to">
                          <option value="">Assign later</option>
                          <?php foreach ($staffUsers as $staffUser): ?>
                            <option value="<?= htmlspecialchars((string) ($staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                              <?= htmlspecialchars((string) ($staffUser['name'] ?? $staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                        <input type="text" name="next_step" value="Awaiting supplier release and transit booking" required>
                        <button type="submit">Convert To Shipment</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h3>Incoming Request Board</h3>
          <ul class="dashboard-list">
            <?php foreach (array_slice($incomingRequests, 0, 5) as $incoming): ?>
              <li>
                <span>
                  <?= htmlspecialchars((string) ($incoming['supplier_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
                  <small><?= htmlspecialchars((string) ($incoming['supplier_tracking_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) ($incoming['origin'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                </span>
                <form method="post" action="admin-dashboard.php" class="inline-actions">
                  <input type="hidden" name="action" value="update-incoming-status">
                  <input type="hidden" name="request_id" value="<?= (int) ($incoming['id'] ?? 0) ?>">
                  <input type="hidden" name="incoming_status" value="<?= strtolower((string) ($incoming['status'] ?? '')) === 'closed' ? 'Reviewing' : 'Closed' ?>">
                  <button type="submit"><?= strtolower((string) ($incoming['status'] ?? '')) === 'closed' ? 'Reopen' : 'Close' ?></button>
                </form>
              </li>
            <?php endforeach; ?>
            <?php if ($incomingRequests === []): ?>
              <li><span>No incoming alerts yet<br><small>Client-submitted supplier references will appear here.</small></span><span>Awaiting</span></li>
            <?php endif; ?>
          </ul>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Shipment Control Center</h2>
          <p class="dashboard-subtitle">Open the shipment detail page for full milestone history, or update ownership and next step directly from here.</p>
          <table class="dashboard-table">
            <thead>
              <tr><th>Reference</th><th>Client</th><th>Owner</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if ($shipments === []): ?>
                <tr><td colspan="5">No shipment records available yet.</td></tr>
              <?php else: ?>
                <?php foreach ($shipments as $shipment): ?>
                  <tr>
                    <td><a href="shipment-detail.php?id=<?= (int) ($shipment['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td><?= htmlspecialchars((string) ($shipment['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($shipment['assigned_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= stripos((string) ($shipment['status'] ?? ''), 'customs') !== false ? 'badge-gold' : 'badge-blue' ?>"><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                      <form method="post" action="admin-dashboard.php" class="inline-form-stack">
                        <input type="hidden" name="action" value="update-shipment">
                        <input type="hidden" name="shipment_id" value="<?= (int) ($shipment['id'] ?? 0) ?>">
                        <select name="status">
                          <?php foreach (['Submitted', 'Order Confirmed', 'In Transit', 'Customs Clearance', 'Released from Customs', 'Out for Delivery', 'Delivered'] as $statusOption): ?>
                            <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?>"<?= (($shipment['status'] ?? '') === $statusOption) ? ' selected' : '' ?>><?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?></option>
                          <?php endforeach; ?>
                        </select>
                        <input type="text" name="next_step" value="<?= htmlspecialchars((string) ($shipment['next_step'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Next step">
                        <select name="assigned_to">
                          <option value="">Unassigned</option>
                          <?php foreach ($staffUsers as $staffUser): ?>
                            <option value="<?= htmlspecialchars((string) ($staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"<?= (($shipment['assigned_to'] ?? '') === ($staffUser['email'] ?? '')) ? ' selected' : '' ?>>
                              <?= htmlspecialchars((string) ($staffUser['name'] ?? $staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                        <textarea name="internal_notes" placeholder="Internal operations note"><?= htmlspecialchars((string) ($shipment['internal_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
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
          <h3>Team Ownership</h3>
          <ul class="dashboard-list">
            <?php foreach ($staffUsers as $staffUser): ?>
              <?php $owned = array_filter($shipments, static fn(array $shipment): bool => ($shipment['assigned_to'] ?? '') === ($staffUser['email'] ?? '')); ?>
              <li>
                <span>
                  <?= htmlspecialchars((string) ($staffUser['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
                  <small><?= htmlspecialchars((string) ($staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                </span>
                <span><?= count($owned) ?> active tasks</span>
              </li>
            <?php endforeach; ?>
            <?php if ($staffUsers === []): ?>
              <li><span>No staff accounts found<br><small>Create staff users when ready.</small></span><span>Awaiting</span></li>
            <?php endif; ?>
          </ul>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Invoice Actions</h2>
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
          <h2>Client Account Management</h2>
          <?php if ($accountError !== ''): ?>
            <div class="result-box show result-error"><strong>Update failed.</strong><p><?= htmlspecialchars($accountError, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <?php if ($accountMessage !== ''): ?>
            <div class="result-box show result-success"><strong>Update completed.</strong><p><?= htmlspecialchars($accountMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
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
          <p class="muted">Storage mode: <?= htmlspecialchars($storageMode, ENT_QUOTES, 'UTF-8') ?></p>
        </article>
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
  </body>
</html>
