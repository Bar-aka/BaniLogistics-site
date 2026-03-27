<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';

$user = bani_current_user();

if (!$user) {
    header('Location: login.php?role=client');
    exit;
}

$invoiceId = (int) ($_GET['id'] ?? 0);
$invoice = bani_fetch_invoice_by_id($invoiceId);

if (!is_array($invoice)) {
    http_response_code(404);
    exit('Invoice record was not found.');
}

$role = (string) ($user['role'] ?? '');
if ($role === 'client' && strtolower((string) ($invoice['client_email'] ?? '')) !== strtolower((string) ($user['email'] ?? ''))) {
    header('Location: client-dashboard.php');
    exit;
}

$recordError = '';
$recordMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($role, ['admin', 'staff'], true)) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update-invoice-status') {
        $result = bani_update_invoice_status($invoiceId, (string) ($_POST['invoice_status'] ?? ''));
        if (($result['success'] ?? false) === true) {
            $recordMessage = (string) ($result['message'] ?? 'Invoice updated successfully.');
            $invoice = bani_fetch_invoice_by_id($invoiceId) ?? $invoice;
        } else {
            $recordError = (string) ($result['message'] ?? 'Unable to update invoice.');
        }
    }
}

$deliveryTargets = bani_invoice_delivery_targets($invoice);
$dashboardUrl = match ($role) {
    'admin' => 'admin-dashboard.php',
    'staff' => 'staff-dashboard.php',
    default => 'client-dashboard.php',
};
$created = isset($_GET['created']) && $_GET['created'] === '1';
$emailSubject = 'Invoice ' . (string) ($invoice['invoice_number'] ?? '') . ' from Bani Global Logistics Limited';
$emailBody = rawurlencode(
    "Hello " . (string) ($invoice['client_name'] ?? 'Client') . ",\r\n\r\n"
    . "Please find your invoice details below:\r\n"
    . "Invoice Number: " . (string) ($invoice['invoice_number'] ?? '') . "\r\n"
    . "Tracking Number: " . (string) (($invoice['tracking_reference'] ?? '') !== '' ? $invoice['tracking_reference'] : 'Not linked') . "\r\n"
    . "Description: " . (string) ($invoice['description'] ?? '') . "\r\n"
    . "Amount: " . (string) (($invoice['currency'] ?? 'KES') . ' ' . number_format((float) ($invoice['amount'] ?? 0), 2)) . "\r\n"
    . "Due Date: " . (string) ($invoice['due_date'] ?? '') . "\r\n\r\n"
    . "Regards,\r\nBani Global Logistics Limited"
);
$mailtoLink = 'mailto:' . rawurlencode((string) ($deliveryTargets['client_email'] ?? ''))
    . '?cc=' . rawurlencode((string) ($deliveryTargets['accounts_email'] ?? ''))
    . '&subject=' . rawurlencode($emailSubject)
    . '&body=' . $emailBody;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($invoice['invoice_number'] ?? 'Invoice'), ENT_QUOTES, 'UTF-8') ?> | Bani Global Logistics Limited</title>
    <meta name="description" content="Review a logistics invoice, recipient routing, and status from the secure Bani portal.">
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
          <?php if (in_array($role, ['admin', 'staff'], true)): ?>
            <a href="create-invoice.php">Create Invoice</a>
            <a href="create-shipment.php">Create Shipment</a>
          <?php endif; ?>
          <a class="active" href="invoice-view.php?id=<?= (int) ($invoice['id'] ?? 0) ?>">Invoice View</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Invoice detail</div>
          <h1><?= htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
          <p>
            Review the final invoice layout, the delivery route to the client, and the internal accounts copy destination from one page.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button secondary" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Back to Dashboard</a>
          <?php if (in_array($role, ['admin', 'staff'], true)): ?>
            <a class="button primary" href="create-invoice.php">New Invoice</a>
          <?php endif; ?>
        </div>
      </section>

      <?php if ($created): ?>
        <div class="result-box show"><strong>Invoice created.</strong><p>This is the exact invoice record now visible to the client in their portal.</p></div>
      <?php endif; ?>
      <?php if ($recordError !== ''): ?>
        <div class="result-box show"><strong>Action failed.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>
      <?php if ($recordMessage !== ''): ?>
        <div class="result-box show"><strong>Action completed.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>

      <section class="detail-layout">
        <article class="dashboard-card invoice-document invoice-watermark">
          <div class="invoice-topline">
            <div>
              <div class="eyebrow">Bani Global Logistics Limited</div>
              <h2>Logistics Invoice</h2>
              <p class="muted">Customs brokerage, freight coordination, and cargo handling support.</p>
            </div>
            <div class="invoice-meta">
              <strong><?= htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
              <span>Issued <?= htmlspecialchars(bani_format_datetime((string) ($invoice['created_at'] ?? null)), ENT_QUOTES, 'UTF-8') ?></span>
              <span>Due <?= htmlspecialchars((string) ($invoice['due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
              <span class="badge <?= strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? 'badge-green' : 'badge-red' ?>"><?= htmlspecialchars((string) ($invoice['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          </div>

          <div class="summary-grid">
            <div class="detail-panel">
              <h3>Bill To</h3>
              <p><strong><?= htmlspecialchars((string) ($invoice['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
              <p><?= htmlspecialchars((string) ($invoice['client_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="detail-panel">
              <h3>Issued By</h3>
              <p><strong>Bani Global Logistics Limited</strong></p>
              <p>support@banilogistics.co.ke</p>
              <p>+254 782 013 236</p>
            </div>
            <div class="detail-panel">
              <h3>Tracking Reference</h3>
              <p><strong><?= htmlspecialchars((string) (($invoice['tracking_reference'] ?? '') !== '' ? $invoice['tracking_reference'] : 'Not linked'), ENT_QUOTES, 'UTF-8') ?></strong></p>
              <p><?= (($invoice['tracking_reference'] ?? '') !== '') ? 'Linked shipment tracking number shown to client and accounts team.' : 'No shipment tracking number was linked to this invoice.' ?></p>
            </div>
          </div>

          <div class="invoice-line">
            <div>
              <strong>Service Description</strong>
              <p><?= htmlspecialchars((string) ($invoice['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="invoice-amount">
              <?= htmlspecialchars((string) (($invoice['currency'] ?? 'KES') . ' ' . number_format((float) ($invoice['amount'] ?? 0), 2)), ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>

          <div class="invoice-total">
            <span>Total Due</span>
            <strong><?= htmlspecialchars((string) (($invoice['currency'] ?? 'KES') . ' ' . number_format((float) ($invoice['amount'] ?? 0), 2)), ENT_QUOTES, 'UTF-8') ?></strong>
          </div>
        </article>

        <aside class="dashboard-card detail-card">
          <h3>Delivery Route</h3>
          <ul class="dashboard-list">
            <li><span>Client Recipient<br><small>Primary invoice destination</small></span><span><?= htmlspecialchars((string) ($deliveryTargets['client_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Accounts Copy<br><small>Internal follow-up address</small></span><span><?= htmlspecialchars((string) ($deliveryTargets['accounts_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Portal Visibility<br><small>Client dashboard record</small></span><span>Live</span></li>
          </ul>

          <h3>Email Format Preview</h3>
          <div class="timeline">
            <div class="timeline-item">
              <strong>Subject</strong>
              <p>Invoice <?= htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?> from Bani Global Logistics Limited</p>
            </div>
            <div class="timeline-item">
              <strong>Tracking Number</strong>
              <p><?= htmlspecialchars((string) (($invoice['tracking_reference'] ?? '') !== '' ? $invoice['tracking_reference'] : 'Not linked'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="timeline-item">
              <strong>To</strong>
              <p><?= htmlspecialchars((string) ($deliveryTargets['client_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="timeline-item">
              <strong>CC / BCC</strong>
              <p><?= htmlspecialchars((string) ($deliveryTargets['accounts_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </div>

          <?php if (in_array($role, ['admin', 'staff'], true)): ?>
            <h3>Quick Actions</h3>
            <div class="workflow-card">
              <a class="button secondary" href="<?= htmlspecialchars($mailtoLink, ENT_QUOTES, 'UTF-8') ?>">Open Email Draft</a>
              <form method="post" action="invoice-view.php?id=<?= (int) ($invoice['id'] ?? 0) ?>">
                <input type="hidden" name="action" value="update-invoice-status">
                <input type="hidden" name="invoice_status" value="<?= strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? 'Due' : 'Paid' ?>">
                <button type="submit"><?= strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? 'Mark Due' : 'Mark Paid' ?></button>
              </form>
            </div>
          <?php endif; ?>
        </aside>
      </section>
    </main>
  </body>
</html>
