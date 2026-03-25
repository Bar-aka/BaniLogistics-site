<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config.php';

const BANI_ALLOWED_ROLES = ['client', 'staff', 'admin'];
const BANI_ALLOWED_STATUSES = ['active', 'suspended'];

function bani_storage_dir(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'storage';
}

function bani_users_path(): string
{
    return bani_storage_dir() . DIRECTORY_SEPARATOR . 'users.json';
}

function bani_seed_users(): array
{
    $now = gmdate('Y-m-d H:i:s');

    return [
        [
            'email' => 'client@banilogistics.co.ke',
            'name' => 'Client Account',
            'phone' => '+254 782 013 236',
            'company' => 'Bani Client Account',
            'role' => 'client',
            'status' => 'active',
            'password_hash' => password_hash('Client@123', PASSWORD_DEFAULT),
            'created_at' => $now,
            'last_login_at' => null,
        ],
        [
            'email' => 'ops@banilogistics.co.ke',
            'name' => 'Operations Team',
            'phone' => '+254 782 013 236',
            'company' => 'Bani Global Logistics Limited',
            'role' => 'staff',
            'status' => 'active',
            'password_hash' => password_hash('Staff@123', PASSWORD_DEFAULT),
            'created_at' => $now,
            'last_login_at' => null,
        ],
        [
            'email' => 'admin@banilogistics.co.ke',
            'name' => 'Admin Account',
            'phone' => '+254 782 013 236',
            'company' => 'Bani Global Logistics Limited',
            'role' => 'admin',
            'status' => 'active',
            'password_hash' => password_hash('Admin@123', PASSWORD_DEFAULT),
            'created_at' => $now,
            'last_login_at' => null,
        ],
    ];
}

function bani_normalize_user(array $user): array
{
    return [
        'email' => strtolower(trim((string) ($user['email'] ?? ''))),
        'name' => trim((string) ($user['name'] ?? '')),
        'phone' => trim((string) ($user['phone'] ?? '')),
        'company' => trim((string) ($user['company'] ?? '')),
        'role' => in_array(($user['role'] ?? ''), BANI_ALLOWED_ROLES, true) ? (string) $user['role'] : 'client',
        'status' => in_array(($user['status'] ?? ''), BANI_ALLOWED_STATUSES, true) ? (string) $user['status'] : 'active',
        'password_hash' => (string) ($user['password_hash'] ?? ''),
        'created_at' => (string) ($user['created_at'] ?? gmdate('Y-m-d H:i:s')),
        'last_login_at' => $user['last_login_at'] ?? null,
    ];
}

function bani_db_ready(): bool
{
    return BANI_DB_NAME !== '' && BANI_DB_USER !== '';
}

function bani_db(): ?PDO
{
    static $pdo = false;

    if ($pdo !== false) {
        return $pdo instanceof PDO ? $pdo : null;
    }

    if (!bani_db_ready()) {
        $pdo = null;
        return null;
    }

    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            BANI_DB_HOST,
            BANI_DB_PORT,
            BANI_DB_NAME
        );

        $pdo = new PDO($dsn, BANI_DB_USER, BANI_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $exception) {
        $pdo = null;
    }

    return $pdo instanceof PDO ? $pdo : null;
}

function bani_ensure_storage(): void
{
    $storageDir = bani_storage_dir();

    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
    }

    $htaccessPath = $storageDir . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($htaccessPath)) {
        file_put_contents($htaccessPath, "Require all denied\nDeny from all\n");
    }

    $usersPath = bani_users_path();
    if (!is_file($usersPath)) {
        bani_save_file_users(bani_seed_users());
    }
}

function bani_load_file_users(): array
{
    bani_ensure_storage();

    $json = file_get_contents(bani_users_path());
    $users = json_decode($json ?: '[]', true);

    if (!is_array($users)) {
        return [];
    }

    return array_map('bani_normalize_user', $users);
}

function bani_save_file_users(array $users): void
{
    $normalized = array_map('bani_normalize_user', array_values($users));
    $json = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents(bani_users_path(), $json . PHP_EOL, LOCK_EX);
}

