<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';

$user = bani_require_roles(['admin', 'staff'], 'staff');
$clientAccounts = bani_list_users('client');
$staffUsers = bani_staff_users();
$recordError = '';
$recordMessage = '';
$createdShipment = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = bani_create_shipment($_POST);

    if (($result['success'] ?? false) === true) {
        $recordMessage = (string) ($result['message'] ?? 'Shipment created successfully.');
        $createdShipment = bani_fetch_shipment_by_id((int) ($result['id'] ?? 0));
        $_POST = [];
    } else {
        $recordError = (string) ($result['message'] ?? 'Unable to create shipment.');
    }
}

$dashboardUrl = ($user['role'] ?? '') === 'admin' ? 'admin-dashboard.php' : 'staff-dashboard.php';
$defaultAssigned = ($user['role'] ?? '') === 'staff' ? (string) ($user['email'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Shipment | Bani Global Logistics Limited</title>
    <meta name="description" content="Create and assign a shipment record from the secure operations portal.">
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
          <a class="active" href="create-shipment.php">Create Shipment</a>
          <a href="create-quote.php">Create Quote</a>
          <a href="create-invoice.php">Create Invoice</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Shipment workflow</div>
          <h1>Create a shipment and assign it to the right operations owner.</h1>
          <p>
            Once saved, the shipment appears in the selected client's portal and in staff processing queues using the live status and next-step details entered here.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button secondary" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Back to Dashboard</a>
          <a class="button primary" href="create-invoice.php">Create Invoice</a>
        </div>
      </section>

      <section class="workspace-grid">
        <article class="dashboard-card form-shell">
          <h2>Create Shipment</h2>
          <p class="dashboard-subtitle">This record drives client tracking visibility, staff assignments, and delivery milestones.</p>
          <?php if ($recordError !== ''): ?>
            <div class="result-box show"><strong>Action failed.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <?php if ($recordMessage !== ''): ?>
            <div class="result-box show"><strong>Action completed.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <form method="post" action="create-shipment.php">
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
                Mode
                <input type="text" name="mode" placeholder="Air Freight" value="<?= htmlspecialchars((string) ($_POST['mode'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
              <label>
                Status
                <input type="text" name="status" placeholder="In Transit" value="<?= htmlspecialchars((string) ($_POST['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
              </label>
            </div>
            <div class="form-grid">
              <label>
                Cargo Description
                <input type="text" name="cargo" placeholder="General cargo" value="<?= htmlspecialchars((string) ($_POST['cargo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </label>
              <label>
                Weight
                <input type="text" name="weight" placeholder="120 kg" value="<?= htmlspecialchars((string) ($_POST['weight'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </label>
            </div>
            <label>
              Assign To
              <select name="assigned_to">
                <option value="">Unassigned</option>
                <?php foreach ($staffUsers as $staffUser): ?>
                  <?php $selected = ((string) ($_POST['assigned_to'] ?? $defaultAssigned)) === (string) ($staffUser['email'] ?? ''); ?>
                  <option value="<?= htmlspecialchars((string) ($staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $selected ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) ($staffUser['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($staffUser['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>
              Next Step
              <input type="text" name="next_step" placeholder="Arrival scan at destination hub" value="<?= htmlspecialchars((string) ($_POST['next_step'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </label>
            <label>
              Internal Notes
              <textarea name="internal_notes" placeholder="Operational handoff notes, customs remarks, or internal instructions"><?= htmlspecialchars((string) ($_POST['internal_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
            <button type="submit">Create Shipment Record</button>
          </form>
        </article>

        <aside class="dashboard-card detail-card">
          <h3>Shipment Visibility</h3>
          <?php if (is_array($createdShipment)): ?>
            <ul class="dashboard-list">
              <li><span>Reference<br><small>Generated shipment code</small></span><span><?= htmlspecialchars((string) ($createdShipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Client<br><small>Portal account owner</small></span><span><?= htmlspecialchars((string) ($createdShipment['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Assigned To<br><small>Operations owner</small></span><span><?= htmlspecialchars((string) ($createdShipment['assigned_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Status<br><small>Current tracking stage</small></span><span><?= htmlspecialchars((string) ($createdShipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            </ul>
            <?php if (isset($result) && is_array($result) && array_key_exists('api_sync', $result)): ?>
              <div class="result-box show">
                <strong><?= ($result['api_sync'] ?? false) ? 'Live API sync completed.' : 'Live API sync pending.' ?></strong>
                <p><?= htmlspecialchars((string) ($result['api_message'] ?? 'No API sync status returned.'), ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="timeline">
              <div class="timeline-item">
                <strong>Client tracking goes live on creation</strong>
                <p>The shipment appears in the client portal once this record is saved.</p>
              </div>
              <div class="timeline-item">
                <strong>Optional live API sync</strong>
                <p>When the Node backend is enabled in config, this same shipment is also mirrored into the production tracking API.</p>
              </div>
              <div class="timeline-item">
                <strong>Staff workload becomes clearer</strong>
                <p>Assignments and next steps are visible in the staff dashboard immediately.</p>
              </div>
              <div class="timeline-item">
                <strong>Status updates continue the same record</strong>
                <p>Admin and staff update this shipment over time rather than creating new entries.</p>
              </div>
            </div>
          <?php endif; ?>
        </aside>
      </section>
    </main>
  </body>
</html>
