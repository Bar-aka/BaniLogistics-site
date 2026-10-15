<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
$user = bani_require_roles(['client','staff','admin']);
$pdo = bani_db();
$id = (int) ($_GET['id'] ?? 0);
if (!$pdo || $id < 1) { http_response_code(404); exit('Document not found.'); }
$stmt = $pdo->prepare('SELECT client_email,assigned_to,document_filename,document_original_name FROM portal_supplier_orders WHERE id=?');
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) { http_response_code(404); exit('Document not found.'); }
$email = strtolower((string)($user['email'] ?? ''));
$role = (string)($user['role'] ?? '');
if ($role === 'client' && strtolower((string)$order['client_email']) !== $email) { http_response_code(404); exit('Document not found.'); }
if ($role === 'staff' && strtolower((string)$order['assigned_to']) !== $email) { http_response_code(404); exit('Document not found.'); }
$filename = (string)($order['document_filename'] ?? '');
if (!preg_match('/\A[a-f0-9]{48}\.(pdf|jpg|png|webp)\z/', $filename)) { http_response_code(404); exit('Document not found.'); }
$path = __DIR__ . '/storage/supplier-orders/' . $filename;
if (!is_file($path)) { http_response_code(404); exit('Document not found.'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!in_array($mime, ['application/pdf','image/jpeg','image/png','image/webp'], true)) { http_response_code(404); exit('Document not found.'); }
$original = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)($order['document_original_name'] ?? 'order-document'));
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($path));
header('Content-Disposition: attachment; filename="' . ($original ?: 'order-document') . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($path);