function bani_find_user_index(string $email, array $users): ?int
{
    $needle = strtolower(trim($email));

    foreach ($users as $index => $user) {
        if (($user['email'] ?? '') === $needle) {
            return $index;
        }
    }

    return null;
}

function bani_load_users(): array
{
    $pdo = bani_db();

    if ($pdo instanceof PDO) {
        $statement = $pdo->query('SELECT email, name, phone, company, role, status, password_hash, created_at, last_login_at FROM portal_users ORDER BY created_at ASC');
        $rows = $statement->fetchAll();

        return array_map('bani_normalize_user', is_array($rows) ? $rows : []);
    }

    return bani_load_file_users();
}

function bani_upsert_db_user(array $user): bool
{
    $pdo = bani_db();

    if (!$pdo instanceof PDO) {
        return false;
    }

    $user = bani_normalize_user($user);

    $statement = $pdo->prepare(
        'INSERT INTO portal_users (email, name, phone, company, role, status, password_hash, created_at, last_login_at)
         VALUES (:email, :name, :phone, :company, :role, :status, :password_hash, :created_at, :last_login_at)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           phone = VALUES(phone),
           company = VALUES(company),
           role = VALUES(role),
           status = VALUES(status),
           password_hash = VALUES(password_hash),
           created_at = VALUES(created_at),
           last_login_at = VALUES(last_login_at)'
    );

    return $statement->execute([
        ':email' => $user['email'],
        ':name' => $user['name'],
        ':phone' => $user['phone'],
        ':company' => $user['company'],
        ':role' => $user['role'],
        ':status' => $user['status'],
        ':password_hash' => $user['password_hash'],
        ':created_at' => $user['created_at'],
        ':last_login_at' => $user['last_login_at'],
    ]);
}

function bani_find_user(string $email): ?array
{
    $email = strtolower(trim($email));
    $pdo = bani_db();

    if ($pdo instanceof PDO) {
        $statement = $pdo->prepare('SELECT email, name, phone, company, role, status, password_hash, created_at, last_login_at FROM portal_users WHERE email = :email LIMIT 1');
        $statement->execute([':email' => $email]);
        $row = $statement->fetch();

        return is_array($row) ? bani_normalize_user($row) : null;
    }

    $users = bani_load_file_users();
    $index = bani_find_user_index($email, $users);

    return $index === null ? null : $users[$index];
}

function bani_current_user(): ?array
{
    $user = $_SESSION['bani_user'] ?? null;

    if (!is_array($user) || !isset($user['email'])) {
        return null;
    }

    $freshUser = bani_find_user((string) $user['email']);
    if ($freshUser === null) {
        return null;
    }

    $_SESSION['bani_user'] = [
        'email' => $freshUser['email'],
        'role' => $freshUser['role'],
        'name' => $freshUser['name'],
        'phone' => $freshUser['phone'],
        'company' => $freshUser['company'],
        'status' => $freshUser['status'],
        'created_at' => $freshUser['created_at'],
        'last_login_at' => $freshUser['last_login_at'],
    ];

    return $_SESSION['bani_user'];
}

function bani_dashboard_url(string $role): string
{
    return match ($role) {
        'client' => 'client-dashboard.php',
        'staff' => 'staff-dashboard.php',
        'admin' => 'admin-dashboard.php',
        default => 'login.php',
    };
}

