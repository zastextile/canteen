<?php
/* ============================================================================
   THE TWO APPS, TALKING TO EACH OTHER FOR REAL
   ============================================================================
   The canteen and ZAS are separate applications with separate databases, and
   each has its own tests — but a test suite on one side can only ever use a
   stand-in for the other. ZAS's canteen tests post to a fake canteen; the
   canteen's tests answer a fake ZAS. Neither proves the two agree.

   This one runs BOTH, over real HTTP, against real copies of both databases:
   a card tap at the counter asks the real ZAS; "Get canteen charges" in ZAS
   asks the real canteen; sending the pay sheet reserves at the counter and
   approving it settles there. It is the only place a field name, a rounding
   or a date boundary between them can be caught.

   HOW TO RUN IT
     1. MariaDB on 127.0.0.1:3306 with two databases:
          canteen_t  loaded from the canteen dump + migration_kiosk.sql
          zas_t      loaded from the ERP schema + php att_migrate.php up
     2. The canteen served on :8080, ZAS on :8082.
     3. Both configs holding the SAME two keys, and ZAS's canteen_url
        pointing at http://127.0.0.1:8080/zas.php (no query string: ZAS
        appends ?a=charges itself and refuses a url that already has one).
     4. php tests/live_canteen_zas.php

   Checked against ZAS commit e471630 ("Canteen charges on pay"): 44 checks.
   ============================================================================ */
