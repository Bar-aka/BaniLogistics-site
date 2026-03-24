<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const BANI_AUTH_USERS = [
    'client@banilogistics.co.ke' => [
        'role' => 'client',
        'password_hash' => 'c3f9c2dc766f0396bc9ef7e63f0f41cfdf2bc7f80cb013f6ce579ea8f898df78',
        'name' => 'Client Account',
    ],
    'ops@banilogistics.co.ke' => [
        'role' => 'staff',
        'password_hash' => '7b5dbf240f66c0de8808f8e7f1612efff263d2a8f9f0534d5f4136fcd6ee57c5',
        'name' => 'Operations Team',
    ],
    'admin@banilogistics.co.ke' => [
        'role' => 'admin',
        'password_hash' => 'ff0b7e42451f4d29e83028531cc0f55ec7cc6f35fae446fb4a0ae837ec4d4435',
        'name' => 'Admin Account',
    ],
];

function bani_password_hash(string $password): string
{
    return hash('sha256', 'bani-secure-salt-v1|' . $password);
}

function bani_current_user(): ?array
{
    return $_SESSION['bani_user'] ?? null;
}

function bani_login(string $email, string $password, string $role): bool
{
    $email = strtolower(trim($email));

    if (!isset(BANI_AUTH_USERS[$email])) {
        return false;
    }

    $user = BANI_AUTH_USERS[$email];

    if ($user['role'] !== $role) {
        return false;
    }

    if (!hash_equals($user['password_hash'], bani_password_hash($password))) {
        return false;
    }

    $_SESSION['bani_user'] = [
        'email' => $email,
        'role' => $user['role'],
        'name' => $user['name'],
    ];

    return true;
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

function bani_dashboard_url(string $role): string
{
    return match ($role) {
        'client' => 'client-dashboard.php',
        'staff' => 'staff-dashboard.php',
        'admin' => 'admin-dashboard.php',
        default => 'login.php',
    };
}