function bani_login(string $email, string $password, string $role): array
{
    $email = strtolower(trim($email));
    $user = bani_find_user($email);

    if ($user === null) {
        return ['success' => false, 'message' => 'No account was found with those details.'];
    }

    if (($user['role'] ?? '') !== $role) {
        return ['success' => false, 'message' => 'The selected role does not match this account.'];
    }

    if (($user['status'] ?? 'active') !== 'active') {
        return ['success' => false, 'message' => 'This account is currently not active. Please contact support.'];
    }

    if (!password_verify($password, (string) ($user['password_hash'] ?? ''))) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    $lastLoginAt = gmdate('Y-m-d H:i:s');

    if (bani_db_ready() && bani_db() instanceof PDO) {
        $updatedUser = $user;
        $updatedUser['last_login_at'] = $lastLoginAt;
        bani_upsert_db_user($updatedUser);
    } else {
        $users = bani_load_file_users();
        $index = bani_find_user_index($email, $users);
        if ($index !== null) {
            $users[$index]['last_login_at'] = $lastLoginAt;
            bani_save_file_users($users);
        }
    }

    $freshUser = bani_find_user($email) ?? $user;

    $_SESSION['bani_user'] = [
        'email' => $freshUser['email'],
        'role' => $freshUser['role'],
        'name' => $freshUser['name'],
        'phone' => $freshUser['phone'],
        'company' => $freshUser['company'],
        'status' => $freshUser['status'],
        'created_at' => $freshUser['created_at'],
        'last_login_at' => $freshUser['last_login_at'],
    ];

    return ['success' => true, 'message' => 'Login successful.'];
}

function bani_register_client(array $input): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $phone = trim((string) ($input['phone'] ?? ''));
    $company = trim((string) ($input['company'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    $confirmPassword = (string) ($input['confirm_password'] ?? '');

    if ($name === '' || $email === '' || $phone === '' || $company === '' || $password === '' || $confirmPassword === '') {
        return ['success' => false, 'message' => 'Please complete all registration fields.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please provide a valid email address.'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Passwords must be at least 8 characters long.'];
    }

    if ($password !== $confirmPassword) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }

    if (bani_find_user($email) !== null) {
        return ['success' => false, 'message' => 'An account with that email already exists.'];
    }

    $newUser = [
        'email' => $email,
        'name' => $name,
        'phone' => $phone,
        'company' => $company,
        'role' => 'client',
        'status' => 'active',
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => gmdate('Y-m-d H:i:s'),
        'last_login_at' => null,
    ];

    if (bani_db_ready() && bani_db() instanceof PDO) {
        bani_upsert_db_user($newUser);
    } else {
        $users = bani_load_file_users();
        $users[] = $newUser;
        bani_save_file_users($users);
    }

    return ['success' => true, 'message' => 'Your portal account has been created. You can now sign in.'];
}

function bani_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function bani_require_role(string $role): void
{
    $user = bani_current_user();

    if (!$user || ($user['role'] ?? '') !== $role) {
        header('Location: login.php?role=' . urlencode($role));
        exit;
    }
}

function bani_list_users(?string $role = null): array
{
    $users = bani_load_users();

    if ($role === null) {
        return $users;
    }

    return array_values(array_filter($users, static fn(array $user): bool => ($user['role'] ?? '') === $role));
}

function bani_user_counts(): array
{
    $counts = [
        'total' => 0,
        'client' => 0,
        'staff' => 0,
        'admin' => 0,
        'active' => 0,
        'suspended' => 0,
    ];

    foreach (bani_load_users() as $user) {
        $counts['total']++;

        $role = $user['role'] ?? '';
        $status = $user['status'] ?? 'active';

        if (isset($counts[$role])) {
            $counts[$role]++;
        }

        if (isset($counts[$status])) {
            $counts[$status]++;
        }
    }

    return $counts;
}

function bani_update_user_status(string $email, string $status): bool
{
    if (!in_array($status, BANI_ALLOWED_STATUSES, true)) {
        return false;
    }

    $user = bani_find_user($email);
    if ($user === null) {
        return false;
    }

    $user['status'] = $status;

    if (bani_db_ready() && bani_db() instanceof PDO) {
        return bani_upsert_db_user($user);
    }

    $users = bani_load_file_users();
    $index = bani_find_user_index($email, $users);

    if ($index === null) {
        return false;
    }

    $users[$index]['status'] = $status;
    bani_save_file_users($users);

    return true;
}

function bani_format_datetime(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return 'Not available';
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return 'Not available';
    }

    return date('d M Y, h:i A', $timestamp);
}

bani_ensure_storage();
