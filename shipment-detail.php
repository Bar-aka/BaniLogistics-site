<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';

$user = bani_require_roles(['client', 'staff', 'admin']);
$shipmentId = (int) ($_GET['id'] ?? $_POST['shipment_id'] ?? 0);
$shipment = bani_fetch_shipment_by_id($shipmentId);

if (!is_array($shipment)) {
    http_response_code(404);
    exit('Shipment not found.');
}

if (($user['role'] ?? '') === 'client' && strtolower((string) ($shipment['client_email'] ?? '')) !== strtolower((string) ($user['email'] ?? ''))) {
    http_response_code(403);
    exit('You do not have permission to view this shipment.');
}

$recordMessage = '';
$recordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array((string) ($user['role'] ?? ''), ['staff', 'admin'], true)) {
    $result = bani_update_shipment($shipmentId, $_POST);
    if (($result['success'] ?? false) === true) {
        $recordMessage = (string) $result['message'];
        $shipment = bani_fetch_shipment_by_id($shipmentId);
    } else {
        $recordError = (string) ($result['message'] ?? 'Unable to update shipment.');
    }
}

$updates = bani_fetch_shipment_updates($shipmentId);
$incomingRequest = !empty($shipment['incoming_request_id']) ? bani_fetch_incoming_request_by_id((int) $shipment['incoming_request_id']) : null;
$staffUsers = bani_staff_users();
$dashboardUrl = ($user['role'] ?? '') === 'admin'
    ? 'admin-dashboard.php'
    : (($user['role'] ?? '') === 'staff' ? 'staff-dashboard.php' : 'client-dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Detail | Bani Global Logistics Limited</title>
    <meta name="description" content="Shipment detail view with milestone history, ownership, and next operational steps.">
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
          <a class="active" href="shipment-detail.php?id=<?= (int) $shipmentId ?>">Shipment Detail</a>
          <a href="track.html">Track Shipment</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Shipment detail</div>
          <h1><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
          <p>
            <?= htmlspecialchars((string) ($shipment['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            | <?= htmlspecialchars((string) (($shipment['origin'] ?? '') . ' to ' . ($shipment['destination'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
            | Owner: <?= htmlspecialchars((string) ($shipment['assigned_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?>
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button secondary" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Back to Dashboard</a>
          <a class="button primary" href="track.html">Open Tracking Page</a>
        </div>
      </section>

      <?php if ($recordError !== ''): ?>
        <div class="result-box show result-error"><strong>Action failed.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>
      <?php if ($recordMessage !== ''): ?>
        <div class="result-box show result-success"><strong>Action completed.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>

      <section class="summary-grid">
        <article class="dashboard-stat"><strong><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><span>Current status</span></article>
        <article class="dashboard-stat"><strong><?= htmlspecialchars((string) ($shipment['next_step'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><span>Current next step</span></article>
      </section>

      <section class="workspace-grid">
        <article class="dashboard-card detail-card">
          <h2>Shipment Milestone History</h2>
          <p class="dashboard-subtitle">Every shipment update is logged here so the team and the client have one shared operational story.</p>
          <div class="timeline">
            <?php foreach ($updates as $update): ?>
              <div class="timeline-item">
                <strong><?= htmlspecialchars((string) ($update['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                <span><?= htmlspecialchars((string) ($update['next_step'] ?? ''), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars(bani_format_datetime($update['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($update['notes'])): ?>
                  <p><?= htmlspecialchars((string) $update['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <?php if ($updates === []): ?>
              <div class="timeline-item"><strong>No milestone history yet</strong><span>The first update will appear here once the shipment is progressed.</span></div>
            <?php endif; ?>
          </div>
        </article>

        <article class="dashboard-card detail-card">
          <div class="detail-panel">
            <h3>Shipment Summary</h3>
            <ul class="dashboard-list">
              <li><span>Client<br><small>Portal-linked account</small></span><span><?= htmlspecialchars((string) ($shipment['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Route<br><small>Operational movement</small></span><span><?= htmlspecialchars((string) (($shipment['origin'] ?? '') . ' to ' . ($shipment['destination'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Mode<br><small>Freight movement type</small></span><span><?= htmlspecialchars((string) ($shipment['mode'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Owner<br><small>Assigned staff</small></span><span><?= htmlspecialchars((string) ($shipment['assigned_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Created<br><small>Shipment opened in portal</small></span><span><?= htmlspecialchars(bani_format_datetime($shipment['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Last Update<br><small>Most recent operational change</small></span><span><?= htmlspecialchars(bani_format_datetime($shipment['updated_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></li>
            </ul>
          </div>

          <?php if (is_array($incomingRequest)): ?>
            <div class="detail-panel">
              <h3>Linked Incoming Request</h3>
              <ul class="dashboard-list">
                <li><span>Supplier<br><small>Origin supplier details</small></span><span><?= htmlspecialchars((string) ($incomingRequest['supplier_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
                <li><span>Supplier Tracking<br><small>External supplier reference</small></span><span><?= htmlspecialchars((string) ($incomingRequest['supplier_tracking_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
                <li><span>Item Details<br><small>Client-submitted intake notes</small></span><span><?= htmlspecialchars((string) ($incomingRequest['item_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              </ul>
            </div>
          <?php endif; ?>

          <?php if (in_array((string) ($user['role'] ?? ''), ['staff', 'admin'], true)): ?>
            <div class="detail-panel">
              <h3>Update Shipment</h3>
              <form method="post">
                <input type="hidden" name="shipment_id" value="<?= (int) $shipmentId ?>">
                <div class="form-grid">
                  <label>
                    Status
                    <select name="status">
                      <?php foreach (['Submitted', 'Order Confirmed', 'In Transit', 'Customs Clearance', 'Released from Customs', 'Out for Delivery', 'Delivered'] as $statusOption): ?>
                        <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?>"<?= (($shipment['status'] ?? '') === $statusOption) ? ' selected' : '' ?>><?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label>
                    Assigned To
                    <select name="assigned_to">
                      <option value="">Unassigned</option>
                      <?php foreach ($staffUsers as $staffUser): ?>
                        <option value="<?= htmlspecialchars((string) ($staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"<?= (($shipment['assigned_to'] ?? '') === ($staffUser['email'] ?? '')) ? ' selected' : '' ?>>
                          <?= htmlspecialchars((string) ($staffUser['name'] ?? $staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                </div>
                <label>
                  Next Step
                  <input type="text" name="next_step" value="<?= htmlspecialchars((string) ($shipment['next_step'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>
                  Internal Notes
                  <textarea name="internal_notes" placeholder="Operational note"><?= htmlspecialchars((string) ($shipment['internal_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
                <button type="submit">Save Shipment Update</button>
              </form>
            </div>
          <?php endif; ?>
        </article>
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
  </body>
</html>
