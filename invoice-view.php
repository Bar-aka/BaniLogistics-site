<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';

$user = bani_require_roles(['client', 'staff', 'admin']);
$invoiceId = (int) ($_GET['id'] ?? $_POST['invoice_id'] ?? 0);
$invoice = bani_fetch_invoice_by_id($invoiceId);

if (!is_array($invoice)) {
    http_response_code(404);
    exit('Invoice not found.');
}

if (($user['role'] ?? '') === 'client' && strtolower((string) ($invoice['client_email'] ?? '')) !== strtolower((string) ($user['email'] ?? ''))) {
    http_response_code(403);
    exit('You do not have permission to view this invoice.');
}

$recordMessage = '';
$recordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update-invoice-status' && in_array((string) ($user['role'] ?? ''), ['staff', 'admin'], true)) {
        $result = bani_update_invoice_status($invoiceId, (string) ($_POST['invoice_status'] ?? ''));
        if (($result['success'] ?? false) === true) {
            $recordMessage = (string) $result['message'];
            $invoice = bani_fetch_invoice_by_id($invoiceId);
        } else {
            $recordError = (string) ($result['message'] ?? 'Unable to update invoice.');
        }
    } elseif ($action === 'submit-payment-reference' && ($user['role'] ?? '') === 'client') {
        $result = bani_submit_invoice_payment_reference(
            $invoiceId,
            (string) ($_POST['payment_reference'] ?? ''),
            (string) ($_POST['payment_notes'] ?? '')
        );
        if (($result['success'] ?? false) === true) {
            $recordMessage = (string) $result['message'];
            $invoice = bani_fetch_invoice_by_id($invoiceId);
        } else {
            $recordError = (string) ($result['message'] ?? 'Unable to submit payment reference.');
        }
    }
}

$dashboardUrl = ($user['role'] ?? '') === 'admin'
    ? 'admin-dashboard.php'
    : (($user['role'] ?? '') === 'staff' ? 'staff-dashboard.php' : 'client-dashboard.php');
