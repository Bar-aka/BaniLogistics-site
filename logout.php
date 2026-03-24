<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

bani_logout();
header('Location: login.php');
exit;
