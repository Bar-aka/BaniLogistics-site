<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal-data.php';

bani_require_role('client');
$user = bani_current_user();
$clientEmail = (string) ($user['email'] ?? '');
$shipments = bani_fetch_shipments($clientEmail, 10);
$quotes = bani_fetch_quotes($clientEmail, 10);
$invoices = bani_fetch_invoices($clientEmail, 10);
$summary = bani_client_summary($clientEmail);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard | Bani Global Logistics Limited</title>
    <meta name="description" content="Client dashboard for shipments, invoices, quote requests, and live logistics updates.">
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
          <a class="active" href="client-dashboard.php">Client Portal</a>
          <a href="contact.html">Contact</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Client dashboard</div>
          <h1>Manage shipments, quotes, and invoices in one workspace.</h1>
          <p>
            Signed in as <strong><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
            (<?= htmlspecialchars($clientEmail, ENT_QUOTES, 'UTF-8') ?>). Review cargo progress,
            invoice status, quote history, and milestone visibility from one secure portal.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button primary" href="quote.html">New Quote Request</a>
          <a class="button secondary" href="track.html">Track Shipment</a>
        </div>
      </section>

      <section class="dashboard-stats">
        <article class="dashboard-stat"><strong><?= (int) $summary['shipments'] ?></strong><span>Active shipments</span></article>
        <article class="dashboard-stat"><strong><?= (int) $summary['quotes'] ?></strong><span>Open quotes</span></article>
        <article class="dashboard-stat"><strong>KES <?= number_format((float) $summary['outstanding'], 2) ?></strong><span>Outstanding invoices</span></article>
        <article class="dashboard-stat"><strong><?= (int) $summary['invoices'] ?></strong><span>Invoice records</span></article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Shipment Overview</h2>
          <p class="dashboard-subtitle">Current cargo movements and latest milestones.</p>
          <table class="dashboard-table">
            <thead>
              <tr><th>Reference</th><th>Route</th><th>Status</th><th>Next Step</th></tr>
            </thead>
            <tbody>
              <?php if ($shipments === []): ?>
                <tr><td colspan="4">No shipment records are available for this account yet.</td></tr>
              <?php else: ?>
                <?php foreach (array_slice($shipments, 0, 6) as $shipment): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) (($shipment['origin'] ?? '') . ' to ' . ($shipment['destination'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= stripos((string) ($shipment['status'] ?? ''), 'customs') !== false ? 'badge-gold' : 'badge-blue' ?>"><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars((string) ($shipment['next_step'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h3>Latest Account Activity</h3>
          <div class="timeline">
            <?php foreach (array_slice($shipments, 0, 2) as $shipment): ?>
              <div class="timeline-item">
                <strong><?= htmlspecialchars((string) ($shipment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?> status updated</strong>
                <span><?= htmlspecialchars((string) ($shipment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars(bani_format_datetime($shipment['updated_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            <?php endforeach; ?>
            <?php foreach (array_slice($quotes, 0, 1) as $quote): ?>
              <div class="timeline-item">
                <strong><?= htmlspecialchars((string) ($quote['quote_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?> quote available</strong>
                <span><?= htmlspecialchars((string) (($quote['currency'] ?? 'KES') . ' ' . number_format((float) ($quote['amount'] ?? 0), 2) . ' | ' . ($quote['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            <?php endforeach; ?>
            <?php foreach (array_slice($invoices, 0, 1) as $invoice): ?>
              <div class="timeline-item">
                <strong><a href="invoice-view.php?id=<?= (int) ($invoice['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a> invoice recorded</strong>
                <span>Due <?= htmlspecialchars((string) ($invoice['due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string) ($invoice['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            <?php endforeach; ?>
            <?php if ($shipments === [] && $quotes === [] && $invoices === []): ?>
              <div class="timeline-item"><strong>No activity yet</strong><span>Your shipment, quote, and invoice activity will appear here once records are created.</span></div>
            <?php endif; ?>
          </div>
        </article>
      </section>

      <section class="dashboard-mini-grid">
        <article class="dashboard-card">
          <h3>Account Profile</h3>
          <ul class="dashboard-list">
            <li><span>Account Name<br><small>Registered portal identity</small></span><span><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Company<br><small>Business profile on record</small></span><span><?= htmlspecialchars((string) ($user['company'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Phone Number<br><small>Primary contact line</small></span><span><?= htmlspecialchars((string) ($user['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Portal Status<br><small>Current access standing</small></span><span class="badge <?= (($user['status'] ?? 'active') === 'active') ? 'badge-green' : 'badge-red' ?>"><?= htmlspecialchars((string) ($user['status'] ?? 'active'), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Registered On<br><small>Account creation date</small></span><span><?= htmlspecialchars(bani_format_datetime($user['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></li>
            <li><span>Last Login<br><small>Most recent portal access</small></span><span><?= htmlspecialchars(bani_format_datetime($user['last_login_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></li>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Open Quotes</h3>
          <ul class="dashboard-list">
            <?php foreach (array_slice($quotes, 0, 5) as $quote): ?>
              <li><span><?= htmlspecialchars((string) ($quote['quote_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars((string) (($quote['origin'] ?? '') . ' to ' . ($quote['destination'] ?? '')), ENT_QUOTES, 'UTF-8') ?></small></span><span class="badge badge-blue"><?= htmlspecialchars((string) ($quote['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <?php endforeach; ?>
            <?php if ($quotes === []): ?>
              <li><span>No quotes yet<br><small>Submitted and issued quotes will appear here.</small></span><span class="badge badge-blue">Awaiting</span></li>
            <?php endif; ?>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Invoice Center</h3>
          <ul class="dashboard-list">
            <?php foreach (array_slice($invoices, 0, 5) as $invoice): ?>
              <li><span><a href="invoice-view.php?id=<?= (int) ($invoice['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($invoice['invoice_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a><br><small><?= htmlspecialchars((string) ($invoice['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></span><span class="badge <?= strtolower((string) ($invoice['status'] ?? '')) === 'paid' ? 'badge-green' : 'badge-red' ?>"><?= htmlspecialchars((string) ($invoice['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
            <?php endforeach; ?>
            <?php if ($invoices === []): ?>
              <li><span>No invoices yet<br><small>Billing records will appear here once issued.</small></span><span class="badge badge-blue">Awaiting</span></li>
            <?php endif; ?>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Support Contacts</h3>
          <ul class="dashboard-list">
            <li><span>Operations<br><small>ops@banilogistics.co.ke</small></span><span>+254 782 013 236</span></li>
            <li><span>Accounts<br><small>accounts@banilogistics.co.ke</small></span><span>Invoice desk</span></li>
            <li><span>Support<br><small>support@banilogistics.co.ke</small></span><span>Follow-ups</span></li>
          </ul>
        </article>
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
  </body>
</html>
