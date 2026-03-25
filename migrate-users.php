<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

header('Content-Type: text/plain; charset=UTF-8');

if (!bani_db_ready()) {
    echo "Database is not configured. Update config.php first.\n";
    exit;
}

$users = bani_load_file_users();
$migrated = 0;

foreach ($users as $user) {
    if (!isset($user['email'], $user['password_hash'], $user['name'], $user['role'])) {
        continue;
    }

    bani_upsert_db_user([
        'email' => (string) $user['email'],
        'name' => (string) $user['name'],
        'phone' => (string) ($user['phone'] ?? ''),
        'company' => (string) ($user['company'] ?? ''),
        'role' => (string) ($user['role'] ?? 'client'),
        'status' => (string) ($user['status'] ?? 'active'),
        'password_hash' => (string) $user['password_hash'],
        'created_at' => (string) ($user['created_at'] ?? gmdate('Y-m-d H:i:s')),
        'last_login_at' => $user['last_login_at'] ?? null,
    ]);

    $migrated++;
}

echo "Migration completed. Users processed: {$migrated}\n";
