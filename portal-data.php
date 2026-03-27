<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function bani_records_table_available(string $tableName): bool
{
    $pdo = bani_db();

    if (!$pdo instanceof PDO) {
        return false;
    }

    static $cache = [];

    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    try {
        $statement = $pdo->prepare('SHOW TABLES LIKE :table_name');
        $statement->execute([':table_name' => $tableName]);
        $cache[$tableName] = (bool) $statement->fetchColumn();
    } catch (Throwable $exception) {
        $cache[$tableName] = false;
    }

    return $cache[$tableName];
}

function bani_records_ready(): bool
{
    return bani_records_table_available('portal_shipments')
        && bani_records_table_available('portal_quotes')
        && bani_records_table_available('portal_invoices');
}

function bani_fetch_shipments(?string $clientEmail = null, int $limit = 20): array
{
    $pdo = bani_db();

    if (!$pdo instanceof PDO || !bani_records_table_available('portal_shipments')) {
        return [];
    }

    $limit = max(1, min($limit, 100));

    if ($clientEmail !== null) {
        $statement = $pdo->prepare(
            "SELECT id, client_email, reference, client_name, assigned_to, assigned_name, origin, destination, mode, status, next_step, internal_notes, created_at, updated_at
             FROM portal_shipments
             WHERE client_email = :client_email
             ORDER BY created_at DESC
             LIMIT {$limit}"
        );
        $statement->execute([':client_email' => strtolower(trim($clientEmail))]);
    } else {
        $statement = $pdo->query(
            "SELECT id, client_email, reference, client_name, assigned_to, assigned_name, origin, destination, mode, status, next_step, internal_notes, created_at, updated_at
             FROM portal_shipments
             ORDER BY created_at DESC
             LIMIT {$limit}"
        );
    }

    $rows = $statement->fetchAll();

    return is_array($rows) ? $rows : [];
}

function bani_fetch_quotes(?string $clientEmail = null, int $limit = 20): array
{
    $pdo = bani_db();

    if (!$pdo instanceof PDO || !bani_records_table_available('portal_quotes')) {
        return [];
    }

    $limit = max(1, min($limit, 100));

    if ($clientEmail !== null) {
        $statement = $pdo->prepare(
            "SELECT id, client_email, quote_number, client_name, shipment_type, origin, destination, mode, amount, currency, status, created_at, updated_at
             FROM portal_quotes
             WHERE client_email = :client_email
             ORDER BY created_at DESC
             LIMIT {$limit}"
        );
        $statement->execute([':client_email' => strtolower(trim($clientEmail))]);
    } else {
        $statement = $pdo->query(
            "SELECT id, client_email, quote_number, client_name, shipment_type, origin, destination, mode, amount, currency, status, created_at, updated_at
             FROM portal_quotes
             ORDER BY created_at DESC
             LIMIT {$limit}"
        );
    }

    $rows = $statement->fetchAll();

    return is_array($rows) ? $rows : [];
}

function bani_fetch_invoices(?string $clientEmail = null, int $limit = 20): array
{
    $pdo = bani_db();

    if (!$pdo instanceof PDO || !bani_records_table_available('portal_invoices')) {
        return [];
    }

    $limit = max(1, min($limit, 100));

    if ($clientEmail !== null) {
        $statement = $pdo->prepare(
            "SELECT id, client_email, invoice_number, client_name, description, amount, currency, status, due_date, created_at, updated_at
             FROM portal_invoices
             WHERE client_email = :client_email
             ORDER BY created_at DESC
             LIMIT {$limit}"
        );
        $statement->execute([':client_email' => strtolower(trim($clientEmail))]);
    } else {
        $statement = $pdo->query(
            "SELECT id, client_email, invoice_number, client_name, description, amount, currency, status, due_date, created_at, updated_at
             FROM portal_invoices
             ORDER BY created_at DESC
             LIMIT {$limit}"
        );
    }

    $rows = $statement->fetchAll();

    return is_array($rows) ? $rows : [];
}

