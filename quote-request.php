<?php
declare(strict_types=1);

require_once __DIR__ . '/portal-data.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$result = bani_create_quote_request($_POST);
echo json_encode($result);
