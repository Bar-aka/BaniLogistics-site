<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$user = bani_require_roles(['client', 'staff', 'admin']);
$role = (string) ($user['role'] ?? '');
$email = strtolower((string) ($user['email'] ?? ''));
$name = (string) ($user['name'] ?? '');
$pdo = bani_db();
$error = '';
$success = '';
$schemaReady = false;

function so_h(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function so_install(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_delivery_addresses (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      label VARCHAR(120) NOT NULL, recipient VARCHAR(190) NOT NULL,
      address_line1 VARCHAR(255) NOT NULL, address_line2 VARCHAR(255) NULL,
      city VARCHAR(120) NOT NULL, region VARCHAR(120) NULL, postal_code VARCHAR(40) NULL,
      country VARCHAR(120) NOT NULL, phone VARCHAR(60) NULL, instructions TEXT NULL,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_delivery_addresses_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_supplier_orders (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      client_email VARCHAR(190) NOT NULL, client_name VARCHAR(190) NOT NULL,
      address_id INT UNSIGNED NULL, address_snapshot TEXT NOT NULL,
      retailer VARCHAR(190) NOT NULL, order_reference VARCHAR(190) NULL,
      supplier_tracking_number VARCHAR(190) NULL, item_description TEXT NOT NULL,
      document_filename VARCHAR(255) NULL, document_original_name VARCHAR(255) NULL,
      status VARCHAR(60) NOT NULL DEFAULT 'Submitted',
      assigned_to VARCHAR(190) NULL, assigned_name VARCHAR(190) NULL,
      customer_update TEXT NULL, internal_notes TEXT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_supplier_orders_client (client_email),
      INDEX idx_supplier_orders_assigned (assigned_to),
      INDEX idx_supplier_orders_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
try {
    if (!$pdo) throw new RuntimeException('Portal database is not configured.');
    so_install($pdo);
    $schemaReady = true;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!bani_verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Your session expired. Refresh the page and try again.');
        }
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'save-address' && $role === 'admin') {
            $id = (int) ($_POST['address_id'] ?? 0);
            $fields = [
                'label' => trim((string) ($_POST['label'] ?? '')),
                'recipient' => trim((string) ($_POST['recipient'] ?? '')),
                'address_line1' => trim((string) ($_POST['address_line1'] ?? '')),
                'address_line2' => trim((string) ($_POST['address_line2'] ?? '')),
                'city' => trim((string) ($_POST['city'] ?? '')),
                'region' => trim((string) ($_POST['region'] ?? '')),
                'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
                'country' => trim((string) ($_POST['country'] ?? '')),
                'phone' => trim((string) ($_POST['phone'] ?? '')),
                'instructions' => trim((string) ($_POST['instructions'] ?? '')),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ];
            if (in_array('', array_intersect_key($fields, array_flip(['label','recipient','address_line1','city','country'])), true)) {
                throw new RuntimeException('Complete the address label, recipient, street address, city, and country.');
            }
            if ($id > 0) {
                $fields['id'] = $id;
                $stmt = $pdo->prepare('UPDATE portal_delivery_addresses SET label=:label,recipient=:recipient,address_line1=:address_line1,address_line2=:address_line2,city=:city,region=:region,postal_code=:postal_code,country=:country,phone=:phone,instructions=:instructions,is_active=:is_active WHERE id=:id');
                $stmt->execute($fields);
                $success = 'Delivery address updated.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO portal_delivery_addresses (label,recipient,address_line1,address_line2,city,region,postal_code,country,phone,instructions,is_active) VALUES (:label,:recipient,:address_line1,:address_line2,:city,:region,:postal_code,:country,:phone,:instructions,:is_active)');
                $stmt->execute($fields);
                $success = 'Delivery address added.';
            }
        } elseif ($action === 'submit-order' && $role === 'client') {
            $addressId = (int) ($_POST['address_id'] ?? 0);
            $addressStmt = $pdo->prepare('SELECT * FROM portal_delivery_addresses WHERE id=? AND is_active=1');
            $addressStmt->execute([$addressId]);
            $address = $addressStmt->fetch(PDO::FETCH_ASSOC);
            if (!$address) throw new RuntimeException('Choose an available delivery address.');
            $retailer = trim((string) ($_POST['retailer'] ?? ''));
            $description = trim((string) ($_POST['item_description'] ?? ''));
            if ($retailer === '' || $description === '') throw new RuntimeException('Enter the store and a short item description.');
            if (strlen($retailer) > 190 || strlen($description) > 10000) throw new RuntimeException('Some order details are too long.');
            $documentName = null;
            $originalName = null;
            $file = $_FILES['order_document'] ?? null;
            if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 8 * 1024 * 1024) {
                    throw new RuntimeException('The document could not be uploaded or exceeds the 8 MB limit.');
                }
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
                $extensions = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                if (!isset($extensions[$mime])) throw new RuntimeException('Upload a PDF, JPG, PNG, or WEBP document.');
                $folder = __DIR__ . '/storage/supplier-orders';
                if (!is_dir($folder) && !mkdir($folder, 0750, true) && !is_dir($folder)) throw new RuntimeException('Secure document storage is unavailable.');
                $documentName = bin2hex(random_bytes(24)) . '.' . $extensions[$mime];
                if (!move_uploaded_file((string) $file['tmp_name'], $folder . '/' . $documentName)) throw new RuntimeException('Unable to save the uploaded document.');
                $originalName = substr(basename((string) ($file['name'] ?? 'order-document')), 0, 255);
            }
            $snapshot = implode("\n", array_filter([
                (string)$address['label'], (string)$address['recipient'], (string)$address['address_line1'],
                (string)$address['address_line2'], trim((string)$address['city'] . ', ' . (string)$address['region'] . ' ' . (string)$address['postal_code']),
                (string)$address['country'], (string)$address['phone'], (string)$address['instructions']
            ], static fn($v) => trim($v) !== ''));
            $stmt = $pdo->prepare('INSERT INTO portal_supplier_orders (client_email,client_name,address_id,address_snapshot,retailer,order_reference,supplier_tracking_number,item_description,document_filename,document_original_name) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$email, $name, $addressId, $snapshot, $retailer, trim((string)($_POST['order_reference'] ?? '')) ?: null, trim((string)($_POST['supplier_tracking_number'] ?? '')) ?: null, $description, $documentName, $originalName]);
            $success = 'Purchase details submitted to Bani operations.';
        } elseif ($action === 'update-customer-order' && $role === 'client') {
            $id = (int) ($_POST['order_id'] ?? 0);
            $check = $pdo->prepare('SELECT * FROM portal_supplier_orders WHERE id=? AND LOWER(client_email)=?');
            $check->execute([$id, $email]);
            $order = $check->fetch(PDO::FETCH_ASSOC);
            if (!$order) throw new RuntimeException('That purchase record is unavailable to your account.');
            $documentName = $order['document_filename'];
            $originalName = $order['document_original_name'];
            $file = $_FILES['order_document'] ?? null;
            if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int)($file['size'] ?? 0) > 8 * 1024 * 1024) {
                    throw new RuntimeException('The document could not be uploaded or exceeds the 8 MB limit.');
                }
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
                $extensions = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                if (!isset($extensions[$mime])) throw new RuntimeException('Upload a PDF, JPG, PNG, or WEBP document.');
                $folder = __DIR__ . '/storage/supplier-orders';
                if (!is_dir($folder) && !mkdir($folder, 0750, true) && !is_dir($folder)) throw new RuntimeException('Secure document storage is unavailable.');
                $documentName = bin2hex(random_bytes(24)) . '.' . $extensions[$mime];
                if (!move_uploaded_file((string)$file['tmp_name'], $folder . '/' . $documentName)) throw new RuntimeException('Unable to save the uploaded document.');
                $originalName = substr(basename((string)($file['name'] ?? 'order-document')), 0, 255);
            }
            $stmt = $pdo->prepare('UPDATE portal_supplier_orders SET order_reference=?,supplier_tracking_number=?,document_filename=?,document_original_name=? WHERE id=? AND LOWER(client_email)=?');
            $stmt->execute([
                trim((string)($_POST['order_reference'] ?? '')) ?: null,
                trim((string)($_POST['supplier_tracking_number'] ?? '')) ?: null,
                $documentName, $originalName, $id, $email
            ]);
            $success = 'Your purchase details have been updated for Bani operations.';
        } elseif ($action === 'update-order' && in_array($role, ['staff','admin'], true)) {
            $id = (int) ($_POST['order_id'] ?? 0);
            $check = $pdo->prepare('SELECT * FROM portal_supplier_orders WHERE id=?' . ($role === 'staff' ? ' AND LOWER(assigned_to)=?' : ''));
            $role === 'staff' ? $check->execute([$id, $email]) : $check->execute([$id]);
            $order = $check->fetch(PDO::FETCH_ASSOC);
            if (!$order) throw new RuntimeException('That purchase record is unavailable to your account.');
            $status = trim((string) ($_POST['status'] ?? 'Submitted'));
            $validStatuses = ['Submitted','Purchased','Awaiting supplier dispatch','In transit to warehouse','Received at warehouse','Prepared for forwarding','Converted to shipment'];
            if (!in_array($status, $validStatuses, true)) throw new RuntimeException('Choose a valid status.');
            $customerUpdate = trim((string) ($_POST['customer_update'] ?? ''));
            $internalNotes = trim((string) ($_POST['internal_notes'] ?? ''));
            $assignedTo = $order['assigned_to']; $assignedName = $order['assigned_name'];
            if ($role === 'admin') {
                $staffEmail = strtolower(trim((string) ($_POST['assigned_to'] ?? '')));
                if ($staffEmail === '') { $assignedTo = null; $assignedName = null; }
                else {
                    $staffStmt = $pdo->prepare("SELECT email,name FROM portal_users WHERE LOWER(email)=? AND role='staff' AND status='active' LIMIT 1");
                    $staffStmt->execute([$staffEmail]); $staffUser = $staffStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$staffUser) throw new RuntimeException('Select an active staff account.');
                    $assignedTo = $staffUser['email']; $assignedName = $staffUser['name'];
                }
            }
            $stmt = $pdo->prepare('UPDATE portal_supplier_orders SET status=?,customer_update=?,internal_notes=?,assigned_to=?,assigned_name=? WHERE id=?');
            $stmt->execute([$status,$customerUpdate ?: null,$internalNotes ?: null,$assignedTo,$assignedName,$id]);
            $success = 'Purchase record updated.';
        } else {
            throw new RuntimeException('This action is not available for your account.');
        }
    }
} catch (Throwable $exception) {
    $error = $exception instanceof PDOException ? 'The portal could not save this record. Please try again or contact operations.' : $exception->getMessage();
}
$addressStmt = ($pdo && $schemaReady) ? $pdo->query('SELECT * FROM portal_delivery_addresses ' . ($role === 'client' ? 'WHERE is_active=1 ' : '') . 'ORDER BY is_active DESC,label ASC') : false;
$addresses = $addressStmt ? $addressStmt->fetchAll(PDO::FETCH_ASSOC) : [];
if ($pdo && $schemaReady) {
    if ($role === 'client') { $stmt=$pdo->prepare('SELECT * FROM portal_supplier_orders WHERE LOWER(client_email)=? ORDER BY created_at DESC'); $stmt->execute([$email]); }
    elseif ($role === 'staff') { $stmt=$pdo->prepare('SELECT * FROM portal_supplier_orders WHERE LOWER(assigned_to)=? ORDER BY created_at DESC'); $stmt->execute([$email]); }
    else { $stmt=$pdo->query('SELECT * FROM portal_supplier_orders ORDER BY created_at DESC'); }
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else $orders = [];
$staffUsers = ($role === 'admin' && $pdo && $schemaReady) ? $pdo->query("SELECT email,name FROM portal_users WHERE role='staff' AND status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Delivery addresses and purchases | Bani Global Logistics</title><link rel="stylesheet" href="/css/style.css">
<style>
.so-wrap{max-width:1180px;margin:2rem auto;padding:0 1rem}.so-card{background:#fff;border:1px solid #dbe3ed;border-radius:16px;padding:1.25rem;margin:1rem 0;box-shadow:0 8px 25px #1231}.so-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem}.so-card label{display:block;font-weight:600;margin:.55rem 0}.so-card input,.so-card select,.so-card textarea{display:block;width:100%;padding:.7rem;border:1px solid #cbd5e1;border-radius:8px;margin-top:.3rem}.so-address{border:1px solid #dbe3ed;border-radius:12px;padding:1rem;margin:.75rem 0}.so-msg{padding:.8rem 1rem;border-radius:8px;background:#eaf4ff;margin:.75rem 0}.so-error{background:#fff0f0;color:#8a1c1c}.so-order{overflow-wrap:anywhere}.so-order details{margin-top:.8rem}.so-muted{color:#526276}
</style></head><body class="dashboard-page">
<header class="site-header"><div class="nav-wrap"><a class="brand" href="index.html" aria-label="Bani Global Logistics home"><img src="/logo.png" alt="Bani Global Logistics Limited Logo"></a><button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-navigation">Menu</button><nav id="site-navigation" class="site-nav" aria-label="Main navigation"><a href="index.html">Home</a><a href="client-dashboard.php">Portal dashboard</a><a class="active" href="supplier-orders.php">Delivery addresses &amp; purchases</a><a href="logout.php">Logout</a></nav></div></header>
<main class="so-wrap"><section class="dashboard-hero"><div><div class="eyebrow">Secure portal · <?= so_h(ucfirst($role)) ?></div><h1>Delivery addresses &amp; purchases</h1><p>Use a listed Bani receiving address when ordering from an online store. After checkout, share the order reference, supplier tracking number, and invoice or order confirmation here.</p></div></section>
<?php if($success!==''):?><div class="so-msg"><?=so_h($success)?></div><?php endif;?><?php if($error!==''):?><div class="so-msg so-error"><?=so_h($error)?></div><?php endif;?>
<section class="so-card"><h2>Available delivery addresses</h2><p class="so-muted">Choose one of these addresses at checkout. Addresses will appear here as operations adds them.</p>
<?php if($addresses===[]):?><p>No delivery addresses have been added yet. Please check back or contact Bani operations.</p><?php endif;?>
<?php foreach($addresses as $a):?><article class="so-address"><h3><?=so_h($a['label'])?><?php if(!(int)$a['is_active']):?> <span class="so-muted">(inactive)</span><?php endif;?></h3><p><strong><?=so_h($a['recipient'])?></strong><br><?=nl2br(so_h(implode("\n",array_filter([$a['address_line1'],$a['address_line2'],$a['city'],trim((string)$a['region'].' '.(string)$a['postal_code']),$a['country'],$a['phone']]))))?></p><?php if(trim((string)$a['instructions'])!==''):?><p><strong>Instructions:</strong> <?=nl2br(so_h($a['instructions']))?></p><?php endif;?></article><?php endforeach;?>
</section>
<?php if($role==='admin'):?><section class="so-card"><h2>Manage delivery address list</h2><p>Add each receiving location once; clients will only see active entries.</p><form method="post"><input type="hidden" name="csrf_token" value="<?=so_h(bani_csrf_token())?>"><input type="hidden" name="action" value="save-address"><div class="so-grid"><label>Address label<input name="label" required placeholder="e.g. USA warehouse"></label><label>Recipient / company<input name="recipient" required></label><label>Address line 1<input name="address_line1" required></label><label>Address line 2<input name="address_line2"></label><label>City<input name="city" required></label><label>State / region<input name="region"></label><label>ZIP / postal code<input name="postal_code"></label><label>Country<input name="country" required></label><label>Phone<input name="phone"></label></div><label>Delivery instructions<textarea name="instructions"></textarea></label><label><input type="checkbox" name="is_active" checked> Show this address to customers</label><button class="button primary" type="submit">Add address</button></form>
<?php foreach($addresses as $a):?><details><summary>Edit <?=so_h($a['label'])?></summary><form method="post"><input type="hidden" name="csrf_token" value="<?=so_h(bani_csrf_token())?>"><input type="hidden" name="action" value="save-address"><input type="hidden" name="address_id" value="<?=(int)$a['id']?>"><div class="so-grid"><?php foreach(['label'=>'Address label','recipient'=>'Recipient','address_line1'=>'Address line 1','address_line2'=>'Address line 2','city'=>'City','region'=>'State / region','postal_code'=>'ZIP / postal code','country'=>'Country','phone'=>'Phone'] as $field=>$label):?><label><?=so_h($label)?><input name="<?=so_h($field)?>" value="<?=so_h($a[$field])?>" <?=in_array($field,['label','recipient','address_line1','city','country'],true)?'required':''?>></label><?php endforeach;?></div><label>Delivery instructions<textarea name="instructions"><?=so_h($a['instructions'])?></textarea></label><label><input type="checkbox" name="is_active" <?=((int)$a['is_active']?'checked':'')?>> Show this address to customers</label><button class="button secondary" type="submit">Save address</button></form></details><?php endforeach;?></section><?php endif;?>
<?php if($role==='client' && $addresses!==[]):?><section class="so-card"><h2>Tell Bani about a purchase</h2><p>Your order is still placed on the retailer's website. This form lets our team identify and receive it.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=so_h(bani_csrf_token())?>"><input type="hidden" name="action" value="submit-order"><div class="so-grid"><label>Delivery address<select name="address_id" required><option value="">Choose an address</option><?php foreach($addresses as $a):?><option value="<?=(int)$a['id']?>"><?=so_h($a['label'])?> — <?=so_h($a['country'])?></option><?php endforeach;?></select></label><label>Store / retailer<input name="retailer" required placeholder="Amazon, eBay, supplier site"></label><label>Order reference<input name="order_reference" placeholder="Retailer order number"></label><label>Supplier tracking number<input name="supplier_tracking_number" placeholder="Add now or update later"></label></div><label>Items purchased<textarea name="item_description" required placeholder="Item names, quantities, and any useful package details"></textarea></label><label>Invoice or order confirmation (PDF/JPG/PNG/WEBP, max 8 MB)<input type="file" name="order_document" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"></label><button class="button primary" type="submit">Submit purchase details</button></form></section><?php endif;?>
<section class="so-card"><h2><?= $role==='client'?'My submitted purchases':($role==='staff'?'Purchases assigned to me':'Customer purchase records')?></h2>
<?php if($orders===[]):?><p>No purchase records to show yet.</p><?php endif;?>
<?php foreach($orders as $o):?><article class="so-address so-order"><h3><?=so_h($o['retailer'])?> · <?=so_h($o['status'])?></h3><p><strong>Client:</strong> <?=so_h($o['client_name'])?> (<?=so_h($o['client_email'])?>)<br><strong>Order:</strong> <?=so_h($o['order_reference']?:'Not provided')?> · <strong>Supplier tracking:</strong> <?=so_h($o['supplier_tracking_number']?:'Not provided')?><br><strong>Delivery address:</strong><br><?=nl2br(so_h($o['address_snapshot']))?><br><strong>Items:</strong> <?=nl2br(so_h($o['item_description']))?><br><strong>Submitted:</strong> <?=so_h($o['created_at'])?><?php if(!empty($o['assigned_name'])):?><br><strong>Assigned to:</strong> <?=so_h($o['assigned_name'])?><?php endif;?></p><?php if(!empty($o['document_filename'])):?><p><a href="supplier-order-document.php?id=<?=(int)$o['id']?>">View attached order document</a></p><?php endif;?><?php if(trim((string)$o['customer_update'])!==''):?><p><strong>Update:</strong> <?=nl2br(so_h($o['customer_update']))?></p><?php endif;?>
<?php if($role==='client'):?><details><summary>Add or update tracking number / invoice</summary><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=so_h(bani_csrf_token())?>"><input type="hidden" name="action" value="update-customer-order"><input type="hidden" name="order_id" value="<?=(int)$o['id']?>"><div class="so-grid"><label>Retailer order reference<input name="order_reference" value="<?=so_h($o['order_reference'])?>"></label><label>Supplier tracking number<input name="supplier_tracking_number" value="<?=so_h($o['supplier_tracking_number'])?>"></label></div><label>Invoice or order confirmation (PDF/JPG/PNG/WEBP, max 8 MB)<input type="file" name="order_document" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"></label><button class="button secondary" type="submit">Update purchase details</button></form></details><?php endif;?><?php if(in_array($role,['staff','admin'],true)):?><details><summary>Update record</summary><form method="post"><input type="hidden" name="csrf_token" value="<?=so_h(bani_csrf_token())?>"><input type="hidden" name="action" value="update-order"><input type="hidden" name="order_id" value="<?=(int)$o['id']?>"><label>Status<select name="status"><?php foreach(['Submitted','Purchased','Awaiting supplier dispatch','In transit to warehouse','Received at warehouse','Prepared for forwarding','Converted to shipment'] as $s):?><option <?=($o['status']===$s?'selected':'')?>><?=so_h($s)?></option><?php endforeach;?></select></label><label>Customer-visible update<textarea name="customer_update"><?=so_h($o['customer_update'])?></textarea></label><label>Internal staff notes<textarea name="internal_notes"><?=so_h($o['internal_notes'])?></textarea></label><?php if($role==='admin'):?><label>Assign to staff<select name="assigned_to"><option value="">Unassigned</option><?php foreach($staffUsers as $staff):?><option value="<?=so_h($staff['email'])?>" <?=strtolower((string)$o['assigned_to'])===strtolower((string)$staff['email'])?'selected':''?>><?=so_h($staff['name'])?> — <?=so_h($staff['email'])?></option><?php endforeach;?></select></label><?php endif;?><button class="button secondary" type="submit">Save update</button></form></details><?php if(trim((string)$o['internal_notes'])!==''):?><p><strong>Internal notes:</strong> <?=nl2br(so_h($o['internal_notes']))?></p><?php endif;?><?php endif;?></article><?php endforeach;?>
</section></main><script src="/script.js" defer></script></body></html>
