<?php
// ===== ZAS Canteen API — built exactly on the database.sql schema (no DB changes) =====
// Response field names match the frontend pages (is_active, qty, token, emoji, …) via SQL aliases.
require_once __DIR__ . '/lib.php';

$a = $_GET['a'] ?? '';
$READONLY = ['stats','users_list','kitchens_list','vendors_list','items_list','menu_list','orders_list',
  'inventory_list','salesstock_list','wallets_list','report','audit_list','chef_stock','sales_today',
  'queue','customer_menu','my_orders','wallet_balance','whoami','comp_list','dish_photo_list'];
if (!in_array($a, $READONLY, true) && $a !== 'login') check_csrf();
// Read-only actions never write to the session — release the session lock early so
// concurrent requests from the same user (menu + orders + polling) don't serialize.
if (in_array($a, $READONLY, true) && $a !== 'whoami' && !empty($_SESSION['csrf'])) session_write_close();

switch ($a) {

// ---------- Auth ----------
case 'login': {
  $b = body();
  if (($_SESSION['login_lock'] ?? 0) > time()) fail('Too many attempts — try again in 5 minutes', 429);
  $u = attempt_login(trim($b['username'] ?? ''), $b['password'] ?? '');
  if (!$u) {
    $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
    if ($_SESSION['login_fails'] >= 8) { $_SESSION['login_lock'] = time() + 300; $_SESSION['login_fails'] = 0; }
    usleep(400000);   // slow brute-force attempts
    fail('Invalid username or password', 401);
  }
  unset($_SESSION['login_fails'], $_SESSION['login_lock']);
  json_out(['ok'=>true,'role'=>$u['role'],'name'=>$u['name'],'csrf'=>csrf_token()]);
}
case 'logout': { require_login(); session_destroy(); json_out(['ok'=>true]); }
case 'whoami': {
  $u = require_login();
  json_out(['ok'=>true,'id'=>(int)$u['id'],'name'=>$u['name'],'username'=>$u['username'],'role'=>$u['role'],
            'kitchen_id'=>$u['kitchen_id']?(int)$u['kitchen_id']:null,'csrf'=>csrf_token()]);
}
case 'my_account': {
  $u = require_login();
  $b = body();
  $name = trim($b['name'] ?? $u['name']); $username = trim($b['username'] ?? $u['username']);
  if ($name===''||$username==='') fail('Name and username required');
  $st = db()->prepare('SELECT id FROM users WHERE username=? AND id<>?'); $st->execute([$username,$u['id']]);
  if ($st->fetch()) fail('Username already taken');
  if (!empty($b['new_password'])) {
    if (strlen($b['new_password'])<6) fail('Password must be at least 6 characters');
    db()->prepare('UPDATE users SET name=?,username=?,password=? WHERE id=?')
      ->execute([$name,$username,password_hash($b['new_password'],PASSWORD_BCRYPT),$u['id']]);
  } else {
    db()->prepare('UPDATE users SET name=?,username=? WHERE id=?')->execute([$name,$username,$u['id']]);
  }
  audit((int)$u['id'],$u['name'],'Update own account','users',(int)$u['id'],null);
  json_out(['ok'=>true]);
}

// ---------- Admin dashboard ----------
case 'stats': {
  require_role('admin');
  $d = db();
  $pending = (int)$d->query("SELECT COUNT(*) FROM orders WHERE status IN ('Pending','Preparing')")->fetchColumn();
  $sales   = (float)$d->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('Pending','Preparing','Delivered') AND DATE(created_at)=CURDATE()")->fetchColumn();
  $cust    = (int)$d->query("SELECT COUNT(*) FROM users WHERE role='customer' AND status='active'")->fetchColumn();
  $menu    = (int)$d->query("SELECT COUNT(*) FROM menu_items WHERE status='active'")->fetchColumn();
  $spark   = $d->query("SELECT DATE(created_at) d, COALESCE(SUM(total),0) t FROM orders
                        WHERE status<>'Cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
                        GROUP BY DATE(created_at) ORDER BY d")->fetchAll();
  json_out(['ok'=>true,'pending'=>$pending,'sales_today'=>$sales,'customers'=>$cust,'menu_items'=>$menu,'spark'=>$spark]);
}

// ---------- Users ----------
case 'users_list': {
  require_role('admin');
  $role = $_GET['role'] ?? '';
  $sql = "SELECT u.id,u.employee_id,u.name,NULL AS name_urdu,u.username,u.role,u.kitchen_id,u.department,u.mobile,u.menu_access,
          (u.status='active') AS is_active,k.name kitchen
          FROM users u LEFT JOIN kitchens k ON k.id=u.kitchen_id";
  if ($role) { $st=db()->prepare($sql." WHERE u.role=? ORDER BY u.name"); $st->execute([$role]); }
  else $st = db()->query($sql." ORDER BY u.role,u.name");
  json_out(['ok'=>true,'rows'=>$st->fetchAll()]);
}
case 'user_save': {
  $me = require_role('admin');
  $b = body();
  $id=(int)($b['id']??0); $name=trim($b['name']??''); $username=trim($b['username']??'');
  $role=$b['role']??'customer';
  if ($name===''||$username==='') fail('Name and username required');
  if (!in_array($role,['admin','accounts','chef','sales','customer'],true)) fail('Bad role');
  $kitchen = in_array($role,['chef','sales'],true) && !empty($b['kitchen_id']) ? (int)$b['kitchen_id'] : null;
  $status = ((int)($b['is_active'] ?? 1)) ? 'active' : 'inactive';
  $macc = $b['menu_access'] ?? '';
  // preserve the admin's EXACT selection order; never auto-add 'standard'
  $parts = array_values(array_intersect(array_filter(array_map('trim', explode(',', (string)$macc))), ['standard','special','executive']));
  if ($role === 'customer' && !$parts) fail('Select at least one menu access type (Standard, Special or Executive)');
  $macc = $parts ? implode(',', $parts) : 'standard';   // staff roles: column unused, keep harmless default
  $st=db()->prepare('SELECT id FROM users WHERE username=? AND id<>?'); $st->execute([$username,$id]);
  if ($st->fetch()) fail('Username already taken');
  if ($id) {
    db()->prepare('UPDATE users SET name=?,username=?,role=?,kitchen_id=?,status=?,menu_access=? WHERE id=?')
      ->execute([$name,$username,$role,$kitchen,$status,$macc,$id]);
    if (!empty($b['new_password']))
      db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($b['new_password'],PASSWORD_BCRYPT),$id]);
    audit((int)$me['id'],$me['name'],'Update user','users',$id,null);
  } else {
    $pw = $b['new_password'] ?: 'canteen123';
    db()->prepare('INSERT INTO users (name,username,password,role,kitchen_id,status,menu_access) VALUES (?,?,?,?,?,?,?)')
      ->execute([$name,$username,password_hash($pw,PASSWORD_BCRYPT),$role,$kitchen,$status,$macc]);
    $id=(int)db()->lastInsertId();
    audit((int)$me['id'],$me['name'],'Create user','users',$id,null);
  }
  cache_bump('users'); // role/status/menu_access may have changed — current_user() cache for this id must not linger stale
  json_out(['ok'=>true,'id'=>$id]);
}
case 'user_delete': {
  $me = require_role('admin');
  $id = (int)(body()['id'] ?? 0);
  if ($id === (int)$me['id']) fail('Cannot delete yourself');
  $has = (int)db()->query("SELECT (SELECT COUNT(*) FROM orders WHERE customer_id=$id)
        + (SELECT COUNT(*) FROM wallet_topups WHERE customer_id=$id)
        + (SELECT COUNT(*) FROM purchases WHERE created_by=$id)
        + (SELECT COUNT(*) FROM consumption WHERE recorded_by=$id)")->fetchColumn();
  if ($has) {
    db()->prepare("UPDATE users SET status='inactive' WHERE id=?")->execute([$id]);
    audit((int)$me['id'],$me['name'],'Disable user (has history)','users',$id,null);
    cache_bump('users');
    json_out(['ok'=>true,'disabled'=>true,'msg'=>'User has order/wallet history — disabled instead of deleted (records preserved)']);
  }
  db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
  audit((int)$me['id'],$me['name'],'Delete user','users',$id,null);
  cache_bump('users');
  json_out(['ok'=>true,'deleted'=>true]);
}

// ---------- Kitchens / vendors / purchase item master / menu ----------
case 'kitchens_list': {
  require_role('admin','accounts');
  $rows = db()->query("SELECT id,name,IF(type='sales','sales','chef') AS type,(status='active') AS is_active
                       FROM kitchens ORDER BY type,name")->fetchAll();
  json_out(['ok'=>true,'rows'=>$rows]);
}
case 'kitchen_save': {
  $me = require_role('admin');
  $b=body(); $id=(int)($b['id']??0); $name=trim($b['name']??'');
  $type = ($b['type']??'chef')==='sales' ? 'sales' : 'kitchen';
  $status = ((int)($b['is_active']??1)) ? 'active' : 'inactive';
  if ($name==='') fail('Name required');
  if ($id) db()->prepare('UPDATE kitchens SET name=?,type=?,status=? WHERE id=?')->execute([$name,$type,$status,$id]);
  else { db()->prepare('INSERT INTO kitchens (name,type,status) VALUES (?,?,?)')->execute([$name,$type,$status]); $id=(int)db()->lastInsertId(); }
  audit((int)$me['id'],$me['name'],'Save kitchen','kitchens',$id,null);
  json_out(['ok'=>true,'id'=>$id]);
}
case 'vendors_list': {
  require_role('admin','accounts');
  $rows = db()->query("SELECT id,name,mobile AS phone,(status='active') AS is_active FROM vendors ORDER BY name")->fetchAll();
  json_out(['ok'=>true,'rows'=>$rows]);
}
case 'vendor_save': {
  $me = require_role('admin');
  $b=body(); $id=(int)($b['id']??0); $name=trim($b['name']??'');
  $status = ((int)($b['is_active']??1)) ? 'active' : 'inactive';
  if ($name==='') fail('Name required');
  if ($id) db()->prepare('UPDATE vendors SET name=?,mobile=?,status=? WHERE id=?')->execute([$name,$b['phone']??null,$status,$id]);
  else { db()->prepare('INSERT INTO vendors (name,mobile,status) VALUES (?,?,?)')->execute([$name,$b['phone']??null,$status]); $id=(int)db()->lastInsertId(); }
  audit((int)$me['id'],$me['name'],'Save vendor','vendors',$id,null);
  json_out(['ok'=>true,'id'=>$id]);
}
case 'items_list': {
  require_role('admin','accounts');
  $rows = db()->query("SELECT id,name,unit,(status='active') AS is_active FROM purchase_items ORDER BY name")->fetchAll();
  json_out(['ok'=>true,'rows'=>$rows]);
}
case 'item_save': {
  $me = require_role('admin');
  $b=body(); $id=(int)($b['id']??0); $name=trim($b['name']??''); $unit=trim($b['unit']??'');
  $status = ((int)($b['is_active']??1)) ? 'active' : 'inactive';
  if ($name===''||$unit==='') fail('Name and unit required');
  if ($id) db()->prepare('UPDATE purchase_items SET name=?,unit=?,status=? WHERE id=?')->execute([$name,$unit,$status,$id]);
  else { db()->prepare('INSERT INTO purchase_items (name,unit,status) VALUES (?,?,?)')->execute([$name,$unit,$status]); $id=(int)db()->lastInsertId(); }
  audit((int)$me['id'],$me['name'],'Save purchase item','purchase_items',$id,null);
  json_out(['ok'=>true,'id'=>$id]);
}
case 'item_delete': {
  $me = require_role('admin');
  $id = (int)(body()['id'] ?? 0);
  $nm = db()->prepare('SELECT name FROM purchase_items WHERE id=?'); $nm->execute([$id]);
  $name = $nm->fetchColumn();
  $st = db()->prepare("SELECT (SELECT COUNT(*) FROM purchases WHERE purchase_item_id=$id)
        + (SELECT COUNT(*) FROM inventory WHERE item_name=? AND quantity>0) c");
  $st->execute([$name]); $has=(int)$st->fetchColumn();
  if ($has) {
    db()->prepare("UPDATE purchase_items SET status='inactive' WHERE id=?")->execute([$id]);
    json_out(['ok'=>true,'disabled'=>true,'msg'=>'Item has purchase/stock history — disabled instead of deleted']);
  }
  db()->prepare('DELETE FROM purchase_items WHERE id=?')->execute([$id]);
  audit((int)$me['id'],$me['name'],'Delete purchase item','purchase_items',$id,null);
  json_out(['ok'=>true,'deleted'=>true]);
}
case 'menu_list': {
  require_role('admin','accounts');
  $rows = cache_remember('menulist:v' . cache_version('menu'), 20, function () {
    return db()->query("SELECT m.id,m.name,NULL AS name_urdu,m.category,m.price,m.menu_group,c.icon AS emoji,(m.status='active') AS is_active
                       FROM menu_items m LEFT JOIN categories c ON c.name=m.category
                       ORDER BY m.category,m.name")->fetchAll();
  });
  json_out(['ok'=>true,'rows'=>$rows]);
}
case 'menu_save': {
  $me = require_role('admin');
  $b=body(); $id=(int)($b['id']??0); $name=trim($b['name']??'');
  $price=(float)($b['price']??0); $cat=trim($b['category']??'');
  $status = ((int)($b['is_active']??1)) ? 'active' : 'inactive';
  $grp = $b['menu_group'] ?? 'standard';
  if (!in_array($grp,['standard','special','executive'],true)) $grp='standard';
  if ($name===''||$cat===''||$price<=0) fail('Name, category and price required');
  if ($id) db()->prepare('UPDATE menu_items SET name=?,category=?,price=?,status=?,menu_group=? WHERE id=?')->execute([$name,$cat,$price,$status,$grp,$id]);
  else { db()->prepare('INSERT INTO menu_items (name,category,price,status,menu_group) VALUES (?,?,?,?,?)')->execute([$name,$cat,$price,$status,$grp]); $id=(int)db()->lastInsertId(); }
  audit((int)$me['id'],$me['name'],'Save menu item','menu_items',$id,null);
  cache_bump('menu'); // name/price/status/group feeds both menu_list AND customer_menu
  json_out(['ok'=>true,'id'=>$id]);
}
case 'menu_delete': {
  $me = require_role('admin');
  $id = (int)(body()['id'] ?? 0);
  $has = (int)db()->query("SELECT COUNT(*) FROM order_items WHERE menu_item_id=$id")->fetchColumn();
  if ($has) {
    db()->prepare("UPDATE menu_items SET status='inactive' WHERE id=?")->execute([$id]);
    cache_bump('menu');
    json_out(['ok'=>true,'disabled'=>true,'msg'=>'Item appears in past orders — disabled instead of deleted']);
  }
  db()->prepare('DELETE FROM inventory WHERE menu_item_id=?')->execute([$id]);
  db()->prepare('DELETE FROM menu_items WHERE id=?')->execute([$id]);
  audit((int)$me['id'],$me['name'],'Delete menu item','menu_items',$id,null);
  cache_bump('menu');
  json_out(['ok'=>true,'deleted'=>true]);
}

// ---------- Purchase (Accounts) → auto stock into kitchen inventory ----------
case 'purchase_save': {
  $me = require_role('accounts','admin');
  $b = $_POST ?: body();
  $kitchen=(int)($b['kitchen_id']??0); $item=(int)($b['item_id']??0); $vendor=(int)($b['vendor_id']??0);
  $qty=(float)($b['qty']??0); $rate=(float)($b['rate']??0);
  $ptypeIn = strtolower($b['payment_type']??'cash');
  $ptype = ['cash'=>'Cash','bank'=>'Bank','credit'=>'Credit'][$ptypeIn] ?? 'Cash';
  if (!$kitchen||!$item||!$vendor||$qty<=0||$rate<=0) fail('All purchase fields are required');
  $k = db()->prepare("SELECT * FROM kitchens WHERE id=? AND type='kitchen' AND status='active'"); $k->execute([$kitchen]);
  if (!$k->fetch()) fail('Kitchen not found or disabled');
  $pi = db()->prepare("SELECT * FROM purchase_items WHERE id=? AND status='active'"); $pi->execute([$item]);
  $pitem = $pi->fetch(); if (!$pitem) fail('Purchase item not found');
  $vd = db()->prepare('SELECT * FROM vendors WHERE id=?'); $vd->execute([$vendor]);
  $ven = $vd->fetch(); if (!$ven) fail('Vendor not found');
  $billImage = null;
  if (!empty($_FILES['bill_image']['tmp_name'])) {
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $ext = strtolower(pathinfo($_FILES['bill_image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext,['jpg','jpeg','png','webp','pdf'],true)) fail('Bill image must be jpg/png/webp/pdf');
    $billImage = 'uploads/bill_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    move_uploaded_file($_FILES['bill_image']['tmp_name'], __DIR__ . '/' . $billImage);
  }
  $d = db(); $d->beginTransaction();
  try {
    $d->prepare('INSERT INTO purchases (kitchen_id,purchase_date,purchase_item_id,item_name,quantity,unit,vendor,vendor_id,vendor_name,rate,total_amount,bill_number,payment_type,bill_image,remarks,created_by)
                 VALUES (?,CURDATE(),?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
      ->execute([$kitchen,$item,$pitem['name'],$qty,$pitem['unit'],$ven['name'],$vendor,$ven['name'],
                 $rate,$qty*$rate,$b['bill_number']??null,$ptype,$billImage,$b['remarks']??null,(int)$me['id']]);
    $pid = (int)$d->lastInsertId();
    // upsert raw-material inventory (menu_item_id NULL = raw)
    $st = $d->prepare('SELECT id FROM inventory WHERE kitchen_id=? AND item_name=? AND menu_item_id IS NULL FOR UPDATE');
    $st->execute([$kitchen,$pitem['name']]);
    $iid = $st->fetchColumn();
    if ($iid) $d->prepare('UPDATE inventory SET quantity=quantity+?,unit=? WHERE id=?')->execute([$qty,$pitem['unit'],$iid]);
    else $d->prepare('INSERT INTO inventory (kitchen_id,item_name,quantity,unit) VALUES (?,?,?,?)')->execute([$kitchen,$pitem['name'],$qty,$pitem['unit']]);
    $d->commit();
    cache_bump('inventory');
    audit((int)$me['id'],$me['name'],'Create purchase','purchases',$pid,null);
    json_out(['ok'=>true,'id'=>$pid,'total'=>$qty*$rate]);
  } catch (Throwable $ex) { if($d->inTransaction())$d->rollBack(); fail('Purchase failed: '.$ex->getMessage(),500); }
}

// ---------- Sales allocation ----------
case 'allocate': {
  $me = require_role('accounts','admin');
  $b=body(); $menu=(int)($b['menu_item_id']??0); $qty=(int)($b['qty']??0);
  $kitchen=(int)($b['kitchen_id']??0) ?: sales_kitchen_id();
  if (!$menu||$qty===0) fail('Menu item and a non-zero quantity required');
  if (!$kitchen) fail('No active sales kitchen');
  $m = db()->prepare("SELECT * FROM menu_items WHERE id=? AND status='active'"); $m->execute([$menu]);
  $mi = $m->fetch(); if (!$mi) fail('Menu item not found or disabled');
  $d = db(); $d->beginTransaction();
  try {
    $st = $d->prepare('SELECT id,quantity FROM inventory WHERE kitchen_id=? AND menu_item_id=? FOR UPDATE');
    $st->execute([$kitchen,$menu]);
    $row = $st->fetch();
    $cur = $row ? (int)$row['quantity'] : 0;
    if ($qty < 0 && $cur + $qty < 0) { $d->rollBack(); fail('Cannot reduce below current stock ('.$cur.' available)'); }
    if ($row) $d->prepare('UPDATE inventory SET quantity=quantity+? WHERE id=?')->execute([$qty,(int)$row['id']]);
    else $d->prepare("INSERT INTO inventory (kitchen_id,menu_item_id,item_name,quantity,unit) VALUES (?,?,?,?,'Pcs')")
        ->execute([$kitchen,$menu,$mi['name'],$qty]);
    $d->prepare("INSERT INTO inv_allocations (kitchen_id,item_name,quantity,unit,allocated_by) VALUES (?,?,?,'Pcs',?)")
      ->execute([$kitchen,$mi['name'],$qty,(int)$me['id']]);
    $d->commit();
    cache_bump('inventory');
    audit((int)$me['id'],$me['name'],'Sales stock '.($qty<0?'reduce':'add').': '.$mi['name'].' '.($qty<0?'':'+').$qty,'inventory',$kitchen,null);
    json_out(['ok'=>true]);
  } catch (Throwable $ex) { if($d->inTransaction())$d->rollBack(); fail('Allocation failed: '.$ex->getMessage(),500); }
}
case 'salesstock_list': {
  require_role('admin','accounts');
  $rows = db()->query("SELECT i.id,i.kitchen_id,i.menu_item_id,i.quantity AS qty,m.name,NULL AS name_urdu,c.icon AS emoji,k.name kitchen
                       FROM inventory i JOIN menu_items m ON m.id=i.menu_item_id
                       LEFT JOIN categories c ON c.name=m.category
                       JOIN kitchens k ON k.id=i.kitchen_id
                       WHERE i.menu_item_id IS NOT NULL ORDER BY m.name")->fetchAll();
  json_out(['ok'=>true,'rows'=>$rows]);
}

// ---------- Wallet ----------
case 'topup': {
  $me = require_role('accounts','admin');
  $b=body(); $cust=(int)($b['customer_id']??0); $amt=(float)($b['amount']??0);
  if (!$cust||$amt==0.0) fail('Customer and amount required');
  if ($amt < 0 && $me['role'] !== 'admin') fail('Only admin can deduct');
  $c = db()->prepare("SELECT id,name FROM users WHERE id=? AND role='customer' AND status='active'"); $c->execute([$cust]);
  if (!$c->fetch()) fail('Customer not found');
  $d = db(); $d->beginTransaction();
  try {
    $d->prepare('UPDATE users SET wallet=wallet+? WHERE id=?')->execute([$amt,$cust]);
    $d->prepare('INSERT INTO wallet_topups (customer_id,amount,type,note,created_by) VALUES (?,?,?,?,?)')
      ->execute([$cust,abs($amt),$amt>0?'topup':'deduct',$b['remarks']??null,(int)$me['id']]);
    $d->commit();
    audit((int)$me['id'],$me['name'],($amt>0?'Wallet top-up ':'Wallet deduct ').CURRENCY.' '.abs($amt),'users',$cust,null);
    json_out(['ok'=>true,'balance'=>wallet_balance($cust)]);
  } catch (Throwable $ex) { if($d->inTransaction())$d->rollBack(); fail('Failed: '.$ex->getMessage(),500); }
}
case 'wallets_list': {
  require_role('admin','accounts');
  $rows = db()->query("SELECT id,name,NULL AS name_urdu,username,wallet AS balance FROM users
                       WHERE role='customer' AND status='active' ORDER BY name")->fetchAll();
  json_out(['ok'=>true,'rows'=>$rows]);
}

// ---------- Inventory (raw materials) ----------
case 'inventory_list': {
  $u = require_role('admin','chef');
  $kitchen = $u['role']==='chef' ? (int)$u['kitchen_id'] : (int)($_GET['kitchen_id'] ?? 0);
  $rows = cache_remember("invlist:v" . cache_version('inventory') . ":$kitchen", 15, function () use ($kitchen) {
    $sql = "SELECT i.id,i.kitchen_id,i.id AS item_id,i.item_name AS name,i.quantity AS qty,i.unit,k.name kitchen
            FROM inventory i JOIN kitchens k ON k.id=i.kitchen_id WHERE i.menu_item_id IS NULL";
    if ($kitchen) { $st=db()->prepare($sql." AND i.kitchen_id=? ORDER BY i.item_name"); $st->execute([$kitchen]); }
    else $st = db()->query($sql." ORDER BY k.name,i.item_name");
    return $st->fetchAll();
  });
  json_out(['ok'=>true,'rows'=>$rows]);
}
case 'inventory_adjust': {
  $me = require_role('admin');
  $b=body(); $iid=(int)($b['item_id']??0); $qty=(float)($b['qty']??-1);
  if (!$iid||$qty<0) fail('Item and non-negative qty required');
  db()->prepare('UPDATE inventory SET quantity=? WHERE id=?')->execute([$qty,$iid]);
  audit((int)$me['id'],$me['name'],'Inventory adjust to '.$qty,'inventory',$iid,null);
  cache_bump('inventory');
  json_out(['ok'=>true]);
}

// ---------- Chef ----------
case 'chef_stock': {
  $u = require_role('chef');
  $kid = (int)$u['kitchen_id'];
  $rows = cache_remember("chefstock:v" . cache_version('inventory') . ":$kid", 15, function () use ($kid) {
    $st = db()->prepare("SELECT id AS item_id,item_name AS name,quantity AS qty,unit FROM inventory
                         WHERE kitchen_id=? AND menu_item_id IS NULL ORDER BY item_name");
    $st->execute([$kid]);
    return $st->fetchAll();
  });
  json_out(['ok'=>true,'rows'=>$rows]);
}
case 'consume': {
  $u = require_role('chef');
  $items = body()['items'] ?? [];   // [{item_id: inventory row id, qty}]
  if (!$items) fail('No items');
  $d = db(); $d->beginTransaction();
  try {
    foreach ($items as $it) {
      $iid=(int)($it['item_id']??0); $q=(float)($it['qty']??0);
      if (!$iid || $q<=0) continue;
      $st = $d->prepare('SELECT * FROM inventory WHERE id=? AND kitchen_id=? FOR UPDATE');
      $st->execute([$iid,(int)$u['kitchen_id']]);
      $row = $st->fetch();
      if (!$row || (float)$row['quantity'] < $q) { $d->rollBack(); fail('Not enough stock for '.($row['item_name']??('item #'.$iid))); }
      $d->prepare('UPDATE inventory SET quantity=quantity-? WHERE id=?')->execute([$q,$iid]);
      $d->prepare('INSERT INTO consumption (kitchen_id,inventory_id,item_name,quantity,unit,recorded_by) VALUES (?,?,?,?,?,?)')
        ->execute([(int)$u['kitchen_id'],$iid,$row['item_name'],$q,$row['unit'],(int)$u['id']]);
    }
    $d->commit();
    cache_bump('inventory');
    audit((int)$u['id'],$u['name'],'Record consumption ('.count($items).' items)','consumption',null,null);
    json_out(['ok'=>true]);
  } catch (Throwable $ex) { if ($d->inTransaction()) $d->rollBack(); fail('Failed: '.$ex->getMessage(),500); }
}

// ---------- Sales ----------
case 'sales_today': {
  require_role('sales');
  $sk = sales_kitchen_id();
  // 5s TTL — this drives whether staff can still sell an item, so it's kept
  // short on purpose; the write-side cache_bump('inventory') calls (order
  // placed/cancelled/reversed, stock allocated) make it correct immediately
  // regardless, this TTL is just a safety net for any path that's missed.
  $rows = cache_remember("salestoday:v" . cache_version('inventory') . ":$sk", 5, function () use ($sk) {
    $st = db()->prepare("SELECT i.menu_item_id,i.quantity AS qty,m.name,NULL AS name_urdu,c.icon AS emoji,m.price
                         FROM inventory i JOIN menu_items m ON m.id=i.menu_item_id
                         LEFT JOIN categories c ON c.name=m.category
                         WHERE i.kitchen_id=? AND i.menu_item_id IS NOT NULL ORDER BY m.name");
    $st->execute([$sk]);
    return $st->fetchAll();
  });
  json_out(['ok'=>true,'rows'=>$rows]);
}
case 'queue': {
  require_role('sales');
  $st = db()->query("SELECT o.id,o.id AS token,LOWER(o.status) AS status,o.total,o.created_at AS placed_at,
                     c.name customer,NULL AS customer_urdu,NULL AS photo_url
                     FROM orders o JOIN users c ON c.id=o.customer_id
                     WHERE o.status IN ('Pending','Preparing') ORDER BY o.created_at");
  $orders = $st->fetchAll();
  if ($orders) {
    $ids = implode(',', array_map(fn($o)=>(int)$o['id'], $orders));
    $items = db()->query("SELECT oi.order_id,oi.item_name AS name_snapshot,NULL AS name_urdu_snapshot,
                          oi.quantity AS qty,oi.price AS price_snapshot,oi.is_comp,c.icon AS emoji
                          FROM order_items oi LEFT JOIN menu_items m ON m.id=oi.menu_item_id
                          LEFT JOIN categories c ON c.name=m.category
                          WHERE oi.order_id IN ($ids)")->fetchAll();
    foreach ($orders as &$o)
      $o['items'] = array_values(array_filter($items, fn($i)=>(int)$i['order_id']===(int)$o['id']));
  }
  json_out(['ok'=>true,'rows'=>$orders]);
}
case 'order_status': {
  $u = require_role('sales');
  $b=body(); $id=(int)($b['id']??0); $status=strtolower($b['status']??'');
  if (!in_array($status,['preparing','delivered'],true)) fail('Bad status');
  $st = db()->prepare("SELECT * FROM orders WHERE id=? AND status IN ('Pending','Preparing')"); $st->execute([$id]);
  $o = $st->fetch(); if (!$o) fail('Order not found');
  db()->prepare('UPDATE orders SET status=? WHERE id=?')->execute([ucfirst($status),$id]);
  $vf = VOICE_DIR.'/order_'.$id.'.mp3';
  if ($status==='delivered' && file_exists($vf)) unlink($vf);   // free cached voice
  audit((int)$u['id'],$u['name'],'Order '.$status,'orders',$id,null);
  json_out(['ok'=>true]);
}
case 'order_voice': {
  $u = require_role('sales');
  $id = (int)(body()['id'] ?? 0);
  $st = db()->prepare("SELECT o.*, c.name FROM orders o JOIN users c ON c.id=o.customer_id WHERE o.id=?");
  $st->execute([$id]);
  $o = $st->fetch(); if (!$o) fail('Order not found');
  $file = 'voices/order_' . $id . '.mp3';
  if (file_exists(__DIR__.'/'.$file)) json_out(['ok'=>true,'cached'=>true,'url'=>$file]);
  $items = db()->prepare('SELECT * FROM order_items WHERE order_id=?'); $items->execute([$id]);
  $urduNums = [1=>'ایک',2=>'دو',3=>'تین',4=>'چار',5=>'پانچ',6=>'چھ',7=>'سات',8=>'آٹھ',9=>'نو',10=>'دس'];
  $parts = [];
  foreach ($items->fetchAll() as $it) {
    $n = (int)$it['quantity'];
    $parts[] = ($urduNums[$n] ?? $n) . ' ' . $it['item_name'];
  }
  $text = $o['name'] . '۔ ' . implode('، ', $parts);
  if (mb_strlen($text) > 90) $text = mb_substr($text, 0, 90);
  if (OPENAI_API_KEY === '') json_out(['ok'=>true,'fallback'=>true,'text'=>$text]);
  if (!is_dir(VOICE_DIR)) mkdir(VOICE_DIR, 0755, true);
  $ch = curl_init('https://api.openai.com/v1/audio/speech');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.OPENAI_API_KEY, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['model'=>TTS_MODEL,'voice'=>TTS_VOICE,'input'=>$text,
      'instructions'=>'Speak in clear, natural Urdu. Calm canteen announcement tone.','response_format'=>'mp3'], JSON_UNESCAPED_UNICODE),
  ]);
  $mp3 = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($code !== 200 || !$mp3) json_out(['ok'=>true,'fallback'=>true,'text'=>$text]);
  file_put_contents(__DIR__.'/'.$file, $mp3);
  audit((int)$u['id'],$u['name'],'Generate order voice','orders',$id,null);
  json_out(['ok'=>true,'cached'=>false,'url'=>$file]);
}

// ---------- Customer ----------
case 'customer_menu': {
  $u = require_role('customer');
  $sk = sales_kitchen_id();
  $acc = array_intersect(array_filter(array_map('trim', explode(',', (string)($u['menu_access'] ?? 'standard')))), ['standard','special','executive']);
  if (!$acc) $acc = ['standard'];
  $groups = "'".implode("','", $acc)."'";
  // shared across every customer with the same menu-access + sales kitchen,
  // so this is cached — but wallet balance and this customer's personal
  // complimentary allocations below are NOT, those stay live every request.
  $cacheKey = "cmenu:v" . cache_version('menu') . '.' . cache_version('inventory') . ":$sk:" . implode(',', $acc);
  $rows = cache_remember($cacheKey, 15, function () use ($sk, $groups) {
    $st = db()->prepare("SELECT m.id,m.name,NULL AS name_urdu,m.category,m.price,m.menu_group,c.icon AS emoji,
                         COALESCE(i.quantity,0) stock
                         FROM menu_items m LEFT JOIN categories c ON c.name=m.category
                         LEFT JOIN inventory i ON i.menu_item_id=m.id AND i.kitchen_id=?
                         WHERE m.status='active' AND m.menu_group IN ($groups) ORDER BY m.category,m.name");
    $st->execute([$sk]);
    return $st->fetchAll();
  });
  $cs = db()->prepare("SELECT ca.menu_item_id, m.name, m.price, m.menu_group, ci.icon AS emoji, COALESCE(i.quantity,0) AS stock,
                       ca.qty_allowed,
                       CASE WHEN ca.repeat_daily=1 AND (ca.used_on IS NULL OR ca.used_on < CURDATE()) THEN 0 ELSE ca.qty_used END AS qty_used,
                       ca.qty_allowed - (CASE WHEN ca.repeat_daily=1 AND (ca.used_on IS NULL OR ca.used_on < CURDATE()) THEN 0 ELSE ca.qty_used END) AS remaining,
                       ca.expiry_date, ca.repeat_daily, ca.days_of_week, ca.time_from, ca.time_to
                       FROM complimentary_allocations ca
                       JOIN menu_items m ON m.id=ca.menu_item_id AND m.status='active'
                       LEFT JOIN categories ci ON ci.name=m.category
                       LEFT JOIN inventory i ON i.menu_item_id=ca.menu_item_id AND i.kitchen_id=?
                       WHERE ca.customer_id=? AND ca.status='active'
                       AND (ca.expiry_date IS NULL OR ca.expiry_date >= CURDATE())
                       AND (ca.days_of_week IS NULL OR FIND_IN_SET(?, ca.days_of_week))
                       AND (ca.time_from IS NULL OR ? BETWEEN ca.time_from AND ca.time_to)
                       HAVING remaining > 0");
  $cs->execute([$sk, (int)$u['id'], date('N'), date('H:i:s')]);
  json_out(['ok'=>true,'rows'=>$rows,'comps'=>$cs->fetchAll(),'balance'=>wallet_balance((int)$u['id']),'kitchen_id'=>$sk]);
}
case 'place_order': {
  $u = require_role('customer');
  $b=body(); $cart = $b['items'] ?? [];
  if (!$cart || !is_array($cart)) fail('Cart is empty');
  if (count($cart) > 30) fail('Too many cart lines');
  $sk = sales_kitchen_id();
  if (!$sk) fail('Canteen is closed (no active sales kitchen)');
  // merge duplicate lines (same item + same comp flag) so limit/stock checks see true totals
  $merged = [];
  foreach ($cart as $c) {
    $mid=(int)($c['menu_item_id']??0); $q=(int)($c['qty']??0); $comp = empty($c['comp']) ? 0 : 1;
    if (!$mid || $q<=0 || $q>50) fail('Bad cart line');
    $key = $mid.'-'.$comp;
    if (!isset($merged[$key])) $merged[$key] = ['mid'=>$mid,'q'=>0,'comp'=>$comp];
    $merged[$key]['q'] += $q;
    if ($merged[$key]['q'] > 50) fail('Max 50 per item');
  }
  $d = db(); $d->beginTransaction();
  try {
    $selItem = $d->prepare("SELECT m.*, i.quantity stock, i.id iid FROM menu_items m
                       LEFT JOIN inventory i ON i.menu_item_id=m.id AND i.kitchen_id=?
                       WHERE m.id=? AND m.status='active' FOR UPDATE");
    $allowed = array_values(array_intersect(array_filter(array_map('trim', explode(',', (string)($u['menu_access'] ?? 'standard')))), ['standard','special','executive']));
    if (!$allowed) $allowed = ['standard'];
    $selComp = $d->prepare("SELECT * FROM complimentary_allocations
                       WHERE customer_id=? AND menu_item_id=? AND status='active'
                       AND (expiry_date IS NULL OR expiry_date >= CURDATE())
                       AND (days_of_week IS NULL OR FIND_IN_SET(?, days_of_week))
                       AND (time_from IS NULL OR ? BETWEEN time_from AND time_to) FOR UPDATE");
    $total = 0.0; $lines = []; $need = []; $compCount = 0;
    foreach ($merged as $c) {
      $mid=$c['mid']; $q=$c['q'];
      $selItem->execute([$sk,$mid]);
      $m = $selItem->fetch();
      if (!$m) { $d->rollBack(); fail('Item unavailable'); }
      // paid lines must be inside the customer's menu access; complimentary lines are validated
      // by their specific allocation below, so an allocated item may sit outside the access tiers.
      if (!$c['comp'] && !in_array($m['menu_group'] ?? 'standard', $allowed, true)) { $d->rollBack(); fail($m['name'].' is not available on your menu'); }
      $need[$mid] = ($need[$mid] ?? 0) + $q;   // combined paid+comp demand per item
      if ((float)($m['stock'] ?? 0) < $need[$mid]) { $d->rollBack(); fail($m['name'].' is out of stock'); }
      $compId = null;
      if ($c['comp']) {
        $selComp->execute([(int)$u['id'],$mid,date('N'),date('H:i:s')]);
        $ca = $selComp->fetch();
        if (!$ca) { $d->rollBack(); fail('Complimentary offer for '.$m['name'].' is not available right now'); }
        $used = (int)$ca['qty_used'];
        if ((int)$ca['repeat_daily']===1 && ($ca['used_on']===null || $ca['used_on'] < date('Y-m-d'))) $used = 0;  // daily reset
        if ((int)$ca['qty_allowed'] - $used < $q) { $d->rollBack(); fail('Complimentary limit reached for '.$m['name']); }
        $compId = (int)$ca['id']; $compUsedNew = $used + $q; $compCount += $q;
      } else {
        $total += $m['price'] * $q;
      }
      $lines[] = ['m'=>$m,'q'=>$q,'comp'=>$c['comp'],'comp_id'=>$compId,'comp_used_new'=>$compUsedNew ?? 0];
    }
    if ($total > 0) {
      $st = $d->prepare('SELECT wallet FROM users WHERE id=? FOR UPDATE');
      $st->execute([(int)$u['id']]);
      if ((float)$st->fetchColumn() < $total) { $d->rollBack(); fail('Insufficient wallet balance'); }
    }
    $d->prepare("INSERT INTO orders (customer_id,total,status) VALUES (?,?,'Pending')")
      ->execute([(int)$u['id'],$total]);
    $oid = (int)$d->lastInsertId();
    $insItem = $d->prepare('INSERT INTO order_items (order_id,menu_item_id,item_name,price,quantity,subtotal,is_comp) VALUES (?,?,?,?,?,?,?)');
    $updInv  = $d->prepare('UPDATE inventory SET quantity=quantity-? WHERE id=?');
    $useComp = $d->prepare('UPDATE complimentary_allocations SET qty_used=?, used_on=CURDATE() WHERE id=?');
    foreach ($lines as $l) {
      $price = $l['comp'] ? 0.0 : (float)$l['m']['price'];
      $insItem->execute([$oid,(int)$l['m']['id'],$l['m']['name'],$price,$l['q'],$price*$l['q'],$l['comp']]);
      $updInv->execute([$l['q'],(int)$l['m']['iid']]);
      if ($l['comp']) $useComp->execute([$l['comp_used_new'],$l['comp_id']]);
    }
    if ($total > 0) {
      $d->prepare('UPDATE users SET wallet=wallet-? WHERE id=?')->execute([$total,(int)$u['id']]);
      $d->prepare("INSERT INTO wallet_topups (customer_id,amount,type,note,created_by) VALUES (?,?,'order',?,?)")
        ->execute([(int)$u['id'],$total,'Order #'.$oid,(int)$u['id']]);
    }
    $d->commit();
    cache_bump('inventory'); // sales-kitchen stock just dropped — sales_today/customer_menu must reflect it now
    audit((int)$u['id'],$u['name'],'Place order '.CURRENCY.' '.$total.($compCount?' + '.$compCount.' complimentary':''),'orders',$oid,null);
    json_out(['ok'=>true,'order_id'=>$oid,'token'=>$oid,'total'=>$total,'balance'=>wallet_balance((int)$u['id'])]);
  } catch (Throwable $ex) { if ($d->inTransaction()) $d->rollBack(); fail('Order failed: '.$ex->getMessage(),500); }
}
case 'my_orders': {
  $u = require_role('customer');
  $st = db()->prepare("SELECT id,id AS token,LOWER(status) AS status,total,cancel_reason,created_at AS placed_at
                       FROM orders WHERE customer_id=? ORDER BY created_at DESC LIMIT 50");
  $st->execute([(int)$u['id']]);
  $orders = $st->fetchAll();
  if ($orders) {
    $ids = implode(',', array_map(fn($o)=>(int)$o['id'], $orders));
    $items = db()->query("SELECT order_id,item_name AS name_snapshot,quantity AS qty,price AS price_snapshot,is_comp
                          FROM order_items WHERE order_id IN ($ids)")->fetchAll();
    foreach ($orders as &$o)
      $o['items'] = array_values(array_filter($items, fn($i)=>(int)$i['order_id']===(int)$o['id']));
  }
  json_out(['ok'=>true,'rows'=>$orders,'balance'=>wallet_balance((int)$u['id'])]);
}
case 'wallet_balance': {
  $u = require_role('customer');
  json_out(['ok'=>true,'balance'=>wallet_balance((int)$u['id'])]);
}

// ---------- Admin: orders, cancel, reverse ----------
case 'orders_list': {
  require_role('admin');
  $orders = db()->query("SELECT o.id,o.id AS token,LOWER(o.status) AS status,o.total,o.cancel_reason,
                         o.created_at AS placed_at,c.name customer
                         FROM orders o JOIN users c ON c.id=o.customer_id
                         ORDER BY o.created_at DESC LIMIT 200")->fetchAll();
  if ($orders) {
    $ids = implode(',', array_map(fn($o)=>(int)$o['id'], $orders));
    $items = db()->query("SELECT order_id,item_name AS name_snapshot,quantity AS qty,price AS price_snapshot,menu_item_id,is_comp
                          FROM order_items WHERE order_id IN ($ids)")->fetchAll();
    foreach ($orders as &$o)
      $o['items'] = array_values(array_filter($items, fn($i)=>(int)$i['order_id']===(int)$o['id']));
  }
  json_out(['ok'=>true,'rows'=>$orders]);
}
case 'order_cancel': {
  $me = require_role('admin');
  $b=body(); $id=(int)($b['id']??0); $reason=trim($b['reason']??'');
  if ($reason==='') fail('Cancel reason required');
  $sk = sales_kitchen_id();
  $d=db(); $d->beginTransaction();
  try {
    $st=$d->prepare("SELECT * FROM orders WHERE id=? AND status IN ('Pending','Preparing') FOR UPDATE"); $st->execute([$id]);
    $o=$st->fetch(); if(!$o){ $d->rollBack(); fail('Order not cancellable'); }
    $items=$d->prepare('SELECT * FROM order_items WHERE order_id=?'); $items->execute([$id]);
    $restoreComp = $d->prepare('UPDATE complimentary_allocations SET qty_used=GREATEST(0,qty_used-?) WHERE customer_id=? AND menu_item_id=? AND status=\'active\'');
    foreach ($items->fetchAll() as $it) {  // restore sales stock + comp usage
      $d->prepare('UPDATE inventory SET quantity=quantity+? WHERE kitchen_id=? AND menu_item_id=?')
        ->execute([(int)$it['quantity'],$sk,(int)$it['menu_item_id']]);
      if (!empty($it['is_comp'])) $restoreComp->execute([(int)$it['quantity'],(int)$o['customer_id'],(int)$it['menu_item_id']]);
    }
    if ((float)$o['total'] > 0) {
      $d->prepare('UPDATE users SET wallet=wallet+? WHERE id=?')->execute([(float)$o['total'],(int)$o['customer_id']]);
      $d->prepare("INSERT INTO wallet_topups (customer_id,amount,type,note,created_by) VALUES (?,?,'refund',?,?)")
        ->execute([(int)$o['customer_id'],(float)$o['total'],'Cancelled order #'.$id.': '.$reason,(int)$me['id']]);
    }
    $d->prepare("UPDATE orders SET status='Cancelled',cancel_reason=? WHERE id=?")->execute([$reason,$id]);
    $d->commit();
    cache_bump('inventory'); // stock was restored above
    audit((int)$me['id'],$me['name'],'Cancel order (refund + stock restore)','orders',$id,$reason);
    json_out(['ok'=>true]);
  } catch (Throwable $ex){ if($d->inTransaction())$d->rollBack(); fail('Cancel failed: '.$ex->getMessage(),500); }
}
case 'order_reverse': {
  $me = require_role('admin');
  $id=(int)(body()['id']??0);
  $d=db(); $d->beginTransaction();
  try {
    $st=$d->prepare("SELECT * FROM orders WHERE id=? AND status='Delivered' FOR UPDATE"); $st->execute([$id]);
    $o=$st->fetch(); if(!$o){ $d->rollBack(); fail('Only delivered orders can be reversed'); }
    $d->prepare('UPDATE users SET wallet=wallet+? WHERE id=?')->execute([(float)$o['total'],(int)$o['customer_id']]);
    $d->prepare("INSERT INTO wallet_topups (customer_id,amount,type,note,created_by) VALUES (?,?,'refund',?,?)")
      ->execute([(int)$o['customer_id'],(float)$o['total'],'Reversed order #'.$id,(int)$me['id']]);
    $d->prepare("UPDATE orders SET status='Reversed' WHERE id=?")->execute([$id]);
    $d->commit();  // no stock restore on reverse, by spec
    audit((int)$me['id'],$me['name'],'Reverse order (refund only)','orders',$id,null);
    json_out(['ok'=>true]);
  } catch (Throwable $ex){ if($d->inTransaction())$d->rollBack(); fail('Reverse failed: '.$ex->getMessage(),500); }
}

// ---------- Dish photos (admin) ----------
// Stored on disk as dish/<menu_item_id>.webp, never in the database, so no schema
// change is needed and a missing file simply falls back to the item's emoji.
case 'dish_photo_save': {
  $me = require_role('admin');
  $b  = body();
  $id = (int)($b['menu_item_id'] ?? 0);
  $data = (string)($b['data'] ?? '');
  if (!$id) fail('Menu item required');
  $st = db()->prepare('SELECT name FROM menu_items WHERE id = ?');
  $st->execute([$id]);
  $mi = $st->fetch(); if (!$mi) fail('Menu item not found');

  if (!preg_match('#^data:image/(webp|jpeg|png);base64,#', $data, $m)) fail('Unsupported image format');
  $raw = base64_decode(substr($data, strpos($data, ',') + 1), true);
  if ($raw === false || strlen($raw) < 256) fail('Could not read that image');
  if (strlen($raw) > 400 * 1024) fail('Image too large — keep it under 400 KB');
  $info = @getimagesizefromstring($raw);
  if (!$info) fail('That file is not a real image');

  $dir = __DIR__ . '/dish';
  if (!is_dir($dir) && !@mkdir($dir, 0755, true)) fail('Could not create the dish/ folder — create it manually and set it writable', 500);
  $ext  = $m[1] === 'jpeg' ? 'jpg' : $m[1];
  foreach (['webp','jpg','png'] as $old) {            // one photo per dish, whatever the old extension
    if ($old !== $ext && is_file("$dir/$id.$old")) @unlink("$dir/$id.$old");
  }
  if (@file_put_contents("$dir/$id.$ext", $raw) === false) fail('Could not write the file — check dish/ is writable', 500);

  audit((int)$me['id'], $me['name'], 'Dish photo uploaded: ' . $mi['name'] . ' (' . round(strlen($raw)/1024) . ' KB)', 'menu_items', $id, null);
  json_out(['ok'=>true,'url'=>'dish/'.$id.'.'.$ext,'kb'=>round(strlen($raw)/1024,1)]);
}
case 'dish_photo_delete': {
  $me = require_role('admin');
  $id = (int)(body()['menu_item_id'] ?? 0);
  if (!$id) fail('Menu item required');
  $gone = false;
  foreach (['webp','jpg','png'] as $ext) {
    if (is_file(__DIR__ . "/dish/$id.$ext")) { @unlink(__DIR__ . "/dish/$id.$ext"); $gone = true; }
  }
  if (!$gone) fail('No photo to remove');
  audit((int)$me['id'], $me['name'], 'Dish photo removed', 'menu_items', $id, null);
  json_out(['ok'=>true]);
}
case 'dish_photo_list': {
  require_role('admin','accounts');
  $out = [];
  foreach (db()->query('SELECT id FROM menu_items')->fetchAll() as $r) {
    foreach (['webp','jpg','png'] as $ext) {
      $f = __DIR__ . '/dish/' . $r['id'] . '.' . $ext;
      if (is_file($f)) { $out[(string)$r['id']] = ['url'=>'dish/'.$r['id'].'.'.$ext,'kb'=>round(filesize($f)/1024,1)]; break; }
    }
  }
  json_out(['ok'=>true,'photos'=>$out]);
}

// ---------- Reports & audit ----------
// Shared by Admin and Accounts — both roles get exactly the same numbers.
// Free (complimentary) lines are stored with price/subtotal 0, so their worth is
// valued at the CURRENT menu price: quantity * menu_items.price.
case 'report': {
  require_role('admin','accounts');
  $type = $_GET['type'] ?? 'sales';
  $from = $_GET['from'] ?? date('Y-m-01');
  $to   = $_GET['to']   ?? date('Y-m-d');
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) fail('Bad date range');
  if ($from > $to) { $swap = $from; $from = $to; $to = $swap; }
  $d = db();
  $LIVE = "('Delivered','Preparing','Pending')";   // orders that count as real business

  $one = function (string $sql, array $p) use ($d) { $st = $d->prepare($sql); $st->execute($p); return $st->fetch(); };

  // ---------- headline figures (identical on every tab) ----------
  $rev = $one("SELECT COUNT(*) orders, COALESCE(SUM(total),0) revenue, COUNT(DISTINCT customer_id) customers
               FROM orders WHERE status IN $LIVE AND DATE(created_at) BETWEEN ? AND ?", [$from,$to]);
  $free = $one("SELECT COALESCE(SUM(oi.quantity),0) units, COALESCE(SUM(oi.quantity * m.price),0) value
                FROM order_items oi JOIN orders o ON o.id = oi.order_id
                LEFT JOIN menu_items m ON m.id = oi.menu_item_id
                WHERE oi.is_comp = 1 AND o.status IN $LIVE AND DATE(o.created_at) BETWEEN ? AND ?", [$from,$to]);
  $lost = $one("SELECT COUNT(*) orders, COALESCE(SUM(total),0) value FROM orders
                WHERE status IN ('Cancelled','Reversed') AND DATE(created_at) BETWEEN ? AND ?", [$from,$to]);
  $buy  = $one("SELECT COUNT(*) bills, COALESCE(SUM(total_amount),0) value FROM purchases
                WHERE purchase_date BETWEEN ? AND ?", [$from,$to]);
  $wal  = $one("SELECT COALESCE(SUM(CASE WHEN type='topup' THEN amount END),0) topups,
                       COALESCE(SUM(CASE WHEN type='refund' THEN amount END),0) refunds
                FROM wallet_topups WHERE DATE(created_at) BETWEEN ? AND ?", [$from,$to]);
  $liability = (float)$d->query("SELECT COALESCE(SUM(wallet),0) FROM users WHERE role='customer' AND status='active'")->fetchColumn();
  $orders = (int)$rev['orders'];

  $summary = [
    'revenue'    => (float)$rev['revenue'],
    'orders'     => $orders,
    'avg_order'  => $orders ? round((float)$rev['revenue'] / $orders, 2) : 0,
    'customers'  => (int)$rev['customers'],
    'free_value' => (float)$free['value'],
    'free_units' => (int)$free['units'],
    'lost_value' => (float)$lost['value'],
    'lost_orders'=> (int)$lost['orders'],
    'purchases'  => (float)$buy['value'],
    'bills'      => (int)$buy['bills'],
    'topups'     => (float)$wal['topups'],
    'refunds'    => (float)$wal['refunds'],
    'liability'  => $liability,
    'margin'     => (float)$rev['revenue'] - (float)$buy['value'],
  ];

  // ---------- the selected table ----------
  $p = [$from,$to];
  if ($type === 'sales') {
    $money = ['revenue','free_value','total_value'];
    $sql = "SELECT DATE(o.created_at) day, COUNT(*) orders, COALESCE(SUM(o.total),0) revenue,
              COALESCE((SELECT SUM(oi.quantity * m.price) FROM order_items oi
                        JOIN orders o2 ON o2.id = oi.order_id
                        LEFT JOIN menu_items m ON m.id = oi.menu_item_id
                        WHERE oi.is_comp = 1 AND o2.status IN $LIVE
                          AND DATE(o2.created_at) = DATE(o.created_at)),0) free_value,
              COALESCE(SUM(o.total),0) + COALESCE((SELECT SUM(oi.quantity * m.price) FROM order_items oi
                        JOIN orders o3 ON o3.id = oi.order_id
                        LEFT JOIN menu_items m ON m.id = oi.menu_item_id
                        WHERE oi.is_comp = 1 AND o3.status IN $LIVE
                          AND DATE(o3.created_at) = DATE(o.created_at)),0) total_value
            FROM orders o
            WHERE o.status IN $LIVE AND DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY DATE(o.created_at) ORDER BY day DESC";
  } elseif ($type === 'items') {
    $money = ['revenue','free_value'];
    $sql = "SELECT oi.item_name item,
              COALESCE(SUM(CASE WHEN oi.is_comp = 0 THEN oi.quantity ELSE 0 END),0) paid_qty,
              COALESCE(SUM(CASE WHEN oi.is_comp = 0 THEN oi.subtotal ELSE 0 END),0) revenue,
              COALESCE(SUM(CASE WHEN oi.is_comp = 1 THEN oi.quantity ELSE 0 END),0) free_qty,
              COALESCE(SUM(CASE WHEN oi.is_comp = 1 THEN oi.quantity * m.price ELSE 0 END),0) free_value
            FROM order_items oi JOIN orders o ON o.id = oi.order_id
            LEFT JOIN menu_items m ON m.id = oi.menu_item_id
            WHERE o.status IN $LIVE AND DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY oi.item_name ORDER BY revenue DESC, free_value DESC";
  } elseif ($type === 'customers') {
    $money = ['spent','free_value','balance'];
    $p = [$from,$to,$from,$to];
    $sql = "SELECT c.name customer, COUNT(*) orders, COALESCE(SUM(o.total),0) spent,
              COALESCE((SELECT SUM(oi.quantity * m.price) FROM order_items oi
                        JOIN orders o2 ON o2.id = oi.order_id
                        LEFT JOIN menu_items m ON m.id = oi.menu_item_id
                        WHERE oi.is_comp = 1 AND o2.customer_id = c.id AND o2.status IN $LIVE
                          AND DATE(o2.created_at) BETWEEN ? AND ?),0) free_value,
              c.wallet balance
            FROM orders o JOIN users c ON c.id = o.customer_id
            WHERE o.status IN $LIVE AND DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY c.id ORDER BY spent DESC";
  } elseif ($type === 'free') {
    $money = ['value'];
    $sql = "SELECT DATE(o.created_at) day, c.name customer, oi.item_name item,
              COALESCE(SUM(oi.quantity),0) qty, COALESCE(SUM(oi.quantity * m.price),0) value
            FROM order_items oi JOIN orders o ON o.id = oi.order_id
            JOIN users c ON c.id = o.customer_id
            LEFT JOIN menu_items m ON m.id = oi.menu_item_id
            WHERE oi.is_comp = 1 AND o.status IN $LIVE AND DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY DATE(o.created_at), c.id, oi.item_name ORDER BY day DESC, value DESC";
  } elseif ($type === 'wallets') {
    $money = ['topups','refunds','spent','deducted','balance'];
    $sql = "SELECT c.name customer,
              COALESCE(SUM(CASE WHEN w.type='topup'  THEN w.amount END),0) topups,
              COALESCE(SUM(CASE WHEN w.type='refund' THEN w.amount END),0) refunds,
              COALESCE(SUM(CASE WHEN w.type='order'  THEN w.amount END),0) spent,
              COALESCE(SUM(CASE WHEN w.type='deduct' THEN w.amount END),0) deducted,
              c.wallet balance
            FROM users c
            LEFT JOIN wallet_topups w ON w.customer_id = c.id AND DATE(w.created_at) BETWEEN ? AND ?
            WHERE c.role = 'customer' AND c.status = 'active'
            GROUP BY c.id
            HAVING topups > 0 OR refunds > 0 OR spent > 0 OR deducted > 0 OR balance > 0
            ORDER BY topups DESC, balance DESC";
  } else {
    $type  = 'purchases';
    $money = ['rate','total'];
    $sql = "SELECT p.purchase_date, p.item_name item, p.vendor_name vendor, k.name kitchen,
              p.quantity qty, p.rate, p.total_amount total, p.payment_type, p.bill_number
            FROM purchases p JOIN kitchens k ON k.id = p.kitchen_id
            WHERE p.purchase_date BETWEEN ? AND ? ORDER BY p.purchase_date DESC";
  }
  $st = $d->prepare($sql);
  $st->execute($p);
  json_out(['ok'=>true,'type'=>$type,'from'=>$from,'to'=>$to,'summary'=>$summary,
            'money'=>$money,'rows'=>$st->fetchAll()]);
}
case 'audit_list': {
  require_role('admin');
  $rows = db()->query("SELECT id,created_at,COALESCE(user_name,'—') user,action,
                       COALESCE(target,'') entity,target_id entity_id,reason AS after_json
                       FROM audit_log ORDER BY created_at DESC, id DESC LIMIT 300")->fetchAll();
  json_out(['ok'=>true,'rows'=>$rows]);
}

// ---------- Complimentary allocations (admin + accounts) ----------
case 'comp_list': {
  require_role('admin','accounts');
  $rows = db()->query("SELECT ca.id,ca.customer_id,cu.name customer,ca.menu_item_id,m.name item,ci.icon AS emoji,
      ca.qty_allowed,
      CASE WHEN ca.repeat_daily=1 AND (ca.used_on IS NULL OR ca.used_on < CURDATE()) THEN 0 ELSE ca.qty_used END AS qty_used,
      ca.expiry_date,ca.days_of_week,ca.time_from,ca.time_to,ca.repeat_daily,ca.created_at,COALESCE(ab.name,'—') assigned_by,
      CASE WHEN ca.status='disabled' THEN 'removed'
           WHEN ca.expiry_date IS NOT NULL AND ca.expiry_date < CURDATE() THEN 'expired'
           WHEN ca.repeat_daily=0 AND ca.qty_used >= ca.qty_allowed THEN 'used up'
           ELSE 'active' END AS state
      FROM complimentary_allocations ca
      JOIN users cu ON cu.id=ca.customer_id
      JOIN menu_items m ON m.id=ca.menu_item_id
      LEFT JOIN categories ci ON ci.name=m.category
      LEFT JOIN users ab ON ab.id=ca.assigned_by
      ORDER BY ca.created_at DESC, ca.id DESC LIMIT 500")->fetchAll();
  json_out(['ok'=>true,'rows'=>$rows]);
}
case 'comp_save': {
  $me = require_role('admin','accounts');
  $b=body(); $id=(int)($b['id']??0);
  $qty=(int)($b['qty_allowed']??0); $exp=trim((string)($b['expiry_date']??''));
  if ($qty<1 || $qty>100) fail('Quantity limit must be 1–100');
  if ($exp!=='') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$exp)) fail('Bad expiry date');
    if ($exp < date('Y-m-d')) fail('Expiry date cannot be in the past');
  } else $exp = null;
  // schedule: days_of_week '1,3,5' (1=Mon…7=Sun), optional time window, daily repeat
  $days = trim((string)($b['days']??''));
  if ($days!=='') {
    $parts = array_filter(array_map('trim', explode(',',$days)));
    foreach ($parts as $p) if (!in_array($p,['1','2','3','4','5','6','7'],true)) fail('Bad days');
    $days = $parts ? implode(',',$parts) : null;
  } else $days = null;
  $tf = trim((string)($b['time_from']??'')); $tt = trim((string)($b['time_to']??''));
  if ($tf!=='' || $tt!=='') {
    if (!preg_match('/^\d{2}:\d{2}$/',$tf) || !preg_match('/^\d{2}:\d{2}$/',$tt)) fail('Set both start and end time');
    if ($tf >= $tt) fail('End time must be after start time');
    $tf .= ':00'; $tt .= ':00';
  } else { $tf=null; $tt=null; }
  $rd = empty($b['repeat_daily']) ? 0 : 1;
  $schedTxt = ($days?' days '.$days:'').($tf?' '.$tf.'-'.$tt:'').($rd?' daily-reset':'');
  if ($id) {   // edit limit / expiry of an existing allocation
    $st=db()->prepare("SELECT * FROM complimentary_allocations WHERE id=? AND status='active'"); $st->execute([$id]);
    $ca=$st->fetch(); if(!$ca) fail('Allocation not found');
    if ($qty < (int)$ca['qty_used'] && !$ca['repeat_daily']) fail('Limit cannot be below already-used quantity ('.$ca['qty_used'].')');
    db()->prepare('UPDATE complimentary_allocations SET qty_allowed=?,expiry_date=?,days_of_week=?,time_from=?,time_to=?,repeat_daily=? WHERE id=?')
      ->execute([$qty,$exp,$days,$tf,$tt,$rd,$id]);
    audit((int)$me['id'],$me['name'],'Update complimentary (limit '.$qty.($exp?', expires '.$exp:'').$schedTxt.')','complimentary',$id,null);
    json_out(['ok'=>true,'id'=>$id]);
  }
  $custIds = array_values(array_unique(array_map('intval', (array)($b['customer_ids'] ?? ($b['customer_id']?[$b['customer_id']]:[])))));
  $midIds  = array_values(array_unique(array_map('intval', (array)($b['menu_item_ids'] ?? ($b['menu_item_id']?[$b['menu_item_id']]:[])))));
  if (!$custIds || !$midIds) fail('Pick at least one customer and one menu item');
  if (count($custIds)*count($midIds) > 200) fail('Too many combinations at once (max 200)');
  $inC = implode(',', array_fill(0,count($custIds),'?'));
  $st = db()->prepare("SELECT id,name FROM users WHERE id IN ($inC) AND role='customer' AND status='active'");
  $st->execute($custIds); $custs = $st->fetchAll();
  if (count($custs) !== count($custIds)) fail('Some selected customers not found');
  $inM = implode(',', array_fill(0,count($midIds),'?'));
  $st = db()->prepare("SELECT id,name FROM menu_items WHERE id IN ($inM) AND status='active'");
  $st->execute($midIds); $items = $st->fetchAll();
  if (count($items) !== count($midIds)) fail('Some selected menu items not found');
  $dup = db()->prepare("SELECT id FROM complimentary_allocations WHERE customer_id=? AND menu_item_id=? AND status='active'
                        AND (repeat_daily=1 OR qty_used < qty_allowed) AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
  $ins = db()->prepare('INSERT INTO complimentary_allocations (customer_id,menu_item_id,qty_allowed,expiry_date,days_of_week,time_from,time_to,repeat_daily,assigned_by) VALUES (?,?,?,?,?,?,?,?,?)');
  $made=0; $skipped=0;
  foreach ($custs as $cu) foreach ($items as $mi) {
    $dup->execute([(int)$cu['id'],(int)$mi['id']]);
    if ($dup->fetch()) { $skipped++; continue; }
    $ins->execute([(int)$cu['id'],(int)$mi['id'],$qty,$exp,$days,$tf,$tt,$rd,(int)$me['id']]);
    audit((int)$me['id'],$me['name'],'Assign complimentary: '.$mi['name'].' x'.$qty.' to '.$cu['name'].($exp?' (expires '.$exp.')':'').$schedTxt,'complimentary',(int)db()->lastInsertId(),null);
    $made++;
  }
  json_out(['ok'=>true,'created'=>$made,'skipped'=>$skipped]);
}
case 'comp_remove': {
  $me = require_role('admin','accounts');
  $id=(int)(body()['id']??0);
  $st=db()->prepare("SELECT ca.*,cu.name customer,m.name item FROM complimentary_allocations ca
                     JOIN users cu ON cu.id=ca.customer_id JOIN menu_items m ON m.id=ca.menu_item_id WHERE ca.id=?");
  $st->execute([$id]);
  $ca=$st->fetch(); if(!$ca) fail('Allocation not found');
  if ($ca['status']==='disabled') fail('Already removed');
  db()->prepare("UPDATE complimentary_allocations SET status='disabled' WHERE id=?")->execute([$id]);
  audit((int)$me['id'],$me['name'],'Remove complimentary: '.$ca['item'].' from '.$ca['customer'].' (used '.$ca['qty_used'].'/'.$ca['qty_allowed'].')','complimentary',$id,null);
  json_out(['ok'=>true]);
}

default: fail('Unknown action', 404);
}