function bani_client_summary(string $clientEmail): array
{
    $shipments = bani_fetch_shipments($clientEmail, 100);
    $quotes = bani_fetch_quotes($clientEmail, 100);
    $invoices = bani_fetch_invoices($clientEmail, 100);

    $outstanding = 0.0;
    foreach ($invoices as $invoice) {
        if (strtolower((string) ($invoice['status'] ?? '')) !== 'paid') {
            $outstanding += (float) ($invoice['amount'] ?? 0);
        }
    }

    return [
        'shipments' => count($shipments),
        'quotes' => count($quotes),
        'invoices' => count($invoices),
        'outstanding' => $outstanding,
    ];
}

function bani_next_reference(string $prefix, string $table, string $column): string
{
    $pdo = bani_db();

    if (!$pdo instanceof PDO) {
        return $prefix . date('His');
    }

    $statement = $pdo->query("SELECT COUNT(*) FROM {$table}");
    $count = (int) $statement->fetchColumn();

    return sprintf('%s%04d', $prefix, $count + 1);
}

function bani_create_shipment(array $input): array
{
    $pdo = bani_db();

    if (!$pdo instanceof PDO || !bani_records_table_available('portal_shipments')) {
        return ['success' => false, 'message' => 'Shipment storage is not ready yet.'];
    }

    $clientEmail = strtolower(trim((string) ($input['client_email'] ?? '')));
    $origin = trim((string) ($input['origin'] ?? ''));
    $destination = trim((string) ($input['destination'] ?? ''));
    $mode = trim((string) ($input['mode'] ?? ''));
    $status = trim((string) ($input['status'] ?? ''));
    $nextStep = trim((string) ($input['next_step'] ?? ''));
    $assignedTo = strtolower(trim((string) ($input['assigned_to'] ?? '')));

    if ($clientEmail === '' || $origin === '' || $destination === '' || $mode === '' || $status === '' || $nextStep === '') {
        return ['success' => false, 'message' => 'Please complete all shipment fields.'];
    }

    $client = bani_find_user($clientEmail);
    if ($client === null || ($client['role'] ?? '') !== 'client') {
        return ['success' => false, 'message' => 'Select a valid client account for this shipment.'];
    }

    $assignedName = null;
    if ($assignedTo !== '') {
        $staff = bani_find_user($assignedTo);
        if ($staff === null || !in_array((string) ($staff['role'] ?? ''), ['staff', 'admin'], true)) {
            return ['success' => false, 'message' => 'Select a valid staff or admin account for assignment.'];
        }
        $assignedName = (string) ($staff['name'] ?? $assignedTo);
    }

    $reference = bani_next_reference('BANI', 'portal_shipments', 'reference');
    $timestamp = gmdate('Y-m-d H:i:s');

    $statement = $pdo->prepare(
        'INSERT INTO portal_shipments (client_email, reference, client_name, assigned_to, assigned_name, origin, destination, mode, status, next_step, internal_notes, created_at, updated_at)
         VALUES (:client_email, :reference, :client_name, :assigned_to, :assigned_name, :origin, :destination, :mode, :status, :next_step, :internal_notes, :created_at, :updated_at)'
    );

    $statement->execute([
        ':client_email' => $client['email'],
        ':reference' => $reference,
        ':client_name' => $client['name'],
        ':assigned_to' => $assignedTo !== '' ? $assignedTo : null,
        ':assigned_name' => $assignedName,
        ':origin' => $origin,
        ':destination' => $destination,
        ':mode' => $mode,
        ':status' => $status,
        ':next_step' => $nextStep,
        ':internal_notes' => trim((string) ($input['internal_notes'] ?? '')) ?: null,
        ':created_at' => $timestamp,
        ':updated_at' => $timestamp,
    ]);

    return ['success' => true, 'message' => "Shipment {$reference} created successfully."];
}

