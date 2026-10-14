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

if (($result['success'] ?? false) === true) {
    $requestType = strtolower(trim((string) ($_POST['request_type'] ?? 'quote')));
    $labels = [
        'request_type' => 'Request type',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone / WhatsApp',
        'type' => 'Shipment type',
        'origin' => 'Origin',
        'destination' => 'Destination',
        'mode' => 'Freight mode',
        'weight' => 'Weight (kg)',
        'product_category' => 'Product category',
        'quantity' => 'Quantity',
        'market' => 'Preferred sourcing market',
        'budget' => 'Budget range',
        'notes' => 'Cargo notes',
        'product_details' => 'Product details',
    ];
    $details = [];
    foreach ($labels as $key => $label) {
        $value = trim((string) ($_POST[$key] ?? ''));
        if ($value !== '') {
            $details[] = $label . ': ' . $value;
        }
    }

    $subject = 'New ' . ($requestType === 'sourcing' ? 'sourcing' : 'shipping') . ' quote request';
    $body = "A new quote request was submitted on banilogistics.co.ke.\n\n"
        . implode("\n", $details)
        . "\n\nPlease follow up with the customer.";
    $headers = [
        'From: Bani Global Logistics <operations@banilogistics.co.ke>',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    $replyTo = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    if ($replyTo !== false) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    $emailAccepted = false;
    if (function_exists('mail')) {
        try {
            $emailAccepted = mail(
                'operations@banilogistics.co.ke',
                $subject,
                $body,
                implode("\r\n", $headers)
            );
        } catch (Throwable $exception) {
            $emailAccepted = false;
        }
    }

    $result['email_notification_accepted'] = $emailAccepted;
}

echo json_encode($result);
