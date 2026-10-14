<?php
declare(strict_types=1);

require_once __DIR__ . '/portal-data.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

function bani_public_tracking_reply(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    bani_public_tracking_reply(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$reference = strtoupper(trim((string) ($_GET['reference'] ?? '')));
if ($reference === '' || preg_match('/^[A-Z0-9][A-Z0-9-]{2,39}$/', $reference) !== 1) {
    bani_public_tracking_reply(400, ['success' => false, 'message' => 'Enter a valid shipment reference.']);
}

$pdo = bani_db();
if (!$pdo instanceof PDO || !bani_records_table_available('portal_shipments')) {
    bani_public_tracking_reply(503, ['success' => false, 'message' => 'Tracking is temporarily unavailable.']);
}

$statement = $pdo->prepare('SELECT status FROM portal_shipments WHERE UPPER(reference) = :reference LIMIT 1');
$statement->execute([':reference' => $reference]);
$row = $statement->fetch();

if (!is_array($row)) {
    bani_public_tracking_reply(404, ['success' => false, 'message' => 'No shipment was found for that reference.']);
}

$rawStatus = strtolower(trim((string) ($row['status'] ?? '')));
if (preg_match('/\b(out for delivery|last.?mile)\b/', $rawStatus) === 1) {
    $publicStatus = 'Out for delivery';
} elseif (preg_match('/\b(delivered|complete|completed)\b/', $rawStatus) === 1) {
    $publicStatus = 'Delivered';
} elseif (str_contains($rawStatus, 'customs') || str_contains($rawStatus, 'clearance')) {
    $publicStatus = 'At customs';
} elseif (str_contains($rawStatus, 'port') || str_contains($rawStatus, 'terminal')) {
    $publicStatus = 'At port';
} elseif (str_contains($rawStatus, 'transit') || str_contains($rawStatus, 'depart')) {
    $publicStatus = 'In transit';
} else {
    $publicStatus = 'Processing';
}

bani_public_tracking_reply(200, [
    'success' => true,
    'public_status' => $publicStatus,
]);
