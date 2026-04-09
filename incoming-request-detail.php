<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';

$user = bani_require_roles(['admin', 'staff'], 'admin');
$requestId = (int) ($_GET['id'] ?? $_POST['request_id'] ?? 0);
$incoming = bani_fetch_incoming_request_by_id($requestId);

if (!is_array($incoming)) {
    http_response_code(404);
    exit('Incoming request not found.');
}

$recordMessage = '';
$recordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'update');

    if ($action === 'convert') {
        $result = bani_convert_incoming_request_to_shipment($requestId, $_POST);
    } else {
        $result = bani_update_incoming_request_status($requestId, $_POST);
    }

    if (($result['success'] ?? false) === true) {
        $recordMessage = (string) ($result['message'] ?? 'Incoming request updated.');
        $incoming = bani_fetch_incoming_request_by_id($requestId);
    } else {
        $recordError = (string) ($result['message'] ?? 'Unable to update incoming request.');
    }
}

$staffUsers = bani_staff_users();
$dashboardUrl = ($user['role'] ?? '') === 'admin' ? 'admin-dashboard.php' : 'staff-dashboard.php';
$linkedShipmentId = (int) ($incoming['linked_shipment_id'] ?? 0);
$linkedShipmentReference = (string) ($incoming['linked_shipment_reference'] ?? '');
$whatsAppMessage = 'Hello ' . (string) ($incoming['client_name'] ?? 'Client') . ', this is Bani Global Logistics following up on your incoming package notification for supplier tracking ' . (string) ($incoming['supplier_tracking_number'] ?? '') . '.';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incoming Request Detail | Bani Global Logistics Limited</title>
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
          <a class="active" href="incoming-request-detail.php?id=<?= (int) $requestId ?>">Incoming Request</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Incoming package detail</div>
          <h1><?= htmlspecialchars((string) ($incoming['supplier_tracking_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
          <p>
            <?= htmlspecialchars((string) ($incoming['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            | <?= htmlspecialchars((string) ($incoming['supplier_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            | Status: <strong><?= htmlspecialchars((string) ($incoming['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button secondary" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Back to Dashboard</a>
          <a class="button secondary" href="<?= htmlspecialchars(bani_whatsapp_link($whatsAppMessage), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">WhatsApp Client</a>
          <?php if (!empty($incoming['client_email'])): ?>
            <a class="button primary" href="mailto:<?= rawurlencode((string) $incoming['client_email']) ?>">Email Client</a>
          <?php endif; ?>
        </div>
      </section>

      <?php if ($recordError !== ''): ?>
        <div class="result-box show result-error"><strong>Action failed.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>
      <?php if ($recordMessage !== ''): ?>
        <div class="result-box show result-success"><strong>Action completed.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>

      <section class="dashboard-grid">
        <article class="dashboard-card detail-card">
          <h2>Request Summary</h2>
          <ul class="dashboard-list">
            <li><span>Client<br><small>Portal account</small></span><span><?= htmlspecialchars((string) ($incoming['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Supplier<br><small>Submitted supplier</small></span><span><?= htmlspecialchars((string) ($incoming['supplier_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Origin<br><small>Declared source</small></span><span><?= htmlspecialchars((string) ($incoming['origin'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Expected Arrival<br><small>Client estimate</small></span><span><?= htmlspecialchars((string) ($incoming['expected_arrival'] ?? 'Not provided'), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Assigned To<br><small>Current owner</small></span><span><?= htmlspecialchars((string) ($incoming['assigned_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Created<br><small>Submission time</small></span><span><?= htmlspecialchars(bani_format_datetime($incoming['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></li>
          </ul>
        </article>

        <article class="dashboard-card detail-card">
          <h2>Submitted Package Details</h2>
          <ul class="dashboard-list">
            <li><span>Supplier Tracking<br><small>External reference</small></span><span><?= htmlspecialchars((string) ($incoming['supplier_tracking_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Item Description<br><small>What the client expects</small></span><span><?= htmlspecialchars((string) ($incoming['item_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <?php if (!empty($incoming['notes'])): ?>
              <li><span>Client Notes<br><small>Extra instructions</small></span><span><?= htmlspecialchars((string) $incoming['notes'], ENT_QUOTES, 'UTF-8') ?></span></li>
            <?php endif; ?>
            <?php if (!empty($incoming['admin_notes'])): ?>
              <li><span>Admin Notes<br><small>Current internal update</small></span><span><?= htmlspecialchars((string) $incoming['admin_notes'], ENT_QUOTES, 'UTF-8') ?></span></li>
            <?php endif; ?>
            <?php if ($linkedShipmentId > 0 && $linkedShipmentReference !== ''): ?>
              <li><span>Linked Shipment<br><small>Converted active shipment</small></span><span><a href="shipment-detail.php?id=<?= $linkedShipmentId ?>"><?= htmlspecialchars($linkedShipmentReference, ENT_QUOTES, 'UTF-8') ?></a></span></li>
            <?php endif; ?>
          </ul>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card detail-card">
          <h2>Update Incoming Request</h2>
          <form method="post">
            <input type="hidden" name="request_id" value="<?= (int) $requestId ?>">
            <input type="hidden" name="action" value="update">
            <div class="form-grid">
              <label>
                Status
                <select name="incoming_status">
                  <?php foreach (['Submitted', 'Reviewing', 'Shipment Opened', 'Closed'] as $statusOption): ?>
                    <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?>"<?= (($incoming['status'] ?? '') === $statusOption) ? ' selected' : '' ?>><?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                Assign To
                <select name="assigned_to">
                  <option value="">Unassigned</option>
                  <?php foreach ($staffUsers as $staffUser): ?>
                    <option value="<?= htmlspecialchars((string) ($staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"<?= (($incoming['assigned_to'] ?? '') === ($staffUser['email'] ?? '')) ? ' selected' : '' ?>>
                      <?= htmlspecialchars((string) ($staffUser['name'] ?? $staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
            </div>
            <label>
              Internal Notes
              <textarea name="admin_notes" placeholder="What happens next for this incoming package"><?= htmlspecialchars((string) ($incoming['admin_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
            <button type="submit">Save Request Update</button>
          </form>
        </article>

        <?php if ($linkedShipmentId <= 0): ?>
          <article class="dashboard-card detail-card">
            <h2>Convert To Shipment</h2>
            <form method="post">
              <input type="hidden" name="request_id" value="<?= (int) $requestId ?>">
              <input type="hidden" name="action" value="convert">
              <div class="form-grid">
                <label>
                  Destination
                  <input type="text" name="destination" placeholder="Final destination" required>
                </label>
                <label>
                  Mode
                  <select name="mode">
                    <option value="Air Freight">Air Freight</option>
                    <option value="Sea Freight">Sea Freight</option>
                    <option value="Road Delivery">Road Delivery</option>
                  </select>
                </label>
              </div>
              <div class="form-grid">
                <label>
                  Assign To
                  <select name="assigned_to">
                    <option value="">Assign later</option>
                    <?php foreach ($staffUsers as $staffUser): ?>
                      <option value="<?= htmlspecialchars((string) ($staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) ($staffUser['name'] ?? $staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label>
                  Next Step
                  <input type="text" name="next_step" value="Awaiting supplier release and transit booking" required>
                </label>
              </div>
              <button type="submit">Open Shipment</button>
            </form>
          </article>
        <?php endif; ?>
      </section>
    </main>
    <script src="/js/script.js"></script>
  </body>
</html>