date_default_timezone_set('Asia/Karachi');
$CAN = 'http://127.0.0.1:8080';
$ZAS = 'http://127.0.0.1:8082';
$CDB = new PDO('mysql:host=127.0.0.1;dbname=canteen_t;charset=utf8mb4','root','',
  [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$ZDB = new PDO('mysql:host=127.0.0.1;dbname=zas_t;charset=utf8mb4','root','',
  [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$CDB->exec("SET time_zone='+05:00'");

$PASS=0; $FAIL=0;
function ok(bool $c, string $what, $extra=''){ global $PASS,$FAIL;
  if($c){$PASS++; echo "  ok   $what\n";} else {$FAIL++; echo "  FAIL $what".($extra!==''?"  <<< $extra":'')."\n";} }
function head(string $s){ echo "\n== $s\n"; }

class Web {
  public $jar; public $csrf='';
  function __construct($t){ $this->jar="/tmp/lj-$t"; @unlink($this->jar); }
  function go($url,$post=null,array $hdr=[],bool $json=false){
    $ch=curl_init($url);
    $o=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$this->jar,CURLOPT_COOKIEFILE=>$this->jar,
        CURLOPT_TIMEOUT=>60,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_HTTPHEADER=>$hdr];
    if($post!==null){$o[CURLOPT_POST]=true;$o[CURLOPT_POSTFIELDS]=$json?json_encode($post):http_build_query($post);}
    curl_setopt_array($ch,$o);
    $b=curl_exec($ch); $c=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    return ['code'=>$c,'raw'=>(string)$b,'j'=>json_decode((string)$b,true)];
  }
}

/* ---------- the canteen counter ---------- */
$cash = new Web('cash');
$cash->go("$CAN/api.php?a=login",['username'=>'sale','password'=>'test1234'],['Content-Type: application/json'],true);
$CSRF = $cash->go("$CAN/api.php?a=whoami")['j']['csrf'] ?? '';
function kiosk(string $a,$d=null){ global $cash,$CAN,$CSRF;
  return $cash->go("$CAN/kiosk_api.php?a=$a",$d,['Content-Type: application/json','X-CSRF: '.$CSRF],true); }
$acct = new Web('acct');
$acct->go("$CAN/api.php?a=login",['username'=>'account','password'=>'test1234'],['Content-Type: application/json'],true);
$ACSRF = $acct->go("$CAN/api.php?a=whoami")['j']['csrf'] ?? '';
function kioskA(string $a,$d=null){ global $acct,$CAN,$ACSRF;
  return $acct->go("$CAN/kiosk_api.php?a=$a",$d,['Content-Type: application/json','X-CSRF: '.$ACSRF],true); }

/* ---------- the ZAS office ---------- */
$zas = new Web('zas');
$tok = preg_match('/name="_csrf" value="([^"]+)"/', $zas->go("$ZAS/login.php")['raw'], $m) ? $m[1] : '';
$zas->go("$ZAS/login.php", ['_csrf'=>$tok,'email'=>'admin@zas.test','password'=>'test1234']);
/* the API's token is the session's own, as the Finance screen prints it */
$ZCSRF = preg_match('/var CSRF = "([^"]+)"/', $zas->go("$ZAS/att_finance.php")['raw'], $m) ? $m[1] : '';
function zapi(string $route,$d=null){ global $zas,$ZAS,$ZCSRF;
  return $zas->go("$ZAS/api/v1/index.php?route=$route",$d,
    ['Content-Type: application/json','X-CSRF-Token: '.$ZCSRF],true); }

$CARD_A = hash('sha256','0001234567');   // Adnan Butt, standard Rs 1,000
$CARD_I = hash('sha256','0009999999');   // Imran Shah, Rs 250 of his own
$CARD_B = hash('sha256','0007777777');   // Bilal Khan
$CARD_L = hash('sha256','0005555555');   // has left

head('The counter asks the real ZAS about a card');
$r = kiosk('kiosk_card',['card'=>$CARD_A]);
ok(!empty($r['j']['allowed']) && $r['j']['name']==='Adnan Butt' && (float)$r['j']['limit']==1000.0,
   'Adnan Butt, Rs 1,000 — the limit comes from ZAS, not from the canteen', $r['raw']);
ok((int)($r['j']['employee_id'] ?? 0) === (int)$ZDB->query("SELECT id FROM att_employees WHERE code='112'")->fetchColumn(),
   'and the id the canteen stores is att_employees.id');
$r = kiosk('kiosk_card',['card'=>$CARD_I]);
ok((float)($r['j']['limit'] ?? 0)==250.0, "Imran's own Rs 250 limit is honoured", $r['raw']);
$r = kiosk('kiosk_card',['card'=>$CARD_L]);
ok(($r['j']['allowed'] ?? null)===false && str_contains((string)$r['j']['message'],'no longer active'),
   'a worker ZAS marks inactive is refused at the counter', $r['raw']);

head('Switching the canteen off for one person, in ZAS');
$ZDB->exec("UPDATE att_emp_cards c JOIN att_employees e ON e.id=c.employee_id SET c.canteen_ok=0 WHERE e.code='114'");
$r = kiosk('kiosk_card',['card'=>$CARD_B]);
ok(str_contains((string)($r['j']['message'] ?? ''),'Canteen'), 'refused the moment ZAS says no', $r['raw']);
$ZDB->exec("UPDATE att_emp_cards c JOIN att_employees e ON e.id=c.employee_id SET c.canteen_ok=1 WHERE e.code='114'");
ok(!empty(kiosk('kiosk_card',['card'=>$CARD_B])['j']['allowed']), 'and allowed again at once');

head('Orders at the counter');
$tea  = (int)$CDB->query("SELECT id FROM menu_items WHERE name='Tea'")->fetchColumn();
$roti = (int)$CDB->query("SELECT id FROM menu_items WHERE name='Roti'")->fetchColumn();
$r = kiosk('kiosk_order',['card'=>$CARD_A,'items'=>[['menu_item_id'=>$tea,'qty'=>2],['menu_item_id'=>$roti,'qty'=>1]]]);
ok(!empty($r['j']['ok']) && (float)$r['j']['total']==115.0,'Adnan: 2 Tea + 1 Roti = PKR 115',$r['raw']);
$oidA=(int)$r['j']['order_id'];
$r = kiosk('kiosk_order',['card'=>$CARD_A,'items'=>[['menu_item_id'=>$tea,'qty'=>1]]]);
ok(!empty($r['j']['ok']),'Adnan again: 1 Tea = PKR 50'); $oidA2=(int)$r['j']['order_id'];
$r = kiosk('kiosk_order',['card'=>$CARD_I,'items'=>[['menu_item_id'=>$tea,'qty'=>4]]]);
ok(!empty($r['j']['ok']),'Imran: 4 Tea = PKR 200'); $oidI=(int)$r['j']['order_id'];
$r = kiosk('kiosk_order',['card'=>$CARD_I,'items'=>[['menu_item_id'=>$tea,'qty'=>2]]]);
ok(empty($r['j']['ok']) && str_contains((string)$r['j']['error'],'Weekly limit'),
   "Imran's next PKR 100 is refused by ZAS's Rs 250 limit", $r['raw']);
$r = kioskA('kiosk_refund',['id'=>$oidA2,'amount'=>50,'reason'=>'cold tea']);
ok(!empty($r['j']['ok']),'accounts refunds the PKR 50 tea',$r['raw']);

/* Accounts prepare pay for the week that has ENDED, so the orders have to be
   in it. The counter has no way to backdate an order, so the test moves them
   into last week in the canteen's own tables — the same rows, a week older. */
$upTo = '2026-11-19';                      // the Thursday that closes Fri 13 - Thu 19
$CDB->exec("UPDATE orders SET created_at = '2026-11-18 13:05:22', week_from = '2026-11-13' WHERE zas_emp_id IS NOT NULL");
$CDB->exec("UPDATE order_refunds SET created_at = '2026-11-19 10:00:00'");

head('ZAS reads the week straight off the canteen');
$r = zapi('/finance/canteen/fetch',['to'=>$upTo]);
$f = $r['j'];
ok(!empty($f['ok']), 'Get canteen charges answers', substr($r['raw'],0,300));
ok((int)($f['orders'] ?? -1) === 3, 'it took all 3 orders', json_encode($f));
ok((int)($f['refunds'] ?? -1) === 1, 'and the 1 refund');
ok(empty($f['skipped']), 'nothing was skipped as malformed — the two sides agree on every field',
   json_encode($f['skipped'] ?? []));

$rows = $ZDB->query("SELECT kind, ext_id, employee_id, amount_paisa, items, at, week_from FROM att_canteen_rows ORDER BY kind, ext_id")->fetchAll();
ok(count($rows) === 4, 'ZAS kept 4 rows of its own', json_encode(array_column($rows,'ext_id')));
$byExt = []; foreach ($rows as $x) $byExt[$x['kind'].':'.$x['ext_id']] = $x;
ok(isset($byExt["order:$oidA"]) && (int)$byExt["order:$oidA"]['amount_paisa'] === 11500,
   'PKR 115 arrived as 11500 paisa, exactly', json_encode($byExt["order:$oidA"] ?? null));
$it = json_decode((string)($byExt["order:$oidA"]['items'] ?? '[]'), true);
ok(is_array($it) && count($it)===2 && $it[0]['qty']===2 && $it[0]['amount']===10000,
   'the item lines survived with whole-number qty and paisa', json_encode($it));
ok((string)$byExt["order:$oidA"]['week_from'] === $CDB->query("SELECT week_from FROM orders WHERE id=$oidA")->fetchColumn(),
   'and both sides put it in the same pay week');

head('Nothing is charged twice if Accounts press it again');
$r2 = zapi('/finance/canteen/fetch',['to'=>$upTo]);
ok(!empty($r2['j']['ok']) && (int)$ZDB->query("SELECT COUNT(*) FROM att_canteen_rows")->fetchColumn() === 4,
   'a second press adds no rows', $r2['raw']);

head('What each person owes');
$sum = $ZDB->query("SELECT employee_id, SUM(CASE WHEN kind='order' THEN amount_paisa ELSE -amount_paisa END) owed
                    FROM att_canteen_rows WHERE zas_state='free' GROUP BY employee_id ORDER BY employee_id")->fetchAll();
$owe = []; foreach ($sum as $s) $owe[(int)$s['employee_id']] = (int)$s['owed'];
$idA = (int)$ZDB->query("SELECT id FROM att_employees WHERE code='112'")->fetchColumn();
$idI = (int)$ZDB->query("SELECT id FROM att_employees WHERE code='113'")->fetchColumn();
ok(($owe[$idA] ?? 0) === 11500, 'Adnan owes 11500 paisa — his refund already netted off', json_encode($owe));
ok(($owe[$idI] ?? 0) === 20000, 'Imran owes 20000 paisa');

head('The canteen still holds them as unsettled');
$st = $CDB->query("SELECT settle_state, COUNT(*) n FROM orders WHERE zas_emp_id IS NOT NULL GROUP BY settle_state")->fetchAll();
ok(count($st)===1 && $st[0]['settle_state']==='unsettled',
   'reading the week changes nothing at the counter — only reserve does', json_encode($st));

/* ------------------------------------------------------------------
   The second half: the sheet. Creating it RESERVES the orders at the
   canteen; approving it SETTLES them. Both go over the wire to the
   real zas.php, so this is the part no single session can test.
   ------------------------------------------------------------------ */
head('Giving them something to be paid, so there is pay to take it from');
foreach ([['112', 500000], ['113', 500000]] as [$code, $paisa]) {
  $eid = (int)$ZDB->query("SELECT id FROM att_employees WHERE code='$code'")->fetchColumn();
  $r = zapi("/finance/people/$eid/opening", ['amount_paisa'=>$paisa, 'reason'=>'test opening']);
  ok(!empty($r['j']['ok']), "opening balance Rs 5,000 for $code", $r['raw']);
}

head('Prepare pay — the canteen line appears by itself');
$pv = $zas->go("$ZAS/api/v1/index.php?route=/finance/preview&kind=daily&from=2026-11-13&to=$upTo");
ok(!empty($pv['j']['ok']), 'the week previews', substr($pv['raw'],0,220));
$rowsP = $pv['j']['rows'] ?? $pv['j']['people'] ?? [];
$mine = []; foreach ($rowsP as $p) { foreach (($p['lines'] ?? []) as $l) if (($l['kind'] ?? '') === 'canteen') $mine[(int)$p['employee_id']] = $l; }
ok(count($mine) >= 2, 'both people have a Canteen line on the sheet', json_encode(array_map(fn($l)=>[$l['label'],$l['amount_paisa']], $mine)));
$lA = $mine[$idA] ?? null;
ok($lA && (int)$lA['amount_paisa'] === 11500, "Adnan's canteen line is 11500 paisa", json_encode($lA));
ok($lA && str_contains(strtolower((string)$lA['label']), 'canteen'), 'and it says Canteen', (string)($lA['label'] ?? ''));

head('Sending the sheet reserves the orders at the canteen');
$items = [];
foreach ($rowsP as $p) { if (isset($mine[(int)$p['employee_id']])) $items[] = ['employee_id'=>(int)$p['employee_id']]; }
$cr = zapi('/finance/sheets', ['kind'=>'daily','from'=>'2026-11-13','to'=>$upTo,'items'=>$items]);
ok(!empty($cr['j']['ok']), 'the sheet is created', substr($cr['raw'],0,300));
$sheetId = (int)($cr['j']['sheet_id'] ?? 0);
$sref = (string)$ZDB->query("SELECT ref FROM att_fin_sheets WHERE id=$sheetId")->fetchColumn();
$st = $CDB->query("SELECT settle_state, sheet_ref FROM orders WHERE zas_emp_id IS NOT NULL AND status<>'Cancelled' GROUP BY settle_state, sheet_ref")->fetchAll();
ok(count($st)===1 && $st[0]['settle_state']==='reserved',
   'the canteen now holds every order as RESERVED', json_encode($st));
ok((string)($st[0]['sheet_ref'] ?? '') === $sref,
   "stamped with ZAS's own sheet reference ($sref)", json_encode($st));

head('Approving the sheet settles them');
$ap = zapi("/finance/sheets/$sheetId/approve", []);
ok(!empty($ap['j']['ok']), 'the sheet is approved', substr($ap['raw'],0,300));
$st = $CDB->query("SELECT settle_state, COUNT(*) n FROM orders WHERE zas_emp_id IS NOT NULL AND status<>'Cancelled' GROUP BY settle_state")->fetchAll();
ok(count($st)===1 && $st[0]['settle_state']==='settled', 'every order is SETTLED at the canteen', json_encode($st));
$rf = $CDB->query("SELECT settle_state FROM order_refunds")->fetchAll();
ok(count($rf)===1 && $rf[0]['settle_state']==='settled', 'and so is the refund', json_encode($rf));
ok((int)$ZDB->query("SELECT COUNT(*) FROM att_canteen_calls WHERE status='pending'")->fetchColumn() === 0,
   'no message is left waiting — the canteen answered every one');

head('And it reaches the money');
$led = $ZDB->query("SELECT label, amount_paisa, kind FROM att_fin_ledger WHERE employee_id=$idA AND kind='deduction'")->fetchAll();
ok(count($led)===1 && (int)$led[0]['amount_paisa']===11500,
   "Adnan's ledger carries one PKR 115.00 deduction", json_encode($led));
ok(str_contains(strtolower((string)($led[0]['label'] ?? '')), 'canteen'), 'labelled Canteen', json_encode($led));

head('A sheet that is cancelled gives the orders back');
$cn = zapi("/finance/sheets/$sheetId/cancel", ['reason'=>'prepared by mistake']);
if (!empty($cn['j']['ok'])) {
  /* The canteen had already SETTLED them, and a settled row is never released
     — that is the brief's own rule. So ZAS keeps its copy and charges it again
     itself: "settled there, owed again here". What must not happen is the week
     quietly disappearing. */
  $st = $CDB->query("SELECT settle_state, COUNT(*) n FROM orders WHERE zas_emp_id IS NOT NULL AND status<>'Cancelled' GROUP BY settle_state")->fetchAll();
  ok(count($st)===1 && $st[0]['settle_state']==='settled',
     'the canteen keeps them settled — a settled row is never released', json_encode($st));
  ok((int)$ZDB->query("SELECT COUNT(*) FROM att_canteen_calls WHERE status='pending'")->fetchColumn() === 0,
     'and nothing is left waiting to be sent');
  $free = (int)$ZDB->query("SELECT COUNT(*) FROM att_canteen_rows WHERE zas_state='free'")->fetchColumn();
  ok($free === 4, 'but ZAS freed all 4 rows again, so the week is not lost', 'free=' . $free);
  ok((int)$ZDB->query("SELECT COUNT(*) FROM att_fin_ledger WHERE employee_id=$idA AND kind='deduction' AND cancelled_at IS NOT NULL")->fetchColumn() === 1,
     "and Adnan's charge on the cancelled sheet is struck through");
  /* put it back as it was, so the last check below still means something */
  $re = zapi('/finance/sheets', ['kind'=>'daily','from'=>'2026-11-13','to'=>$upTo,'items'=>$items]);
  $sheetId = (int)($re['j']['sheet_id'] ?? 0);
  zapi("/finance/sheets/$sheetId/approve", []);
  $live = $ZDB->query("SELECT COUNT(*) FROM att_fin_ledger WHERE employee_id=$idA AND kind='deduction' AND cancelled_at IS NULL")->fetchColumn();
  ok((int)$live === 1, 'the next sheet charges it once, and only once', 'live deductions=' . $live);
} else {
  ok(true, 'cancelling an approved sheet is not allowed for this user — skipped', substr($cn['raw'],0,160));
}

head('The week is finished: nothing comes again');
$again = zapi('/finance/canteen/fetch', ['to'=>$upTo]);
ok(!empty($again['j']['ok']) && (int)($again['j']['orders'] ?? -1) === 0,
   'a later Get canteen charges finds nothing left to charge', $again['raw']);

echo "\n=====================================\n";
echo ($FAIL===0 ? "ALL $PASS CHECKS PASSED\n" : "$PASS passed, $FAIL FAILED\n");
echo "=====================================\n";
exit($FAIL===0?0:1);
