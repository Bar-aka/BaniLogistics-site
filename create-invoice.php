<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';

$user = bani_require_roles(['admin', 'staff'], 'staff');
$clientAccounts = bani_list_users('client');
$recordError = '';
$recordMessage = '';
$createdInvoice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = bani_create_invoice($_POST);

    if (($result['success'] ?? false) === true) {
        $recordMessage = (string) ($result['message'] ?? 'Invoice created successfully.');
        $createdInvoice = bani_fetch_invoice_by_id((int) ($result['id'] ?? 0));
        $_POST = [];
    } else {
        $recordError = (string) ($result['message'] ?? 'Unable to create invoice.');
    }
}

$dashboardUrl = ($user['role'] ?? '') === 'admin' ? 'admin-dashboard.php' : 'staff-dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice | Bani Global Logistics Limited</title>
    <meta name="description" content="Create an invoice with payment instructions for client settlement and accounts follow-up.">
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
          <a class="active" href="create-invoice.php">Create Invoice</a>
          <a href="create-shipment.php">Create Shipment</a>
          <a href="create-quote.php">Create Quote</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Invoice workflow</div>
          <h1>Create an invoice with full payment instructions.</h1>
          <p>
            Once saved, the client can open the invoice, see the exact amount due, review how to pay, and submit an external payment reference for confirmation.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button secondary" href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>">Back to Dashboard</a>
        </div>
      </section>

      <section class="workspace-grid">
        <article class="dashboard-card form-shell">
          <h2>Create Invoice</h2>
          <p class="dashboard-subtitle">Link it to a client and tracking number, then add bank, M-Pesa, or PayPal details as needed.</p>
          <?php if ($recordError !== ''): ?>
            <div class="result-box show result-error"><strong>Unable to save invoice.</strong><p><?= htmlspecialchars($recordError, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>
          <?php if ($recordMessage !== ''): ?>
            <div class="result-box show result-success"><strong>Invoice created.</strong><p><?= htmlspecialchars($recordMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
          <?php endif; ?>

          <form method="post">
            <div class="form-grid">
              <label>
                Client Account
                <select name="client_email" required>
                  <option value="">Select client account</option>
                  <?php foreach ($clientAccounts as $account): ?>
                    <option value="<?= htmlspecialchars((string) ($account['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) (($account['name'] ?? '') . ' - ' . ($account['company'] ?? '')), ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                Tracking Number
                <input type="text" name="tracking_reference" placeholder="BG tracking number or shipment ref">
              </label>
            </div>

            <div class="form-grid">
              <label>
                Amount
                <input type="number" name="amount" min="0" step="0.01" placeholder="Invoice amount" required>
              </label>
              <label>
                Currency
                <input type="text" name="currency" value="KES" required>
              </label>
            </div>

            <div class="form-grid">
              <label>
                Status
                <select name="status" required>
                  <option value="Due">Due</option>
                  <option value="Paid">Paid</option>
                </select>
              </label>
              <label>
                Due Date
                <input type="date" name="due_date" required>
              </label>
            </div>

            <label>
              Service Description
              <textarea name="description" placeholder="What the invoice covers, shipment leg, handling charges, or customs services" required></textarea>
            </label>

            <div class="form-grid">
              <label>
                Bank Name
                <input type="text" name="bank_name" placeholder="Bank name">
              </label>
              <label>
                Account Name
                <input type="text" name="account_name" placeholder="Account name">
              </label>
            </div>

            <div class="form-grid">
              <label>
                Account Number
                <input type="text" name="account_number" placeholder="Account number">
              </label>
              <label>
                Branch / SWIFT
                <input type="text" name="bank_branch" placeholder="Branch">
              </label>
            </div>

            <label>
              SWIFT Code
              <input type="text" name="swift_code" placeholder="SWIFT or routing code">
            </label>

            <label>
              M-Pesa Details
              <textarea name="mpesa_details" placeholder="Paybill, account number, till number, or M-Pesa instructions"></textarea>
            </label>

            <label>
              PayPal Details
              <textarea name="paypal_details" placeholder="PayPal email or payment link"></textarea>
            </label>

            <label>
              Payment Instructions
              <textarea name="payment_instructions" placeholder="Tell the client how to quote the invoice number, where to send payment proof, and any timing notes"></textarea>
            </label>

            <button type="submit">Create Invoice</button>
          </form>
        </article>

        <article class="dashboard-card detail-card">
          <div class="detail-panel">
            <h3>Client Payment Flow</h3>
            <ul class="dashboard-list">
              <li><span>Step 1<br><small>Client opens the invoice link</small></span><span>Invoice view</span></li>
              <li><span>Step 2<br><small>Client sees payment instructions</small></span><span>Bank / M-Pesa / PayPal</span></li>
              <li><span>Step 3<br><small>Client pays outside the platform</small></span><span>External settlement</span></li>
              <li><span>Step 4<br><small>Client submits payment reference</small></span><span>Accounts review</span></li>
            </ul>
          </div>

          <?php if (is_array($createdInvoice)): ?>
            <div class="detail-panel">
              <h3>Invoice Created</h3>
              <p><strong><?= htmlspecialchars((string) ($createdInvoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
              <p class="muted">Open the invoice below to confirm the payment layout the client will see.</p>
              <a class="button primary" href="invoice-view.php?id=<?= (int) ($createdInvoice['id'] ?? 0) ?>">Open Invoice</a>
            </div>
          <?php endif; ?>
        </article>
      </section>
    </main>

    <script src="/js/script.js"></script>
  </body>
</html>
