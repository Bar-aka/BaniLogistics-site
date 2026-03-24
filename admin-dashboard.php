<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
bani_require_role('admin');
$user = bani_current_user();
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
          <h1>Monitor business performance, workload, and service health.</h1>
          <p>
            Signed in as <strong><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></strong>.
            Review quotes, invoicing, active jobs, staff productivity, and service performance from a secured management view.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button primary" href="staff-dashboard.php">Open Staff Dashboard</a>
          <a class="button secondary" href="client-dashboard.php">Open Client Portal</a>
        </div>
      </section>

      <section class="dashboard-stats">
        <article class="dashboard-stat"><strong>KES 4.2M</strong><span>Monthly billed volume</span></article>
        <article class="dashboard-stat"><strong>27</strong><span>Active customers</span></article>
        <article class="dashboard-stat"><strong>41</strong><span>Open shipments</span></article>
        <article class="dashboard-stat"><strong>87%</strong><span>Quote conversion rate</span></article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Management Snapshot</h2>
          <p class="dashboard-subtitle">Current commercial and operational picture.</p>
          <table class="dashboard-table">
            <thead>
              <tr><th>Area</th><th>Current</th><th>Direction</th></tr>
            </thead>
            <tbody>
              <tr><td>Open Quotes</td><td>13</td><td><span class="badge badge-blue">Steady</span></td></tr>
              <tr><td>Invoices Due</td><td>KES 184K</td><td><span class="badge badge-gold">Review</span></td></tr>
              <tr><td>Delayed Shipments</td><td>3</td><td><span class="badge badge-red">Attention</span></td></tr>
              <tr><td>Cleared Deliveries</td><td>22</td><td><span class="badge badge-green">Healthy</span></td></tr>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h3>Leadership Priorities</h3>
          <div class="timeline">
            <div class="timeline-item"><strong>Follow up overdue invoice queue</strong><p>Accounts to chase 4 customer balances above 14 days.</p></div>
            <div class="timeline-item"><strong>Resolve three delayed import files</strong><p>Operations to clear documentation blockers before noon.</p></div>
            <div class="timeline-item"><strong>Review quote response speed</strong><p>Average turnaround improved, but same-day target still needs tightening.</p></div>
          </div>
        </article>
      </section>

      <section class="dashboard-mini-grid">
        <article class="dashboard-card">
          <h3>Team Workload</h3>
          <ul class="dashboard-list">
            <li><span>Amina<br><small>Clearance and compliance</small></span><span>7 files</span></li>
            <li><span>Brian<br><small>Freight coordination</small></span><span>6 files</span></li>
            <li><span>Mercy<br><small>Delivery operations</small></span><span>5 files</span></li>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Commercial Pipeline</h3>
          <ul class="dashboard-list">
            <li><span>Importer accounts<br><small>3 proposals awaiting approval</small></span><span class="badge badge-blue">Open</span></li>
            <li><span>Export clients<br><small>2 renewals this week</small></span><span class="badge badge-green">Hot</span></li>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Control Center</h3>
          <ul class="dashboard-list">
            <li><span>Operations<br><small>ops@banilogistics.co.ke</small></span><span>Live</span></li>
            <li><span>Accounts<br><small>accounts@banilogistics.co.ke</small></span><span>Billing</span></li>
            <li><span>Support<br><small>support@banilogistics.co.ke</small></span><span>Follow-ups</span></li>
          </ul>
        </article>
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
  </body>
</html>
