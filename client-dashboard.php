<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
bani_require_role('client');
$user = bani_current_user();
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
            Signed in as <strong><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></strong>
            (<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>). Review cargo progress,
            invoice status, quote history, and milestone visibility from one secure portal.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button primary" href="quote.html">New Quote Request</a>
          <a class="button secondary" href="track.html">Track Shipment</a>
        </div>
      </section>

      <section class="dashboard-stats">
        <article class="dashboard-stat"><strong>04</strong><span>Active shipments</span></article>
        <article class="dashboard-stat"><strong>02</strong><span>Open quotes</span></article>
        <article class="dashboard-stat"><strong>KES 184K</strong><span>Outstanding invoices</span></article>
        <article class="dashboard-stat"><strong>96%</strong><span>On-time milestone rate</span></article>
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
              <tr><td>BANI123</td><td>Guangzhou to Nairobi</td><td><span class="badge badge-blue">In Transit</span></td><td>Arrival scan at destination hub</td></tr>
              <tr><td>BANI456</td><td>Dubai to Mombasa</td><td><span class="badge badge-gold">Customs</span></td><td>Awaiting clearance release</td></tr>
              <tr><td>BANI789</td><td>Nairobi station to Westlands</td><td><span class="badge badge-green">Out for Delivery</span></td><td>Customer handoff confirmation</td></tr>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h3>Latest Activity</h3>
          <div class="timeline">
            <div class="timeline-item"><strong>Customs documents approved</strong><span>BANI456 • 09:40 AM</span></div>
            <div class="timeline-item"><strong>Invoice generated</strong><span>INV-2048 • 08:15 AM</span></div>
            <div class="timeline-item"><strong>Quote response shared</strong><span>Q-1184 • Yesterday</span></div>
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
            <li><span>Q-1184<br><small>Air freight to Nairobi</small></span><span class="badge badge-blue">Pending</span></li>
            <li><span>Q-1181<br><small>Sea freight to Mombasa</small></span><span class="badge badge-green">Approved</span></li>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Invoice Center</h3>
          <ul class="dashboard-list">
            <li><span>INV-2048<br><small>Shipment handling</small></span><span class="badge badge-red">Due</span></li>
            <li><span>INV-2039<br><small>Customs support</small></span><span class="badge badge-green">Paid</span></li>
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