function bani_update_shipment(int $shipmentId, array $input): array
{
    $pdo = bani_db();

    if (!$pdo instanceof PDO || !bani_records_table_available('portal_shipments')) {
        return ['success' => false, 'message' => 'Shipment storage is not ready yet.'];
    }

    $statement = $pdo->prepare('SELECT * FROM portal_shipments WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $shipmentId]);
    $shipment = $statement->fetch();

    if (!is_array($shipment)) {
        return ['success' => false, 'message' => 'Shipment record was not found.'];
    }

    $status = trim((string) ($input['status'] ?? $shipment['status']));
    $nextStep = trim((string) ($input['next_step'] ?? $shipment['next_step']));
    $assignedTo = strtolower(trim((string) ($input['assigned_to'] ?? $shipment['assigned_to'] ?? '')));
    $internalNotes = trim((string) ($input['internal_notes'] ?? $shipment['internal_notes'] ?? ''));

    if ($status === '' || $nextStep === '') {
        return ['success' => false, 'message' => 'Status and next step are required for shipment updates.'];
    }

    $assignedName = null;
    if ($assignedTo !== '') {
        $staff = bani_find_user($assignedTo);
        if ($staff === null || !in_array((string) ($staff['role'] ?? ''), ['staff', 'admin'], true)) {
            return ['success' => false, 'message' => 'Select a valid staff or admin account for shipment assignment.'];
        }
        $assignedName = (string) ($staff['name'] ?? $assignedTo);
    }

    $update = $pdo->prepare(
        'UPDATE portal_shipments
         SET assigned_to = :assigned_to,
             assigned_name = :assigned_name,
             status = :status,
             next_step = :next_step,
             internal_notes = :internal_notes,
             updated_at = :updated_at
         WHERE id = :id'
    );

    $update->execute([
        ':assigned_to' => $assignedTo !== '' ? $assignedTo : null,
        ':assigned_name' => $assignedName,
        ':status' => $status,
        ':next_step' => $nextStep,
        ':internal_notes' => $internalNotes !== '' ? $internalNotes : null,
        ':updated_at' => gmdate('Y-m-d H:i:s'),
        ':id' => $shipmentId,
    ]);

    return ['success' => true, 'message' => 'Shipment updated successfully.'];
}

function bani_update_invoice_status(int $invoiceId, string $status): array
{
    $pdo = bani_db();

    if (!$pdo instanceof PDO || !bani_records_table_available('portal_invoices')) {
        return ['success' => false, 'message' => 'Invoice storage is not ready yet.'];
    }

    $status = trim($status);
    if ($status === '') {
        return ['success' => false, 'message' => 'Invoice status is required.'];
    }

    $statement = $pdo->prepare('UPDATE portal_invoices SET status = :status, updated_at = :updated_at WHERE id = :id');
    $statement->execute([
        ':status' => $status,
        ':updated_at' => gmdate('Y-m-d H:i:s'),
        ':id' => $invoiceId,
    ]);

    return ['success' => true, 'message' => 'Invoice status updated successfully.'];
}

function bani_staff_users(): array
{
    return array_values(array_filter(
        bani_list_users(),
        static fn(array $user): bool => in_array((string) ($user['role'] ?? ''), ['staff', 'admin'], true)
    ));
}

function bani_count_shipments_by_status(array $shipments, string $needle): int
{
    return count(array_filter(
        $shipments,
        static fn(array $shipment): bool => stripos((string) ($shipment['status'] ?? ''), $needle) !== false
    ));
}

function bani_create_quote(array $input): array
{
    $pdo = bani_db();

    if (!$pdo instanceof PDO || !bani_records_table_available('portal_quotes')) {
        return ['success' => false, 'message' => 'Quote storage is not ready yet.'];
    }

    $clientEmail = strtolower(trim((string) ($input['client_email'] ?? '')));
    $shipmentType = trim((string) ($input['shipment_type'] ?? ''));
    $origin = trim((string) ($input['origin'] ?? ''));
    $destination = trim((string) ($input['destination'] ?? ''));
    $mode = trim((string) ($input['mode'] ?? ''));
    $amount = (float) ($input['amount'] ?? 0);
    $currency = trim((string) ($input['currency'] ?? 'KES'));
    $status = trim((string) ($input['status'] ?? ''));

    if ($clientEmail === '' || $shipmentType === '' || $origin === '' || $destination === '' || $mode === '' || $amount <= 0 || $status === '') {
        return ['success' => false, 'message' => 'Please complete all quote fields with a valid amount.'];
    }

    $client = bani_find_user($clientEmail);
    if ($client === null || ($client['role'] ?? '') !== 'client') {
        return ['success' => false, 'message' => 'Select a valid client account for this quote.'];
    }

    $quoteNumber = bani_next_reference('Q-', 'portal_quotes', 'quote_number');
    $timestamp = gmdate('Y-m-d H:i:s');

    $statement = $pdo->prepare(
        'INSERT INTO portal_quotes (client_email, quote_number, client_name, shipment_type, origin, destination, mode, amount, currency, status, created_at, updated_at)
         VALUES (:client_email, :quote_number, :client_name, :shipment_type, :origin, :destination, :mode, :amount, :currency, :status, :created_at, :updated_at)'
    );

    $statement->execute([
        ':client_email' => $client['email'],
        ':quote_number' => $quoteNumber,
        ':client_name' => $client['name'],
        ':shipment_type' => $shipmentType,
        ':origin' => $origin,
        ':destination' => $destination,
        ':mode' => $mode,
        ':amount' => $amount,
        ':currency' => $currency,
        ':status' => $status,
        ':created_at' => $timestamp,
        ':updated_at' => $timestamp,
    ]);

    return ['success' => true, 'message' => "Quote {$quoteNumber} created successfully."];
}

function bani_create_invoice(array $input): array
{
    $pdo = bani_db();

    if (!$pdo instanceof PDO || !bani_records_table_available('portal_invoices')) {
        return ['success' => false, 'message' => 'Invoice storage is not ready yet.'];
    }

    $clientEmail = strtolower(trim((string) ($input['client_email'] ?? '')));
    $description = trim((string) ($input['description'] ?? ''));
    $amount = (float) ($input['amount'] ?? 0);
    $currency = trim((string) ($input['currency'] ?? 'KES'));
    $status = trim((string) ($input['status'] ?? ''));
    $dueDate = trim((string) ($input['due_date'] ?? ''));

    if ($clientEmail === '' || $description === '' || $amount <= 0 || $currency === '' || $status === '' || $dueDate === '') {
        return ['success' => false, 'message' => 'Please complete all invoice fields with a valid amount and due date.'];
    }

    $client = bani_find_user($clientEmail);
    if ($client === null || ($client['role'] ?? '') !== 'client') {
        return ['success' => false, 'message' => 'Select a valid client account for this invoice.'];
    }

    $invoiceNumber = bani_next_reference('INV-', 'portal_invoices', 'invoice_number');
    $timestamp = gmdate('Y-m-d H:i:s');

    $statement = $pdo->prepare(
        'INSERT INTO portal_invoices (client_email, invoice_number, client_name, description, amount, currency, status, due_date, created_at, updated_at)
         VALUES (:client_email, :invoice_number, :client_name, :description, :amount, :currency, :status, :due_date, :created_at, :updated_at)'
    );

    $statement->execute([
        ':client_email' => $client['email'],
        ':invoice_number' => $invoiceNumber,
        ':client_name' => $client['name'],
        ':description' => $description,
        ':amount' => $amount,
        ':currency' => $currency,
        ':status' => $status,
        ':due_date' => $dueDate,
        ':created_at' => $timestamp,
        ':updated_at' => $timestamp,
    ]);

    return ['success' => true, 'message' => "Invoice {$invoiceNumber} created successfully."];
}
