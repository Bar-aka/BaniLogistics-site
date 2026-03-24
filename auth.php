<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

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
        bani_save_users(bani_seed_users());
    }
}

function bani_seed_users(): array
{
    $now = gmdate('c');

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

function bani_load_users(): array
{
    bani_ensure_storage();

    $json = file_get_contents(bani_users_path());
    $users = json_decode($json ?: '[]', true);

    return is_array($users) ? $users : [];
}

function bani_save_users(array $users): void
{
    $json = json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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

function bani_find_user(string $email): ?array
{
    $users = bani_load_users();
    $index = bani_find_user_index($email, $users);

    return $index === null ? null : $users[$index];
}

function bani_current_user(): ?array
{
    return $_SESSION['bani_user'] ?? null;
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
    $users = bani_load_users();
    $index = bani_find_user_index($email, $users);

    if ($index === null) {
        return ['success' => false, 'message' => 'No account was found with those details.'];
    }

    $user = $users[$index];

    if (($user['role'] ?? '') !== $role) {
        return ['success' => false, 'message' => 'The selected role does not match this account.'];
    }

    if (($user['status'] ?? 'active') !== 'active') {
        return ['success' => false, 'message' => 'This account is currently not active. Please contact support.'];
    }

    if (!password_verify($password, (string) ($user['password_hash'] ?? ''))) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    $users[$index]['last_login_at'] = gmdate('c');
    bani_save_users($users);

    $_SESSION['bani_user'] = [
        'email' => $user['email'],
        'role' => $user['role'],
        'name' => $user['name'],
        'phone' => $user['phone'] ?? '',
        'company' => $user['company'] ?? '',
        'status' => $user['status'] ?? 'active',
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

    $users = bani_load_users();
    if (bani_find_user_index($email, $users) !== null) {
        return ['success' => false, 'message' => 'An account with that email already exists.'];
    }

    $users[] = [
        'email' => $email,
        'name' => $name,
        'phone' => $phone,
        'company' => $company,
        'role' => 'client',
        'status' => 'active',
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => gmdate('c'),
        'last_login_at' => null,
    ];

    bani_save_users($users);

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

    $users = bani_load_users();
    $index = bani_find_user_index($email, $users);

    if ($index === null) {
        return false;
    }

    $users[$index]['status'] = $status;
    bani_save_users($users);

    return true;
}

bani_ensure_storage();
