<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';

$user = bani_require_roles(['admin', 'staff'], 'admin');
$requestId = (int) ($_GET['id'] ?? $_POST['request_id'] ?? 0);
$request = bani_fetch_quote_request_by_id($requestId);

if (!is_array($request)) {
    http_response_code(404);
    exit('Quote request not found.');
}

$recordMessage = '';
$recordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = bani_update_quote_request($requestId, $_POST);
    if (($result['success'] ?? false) === true) {
        $recordMessage = (string) ($result['message'] ?? 'Quote request updated.');
        $request = bani_fetch_quote_request_by_id($requestId);
    } else {
        $recordError = (string) ($result['message'] ?? 'Unable to update quote request.');
    }
}

$staffUsers = bani_staff_users();
$dashboardUrl = ($user['role'] ?? '') === 'admin' ? 'admin-dashboard.php' : 'staff-dashboard.php';
$clientChannel = (string) (($request['client_email'] ?? '') !== '' ? $request['client_email'] : ($request['phone'] ?? ''));
$whatsAppMessage = 'Hello ' . (string) ($request['client_name'] ?? 'Client') . ', this is Bani Global Logistics following up on your ' . (string) ($request['request_type'] ?? 'quote') . ' request.';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Request Detail | Bani Global Logistics Limited</title>
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
          <a class="active" href="quote-request-detail.php?id=<?= (int) $requestId ?>">Quote Request</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Quote request detail</div>
          <h1><?= htmlspecialchars(ucfirst((string) ($request['request_type'] ?? 'Request')) . ' request', ENT_QUOTES, 'UTF-8') ?></h1>
          <p>
            <?= htmlspecialchars((string) ($request['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            | <?= htmlspecialchars($clientChannel, ENT_QUOTES, 'UTF-8') ?>
            | Status: <strong><?= htmlspecialchars((string) ($request['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button secondary" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Back to Dashboard</a>
          <a class="button secondary" href="<?= htmlspecialchars(bani_whatsapp_link($whatsAppMessage), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">WhatsApp Client</a>
          <?php if (!empty($request['client_email'])): ?>
            <a class="button primary" href="mailto:<?= rawurlencode((string) $request['client_email']) ?>">Email Client</a>
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
            <li><span>Client<br><small>Primary request owner</small></span><span><?= htmlspecialchars((string) ($request['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Contact<br><small>Email or WhatsApp</small></span><span><?= htmlspecialchars($clientChannel, ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Status<br><small>Current request stage</small></span><span><?= htmlspecialchars((string) ($request['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Assigned To<br><small>Current request owner</small></span><span><?= htmlspecialchars((string) ($request['assigned_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Created<br><small>Submission time</small></span><span><?= htmlspecialchars(bani_format_datetime($request['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Updated<br><small>Latest action</small></span><span><?= htmlspecialchars(bani_format_datetime($request['updated_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></li>
          </ul>
        </article>

        <article class="dashboard-card detail-card">
          <h2>Submitted Details</h2>
          <ul class="dashboard-list">
            <?php if (($request['request_type'] ?? '') === 'shipping'): ?>
              <li><span>Route<br><small>Origin to destination</small></span><span><?= htmlspecialchars((string) (($request['origin'] ?? '') . ' to ' . ($request['destination'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Mode<br><small>Freight mode</small></span><span><?= htmlspecialchars((string) ($request['mode'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Weight<br><small>Submitted estimate</small></span><span><?= htmlspecialchars((string) ($request['weight'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Shipment Type<br><small>Declared by client</small></span><span><?= htmlspecialchars((string) ($request['shipment_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <?php else: ?>
              <li><span>Product Category<br><small>Requested sourcing line</small></span><span><?= htmlspecialchars((string) ($request['product_category'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Quantity<br><small>Requested volume</small></span><span><?= htmlspecialchars((string) ($request['quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Market<br><small>Target market or origin</small></span><span><?= htmlspecialchars((string) ($request['market'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Budget<br><small>Client budget note</small></span><span><?= htmlspecialchars((string) ($request['budget'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Product Details<br><small>Full sourcing brief</small></span><span><?= htmlspecialchars((string) ($request['product_details'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <?php endif; ?>
            <?php if (!empty($request['notes'])): ?>
              <li><span>Client Notes<br><small>Additional detail</small></span><span><?= htmlspecialchars((string) $request['notes'], ENT_QUOTES, 'UTF-8') ?></span></li>
            <?php endif; ?>
          </ul>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card detail-card">
          <h2>Action Request</h2>
          <form method="post">
            <input type="hidden" name="request_id" value="<?= (int) $requestId ?>">
            <div class="form-grid">
              <label>
                Status
                <select name="quote_status">
                  <?php foreach (['Submitted', 'Reviewing', 'Quoted', 'Assigned', 'Closed'] as $statusOption): ?>
                    <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?>"<?= (($request['status'] ?? '') === $statusOption) ? ' selected' : '' ?>><?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                Assign To
                <select name="assigned_to">
                  <option value="">Unassigned</option>
                  <?php foreach ($staffUsers as $staffUser): ?>
                    <option value="<?= htmlspecialchars((string) ($staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"<?= (($request['assigned_to'] ?? '') === ($staffUser['email'] ?? '')) ? ' selected' : '' ?>>
                      <?= htmlspecialchars((string) ($staffUser['name'] ?? $staffUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
            </div>
            <label>
              Internal Notes
              <textarea name="admin_notes" placeholder="Add the next commercial action, promised feedback, or sourcing follow-up"><?= htmlspecialchars((string) ($request['admin_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
            <button type="submit">Save Request Update</button>
          </form>
        </article>
      </section>
    </main>
  </body>
</html>