$delivery = bani_invoice_delivery_targets($invoice);
$trackingReference = (string) ($invoice['tracking_reference'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice View | Bani Global Logistics Limited</title>
    <meta name="description" content="Invoice view with payment instructions, tracking references, and payment-reference submission.">
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
          <a class="active" href="invoice-view.php?id=<?= (int) $invoiceId ?>">Invoice View</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Invoice document</div>
          <h1><?= htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
          <p>
            Client: <strong><?= htmlspecialchars((string) ($invoice['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
            <?php if ($trackingReference !== ''): ?>
              | Tracking: <strong><?= htmlspecialchars($trackingReference, ENT_QUOTES, 'UTF-8') ?></strong>
            <?php endif; ?>
            | Status: <strong><?= htmlspecialchars((string) ($invoice['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button secondary" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Back to Dashboard</a>
          <?php if ($trackingReference !== ''): ?>
            <a class="button primary" href="track.html">Track Shipment</a>
          <?php endif; ?>
        </div>
      </section>

      <?php if ($recordError !== ''): ?>
        <div class="result-box show result-error"><strong>Action failed.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>
      <?php if ($recordMessage !== ''): ?>
        <div class="result-box show result-success"><strong>Action completed.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
      <?php endif; ?>

      <section class="workspace-grid">
        <article class="dashboard-card invoice-document invoice-watermark">
          <div class="invoice-topline">
            <div>
              <h2>Bani Global Logistics Limited</h2>
              <p class="muted">Operations, customs brokerage, sourcing, and global logistics support.</p>
            </div>
            <div class="invoice-meta">
              <strong><?= htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
              <span>Issued <?= htmlspecialchars(bani_format_datetime($invoice['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
              <span>Due <?= htmlspecialchars((string) ($invoice['due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          </div>

          <div class="summary-grid">
            <div class="detail-panel">
              <h3>Bill To</h3>
              <p><strong><?= htmlspecialchars((string) ($invoice['client_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
              <p><?= htmlspecialchars((string) ($invoice['client_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="detail-panel">
              <h3>Invoice Summary</h3>
              <p>Status: <strong><?= htmlspecialchars((string) ($invoice['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
              <?php if ($trackingReference !== ''): ?>
                <p>Tracking Number: <strong><?= htmlspecialchars($trackingReference, ENT_QUOTES, 'UTF-8') ?></strong></p>
              <?php endif; ?>
            </div>
          </div>

          <div class="detail-panel">
            <h3>Service Description</h3>
            <p><?= htmlspecialchars((string) ($invoice['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="invoice-line">
              <span>Total Due</span>
              <strong class="invoice-amount"><?= htmlspecialchars((string) ($invoice['currency'] ?? 'KES'), ENT_QUOTES, 'UTF-8') ?> <?= number_format((float) ($invoice['amount'] ?? 0), 2) ?></strong>
            </div>
          </div>

          <div class="detail-panel">
            <h3>Delivery Route</h3>
            <ul class="dashboard-list">
              <li><span>Client delivery<br><small>Primary recipient</small></span><span><?= htmlspecialchars((string) ($delivery['client_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <li><span>Accounts copy<br><small>Internal follow-up</small></span><span><?= htmlspecialchars((string) ($delivery['accounts_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            </ul>
          </div>
        </article>

        <article class="dashboard-card detail-card">
          <div class="detail-panel">
            <h3>How To Pay</h3>
            <ul class="dashboard-list">
              <?php if (!empty($invoice['bank_name']) || !empty($invoice['account_name']) || !empty($invoice['account_number'])): ?>
                <li><span>Bank Transfer<br><small><?= htmlspecialchars((string) ($invoice['bank_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></span><span><?= htmlspecialchars((string) ($invoice['account_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($invoice['account_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <?php endif; ?>
              <?php if (!empty($invoice['bank_branch']) || !empty($invoice['swift_code'])): ?>
                <li><span>Branch / SWIFT<br><small>For bank settlement</small></span><span><?= htmlspecialchars((string) ($invoice['bank_branch'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($invoice['swift_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <?php endif; ?>
              <?php if (!empty($invoice['mpesa_details'])): ?>
                <li><span>M-Pesa<br><small>Mobile money settlement</small></span><span><?= nl2br(htmlspecialchars((string) $invoice['mpesa_details'], ENT_QUOTES, 'UTF-8')) ?></span></li>
              <?php endif; ?>
              <?php if (!empty($invoice['mpesa_pay_link'])): ?>
                <li><span>M-Pesa Checkout<br><small>Direct payment link</small></span><span><a href="<?= htmlspecialchars((string) $invoice['mpesa_pay_link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Open M-Pesa Payment</a></span></li>
              <?php endif; ?>
              <?php if (!empty($invoice['paypal_details'])): ?>
                <li><span>PayPal<br><small>Online payment route</small></span><span><?= nl2br(htmlspecialchars((string) $invoice['paypal_details'], ENT_QUOTES, 'UTF-8')) ?></span></li>
              <?php endif; ?>
              <?php if (!empty($invoice['paypal_pay_link'])): ?>
                <li><span>PayPal Checkout<br><small>Direct payment link</small></span><span><a href="<?= htmlspecialchars((string) $invoice['paypal_pay_link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Pay With PayPal</a></span></li>
              <?php endif; ?>
              <?php if (!empty($invoice['payment_instructions'])): ?>
                <li><span>Instructions<br><small>Reference and proof guidance</small></span><span><?= nl2br(htmlspecialchars((string) $invoice['payment_instructions'], ENT_QUOTES, 'UTF-8')) ?></span></li>
              <?php endif; ?>
              <?php if (empty($invoice['bank_name']) && empty($invoice['mpesa_details']) && empty($invoice['paypal_details']) && empty($invoice['payment_instructions'])): ?>
                <li><span>Payment details pending<br><small>Accounts team will share them if needed</small></span><span><?= htmlspecialchars((string) ($delivery['accounts_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
              <?php endif; ?>
            </ul>
          </div>

          <?php if (($user['role'] ?? '') === 'client' && strtolower((string) ($invoice['status'] ?? '')) !== 'paid'): ?>
            <div class="detail-panel">
              <h3>Submit Payment Reference</h3>
              <p class="muted">If you settle this invoice outside the platform, paste the payment reference here so our accounts team can verify it.</p>
              <?php if (!empty($invoice['mpesa_pay_link']) || !empty($invoice['paypal_pay_link'])): ?>
                <div class="dashboard-actions" style="margin-bottom: 14px;">
                  <?php if (!empty($invoice['mpesa_pay_link'])): ?>
                    <a class="button primary" href="<?= htmlspecialchars((string) $invoice['mpesa_pay_link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Pay With M-Pesa</a>
                  <?php endif; ?>
                  <?php if (!empty($invoice['paypal_pay_link'])): ?>
                    <a class="button secondary" href="<?= htmlspecialchars((string) $invoice['paypal_pay_link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Pay With PayPal</a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <form method="post">
                <input type="hidden" name="action" value="submit-payment-reference">
                <input type="hidden" name="invoice_id" value="<?= (int) $invoiceId ?>">
                <label>
                  Payment Reference
                  <input type="text" name="payment_reference" value="<?= htmlspecialchars((string) ($invoice['payment_reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Bank ref, M-Pesa code, or PayPal transaction ID" required>
                </label>
                <label>
                  Payment Notes
                  <textarea name="payment_notes" placeholder="Optional note for accounts team"><?= htmlspecialchars((string) ($invoice['payment_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
                <button type="submit">Submit Payment Reference</button>
              </form>
            </div>
          <?php endif; ?>

          <?php if (!empty($invoice['payment_reference'])): ?>
            <div class="detail-panel">
              <h3>Submitted Payment Reference</h3>
              <ul class="dashboard-list">
                <li><span>Reference<br><small>Submitted by client</small></span><span><?= htmlspecialchars((string) ($invoice['payment_reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
                <li><span>Submitted On<br><small>Awaiting confirmation</small></span><span><?= htmlspecialchars(bani_format_datetime($invoice['payment_submitted_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></li>
                <?php if (!empty($invoice['payment_notes'])): ?>
                  <li><span>Notes<br><small>Client note</small></span><span><?= htmlspecialchars((string) ($invoice['payment_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
                <?php endif; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if (in_array((string) ($user['role'] ?? ''), ['staff', 'admin'], true)): ?>
            <div class="detail-panel">
              <h3>Invoice Admin Actions</h3>
              <form method="post" class="inline-actions">
                <input type="hidden" name="action" value="update-invoice-status">
                <input type="hidden" name="invoice_id" value="<?= (int) $invoiceId ?>">
                <input type="hidden" name="invoice_status" value="<?= strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? 'Due' : 'Paid' ?>">
                <button type="submit"><?= strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? 'Mark Due' : 'Mark Paid' ?></button>
              </form>
            </div>
          <?php endif; ?>

          <div class="detail-panel">
            <h3>Email Draft</h3>
            <p class="muted">Client delivery stays aligned with accounts follow-up.</p>
            <a
              class="button secondary"
              href="mailto:<?= rawurlencode((string) ($delivery['client_email'] ?? '')) ?>?cc=<?= rawurlencode((string) ($delivery['accounts_email'] ?? '')) ?>&subject=<?= rawurlencode('Invoice ' . (string) ($invoice['invoice_number'] ?? '')) ?>&body=<?= rawurlencode('Please find invoice ' . (string) ($invoice['invoice_number'] ?? '') . ($trackingReference !== '' ? ' for shipment ' . $trackingReference : '') . '. Amount due: ' . (string) ($invoice['currency'] ?? 'KES') . ' ' . number_format((float) ($invoice['amount'] ?? 0), 2) . '.') ?>"
            >Open Email Draft</a>
          </div>
        </article>
      </section>
    </main>

    <script src="/js/script.js"></script>
  </body>
</html>
