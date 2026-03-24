<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
bani_require_role('staff');
$user = bani_current_user();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Bani Global Logistics Limited</title>
    <meta name="description" content="Operational dashboard for staff managing shipments, customs, and customer handoffs.">
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
          <a class="active" href="staff-dashboard.php">Staff Dashboard</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main class="dashboard-shell">
      <section class="dashboard-hero">
        <div>
          <div class="eyebrow">Staff dashboard</div>
          <h1>Coordinate daily operations with cleaner shipment visibility.</h1>
          <p>
            Signed in as <strong><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></strong>.
            Manage active jobs, customs handoffs, route coordination, and customer-facing checkpoints from one secure operations view.
          </p>
        </div>
        <div class="dashboard-actions">
          <a class="button primary" href="track.html">Open Tracking Page</a>
          <a class="button secondary" href="contact.html">Support Desk</a>
        </div>
      </section>

      <section class="dashboard-stats">
        <article class="dashboard-stat"><strong>18</strong><span>Active jobs today</span></article>
        <article class="dashboard-stat"><strong>06</strong><span>Customs files pending</span></article>
        <article class="dashboard-stat"><strong>09</strong><span>Delivery handoffs due</span></article>
        <article class="dashboard-stat"><strong>03</strong><span>High-priority escalations</span></article>
      </section>

      <section class="dashboard-grid">
        <article class="dashboard-card">
          <h2>Operations Queue</h2>
          <p class="dashboard-subtitle">Priority shipment actions requiring staff attention.</p>
          <table class="dashboard-table">
            <thead>
              <tr><th>Ref</th><th>Task</th><th>Owner</th><th>Priority</th></tr>
            </thead>
            <tbody>
              <tr><td>BANI456</td><td>Customs release follow-up</td><td>Amina</td><td><span class="badge badge-gold">High</span></td></tr>
              <tr><td>BANI912</td><td>Container arrival booking</td><td>Brian</td><td><span class="badge badge-blue">Normal</span></td></tr>
              <tr><td>BANI789</td><td>Confirm final delivery</td><td>Mercy</td><td><span class="badge badge-green">Today</span></td></tr>
            </tbody>
          </table>
        </article>

        <article class="dashboard-card">
          <h3>Team Notes</h3>
          <div class="timeline">
            <div class="timeline-item"><strong>Driver reassigned for Westlands route</strong><p>Last-mile route updated to avoid congestion.</p></div>
            <div class="timeline-item"><strong>Supplier documents received</strong><p>Commercial invoice and packing list uploaded for customs review.</p></div>
            <div class="timeline-item"><strong>Customer follow-up requested</strong><p>Support team to confirm ETA on BANI123.</p></div>
          </div>
        </article>
      </section>

      <section class="dashboard-mini-grid">
        <article class="dashboard-card">
          <h3>Clearance Board</h3>
          <ul class="dashboard-list">
            <li><span>BANI456<br><small>IDF and invoice review</small></span><span class="badge badge-gold">Pending</span></li>
            <li><span>BANI623<br><small>KRA release issued</small></span><span class="badge badge-green">Ready</span></li>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Dispatch Schedule</h3>
          <ul class="dashboard-list">
            <li><span>Nairobi CBD<br><small>2 customer drops</small></span><span>10:30 AM</span></li>
            <li><span>Industrial Area<br><small>Warehouse release</small></span><span>1:15 PM</span></li>
          </ul>
        </article>
        <article class="dashboard-card">
          <h3>Escalations</h3>
          <ul class="dashboard-list">
            <li><span>Delayed carrier scan<br><small>BANI305</small></span><span class="badge badge-red">Action</span></li>
            <li><span>Missing consignee number<br><small>BANI811</small></span><span class="badge badge-gold">Review</span></li>
          </ul>
        </article>
      </section>
    </main>

    <a class="whatsapp-float" href="https://wa.me/254782013236" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
  </body>
</html>
