<?php
require __DIR__.'/config.php';
$a=$_GET['a']??'';$in=json_decode(file_get_contents('php://input'),true)?:[];$db=db();
function sv($in,$k,$n=255){return mb_substr(trim((string)($in[$k]??'')),0,$n);}
function rows($sql,$pa=[]){$s=db()->prepare($sql);$s->execute($pa);return $s->fetchAll();}
function save($tbl,$cols,$in,$vals){
 $db=db();
 if(!empty($in['id'])){$db->prepare("UPDATE $tbl SET ".implode('=?,',$cols)."=? WHERE id=?")->execute([...$vals,(int)$in['id']]);}
 else{$db->prepare("INSERT INTO $tbl(".implode(',',$cols).") VALUES(".implode(',',array_fill(0,count($cols),'?')).")")->execute($vals);}
 out(['ok'=>1]);}
try{switch($a){
case 'menu':
 $c=rows('SELECT id,name FROM categories ORDER BY sort,id');
 foreach($c as &$x)$x['items']=rows('SELECT id,name,descr,price,image FROM items WHERE cat_id=? AND active=1 ORDER BY id',[$x['id']]);
 unset($x);
 out(['cats'=>array_values(array_filter($c,fn($x)=>$x['items'])),'tables'=>array_map('intval',$db->query('SELECT num FROM rtables ORDER BY num')->fetchAll(PDO::FETCH_COLUMN))]);
case 'order_create':
 $tn=(int)($in['table']??0);
 if(!rows('SELECT 1 FROM rtables WHERE num=?',[$tn]))out(['error'=>'رقم الطاولة غير صحيح'],400);
 $tot=0;$ls=[];
 foreach(array_slice($in['items']??[],0,60) as $l){$q=(int)($l['qty']??0);if($q<1||$q>50)continue;
  if($r=rows('SELECT name,price FROM items WHERE id=? AND active=1',[(int)($l['id']??0)])){$r=$r[0];$ls[]=[$r['name'],$r['price'],$q];$tot+=$r['price']*$q;}}
 if(!$ls)out(['error'=>'السلة فارغة'],400);
 $tk=bin2hex(random_bytes(8));
 $db->beginTransaction();
 $db->prepare('INSERT INTO orders(token,table_num,note,total) VALUES(?,?,?,?)')->execute([$tk,$tn,sv($in,'note',500),$tot]);
 $id=$db->lastInsertId();
 foreach($ls as $l)$db->prepare('INSERT INTO order_items(order_id,name,price,qty) VALUES(?,?,?,?)')->execute([$id,...$l]);
 $db->commit();out(['id'=>(int)$id,'token'=>$tk]);
case 'order_status':
 $r=rows('SELECT id,status,reject_reason,table_num,total FROM orders WHERE id=? AND token=?',[(int)($_GET['id']??0),$_GET['t']??'']);
 out($r?$r[0]:['error'=>'غير موجود'],$r?200:404);
case 'login':
 $u=rows('SELECT * FROM users WHERE username=? AND active=1',[sv($in,'username',50)]);
 if(!$u||!password_verify((string)($in['password']??''),$u[0]['pass'])){sleep(1);out(['error'=>'بيانات غير صحيحة'],400);}
 session_regenerate_id(true);$_SESSION['u']=['id'=>$u[0]['id'],'name'=>$u[0]['name'],'role'=>$u[0]['role']];out(me());
case 'me':out(me());
case 'logout':session_destroy();out(['ok'=>1]);
case 'orders':
 need('orders_view');
 $o=rows('SELECT * FROM orders ORDER BY id DESC LIMIT 300');$by=[];
 foreach($o as $i=>$r)$by[$r['id']]=$i;
 if($o){foreach(rows('SELECT * FROM order_items WHERE order_id IN ('.implode(',',array_map('intval',array_keys($by))).')') as $it)$o[$by[$it['order_id']]]['items'][]=$it;}
 out($o);
case 'order_set':
 $m=['review'=>'orders','accepted'=>'orders','preparing'=>'kitchen|orders','rejected'=>'orders','ready'=>'kitchen|orders','awaiting_payment'=>'pay|orders','paid'=>'pay','closed'=>'pay|orders'];
 $st=sv($in,'status',20);if(!isset($m[$st]))out(['error'=>'حالة غير صحيحة'],400);
 need($m[$st]);
 $me=in_array($in['method']??'',['cash','card','transfer','online','other'])?$in['method']:null;
 if($st==='paid'&&!$me)out(['error'=>'اختر طريقة الدفع'],400);
 $db->prepare('UPDATE orders SET status=?,reject_reason=IF(?="rejected",?,reject_reason),pay_method=IF(?="paid",?,pay_method),paid_at=IF(?="paid",NOW(),paid_at) WHERE id=?')->execute([$st,$st,sv($in,'reason'),$st,$me,$st,(int)($in['id']??0)]);
 out(['ok'=>1]);
case 'menu_all':need('menu');out(['cats'=>rows('SELECT * FROM categories ORDER BY sort,id'),'items'=>rows('SELECT * FROM items ORDER BY id')]);
case 'cat_save':need('menu');$n=sv($in,'name',80);if(!$n)out(['error'=>'الاسم مطلوب'],400);save('categories',['name'],$in,[$n]);
case 'cat_del':need('menu');$db->prepare('DELETE FROM categories WHERE id=?')->execute([(int)$in['id']]);out(['ok'=>1]);
case 'item_save':need('menu');
 $p=(float)($in['price']??-1);$n=sv($in,'name',120);if(!$n||$p<0||!rows('SELECT 1 FROM categories WHERE id=?',[(int)($in['cat_id']??0)]))out(['error'=>'تحقق من الاسم والسعر والقسم'],400);
 save('items',['cat_id','name','descr','price','image','active'],$in,[(int)$in['cat_id'],$n,sv($in,'descr',500),$p,sv($in,'image',500),empty($in['active'])?0:1]);
case 'item_del':need('menu');$db->prepare('DELETE FROM items WHERE id=?')->execute([(int)$in['id']]);out(['ok'=>1]);
case 'tables':need('orders_view');out(array_map('intval',$db->query('SELECT num FROM rtables ORDER BY num')->fetchAll(PDO::FETCH_COLUMN)));
case 'table_add':need('tables');$n=(int)($in['num']??0);if($n<1||$n>999)out(['error'=>'رقم غير صحيح'],400);$db->prepare('INSERT IGNORE INTO rtables VALUES(?)')->execute([$n]);out(['ok'=>1]);
case 'table_del':need('tables');$db->prepare('DELETE FROM rtables WHERE num=?')->execute([(int)$in['num']]);out(['ok'=>1]);
case 'users':need('users');out(rows('SELECT id,username,name,role,active FROM users ORDER BY id'));
case 'user_save':need('users');
 $un=sv($in,'username',50);$nm=sv($in,'name',80);$ro=sv($in,'role',20);$pw=(string)($in['password']??'');
 if(!preg_match('/^[A-Za-z0-9_.-]{3,50}$/',$un)||!$nm||!in_array($ro,['admin','cashier','kitchen','reception']))out(['error'=>'تحقق من البيانات'],400);
 if(empty($in['id'])&&strlen($pw)<6)out(['error'=>'كلمة المرور 6 أحرف على الأقل'],400);
 try{
  if(!empty($in['id'])){$db->prepare('UPDATE users SET username=?,name=?,role=?,active=? WHERE id=?')->execute([$un,$nm,$ro,empty($in['active'])?0:1,(int)$in['id']]);
   if($pw!=='')$db->prepare('UPDATE users SET pass=? WHERE id=?')->execute([password_hash($pw,PASSWORD_DEFAULT),(int)$in['id']]);}
  else $db->prepare('INSERT INTO users(username,name,pass,role) VALUES(?,?,?,?)')->execute([$un,$nm,password_hash($pw,PASSWORD_DEFAULT),$ro]);
 }catch(PDOException $e){out(['error'=>'اسم المستخدم مستخدم مسبقًا'],400);}
 out(['ok'=>1]);
case 'user_del':need('users');if((int)$in['id']===$_SESSION['u']['id'])out(['error'=>'لا يمكن حذف حسابك'],400);$db->prepare('DELETE FROM users WHERE id=?')->execute([(int)$in['id']]);out(['ok'=>1]);
case 'report':
 need('reports');$d=fn($k)=>preg_match('/^\d{4}-\d\d-\d\d$/',$_GET[$k]??'')?$_GET[$k]:date('Y-m-d');
 $p=[$d('from').' 00:00:00',$d('to').' 23:59:59'];$sum=fn($s)=>rows("SELECT COALESCE(SUM(total),0) v FROM orders WHERE pay_method IS NOT NULL AND paid_at>=$s")[0]['v'];
 out(['today'=>$sum('CURDATE()'),'week'=>$sum('CURDATE()-INTERVAL 6 DAY'),'month'=>$sum('DATE_FORMAT(NOW(),"%Y-%m-01")'),
  'status'=>rows('SELECT status k,COUNT(*) c FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY status',$p),
  'methods'=>rows('SELECT pay_method k,COUNT(*) c,SUM(total) s FROM orders WHERE pay_method IS NOT NULL AND paid_at BETWEEN ? AND ? GROUP BY pay_method',$p),
  'top'=>rows('SELECT oi.name k,SUM(oi.qty) c FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status<>"rejected" AND o.created_at BETWEEN ? AND ? GROUP BY oi.name ORDER BY c DESC LIMIT 10',$p),
  'tables'=>rows('SELECT table_num k,COUNT(*) c,SUM(total) s FROM orders WHERE pay_method IS NOT NULL AND paid_at BETWEEN ? AND ? GROUP BY table_num ORDER BY table_num',$p),
  'days'=>rows('SELECT DATE(paid_at) k,COUNT(*) c,SUM(total) s FROM orders WHERE pay_method IS NOT NULL AND paid_at BETWEEN ? AND ? GROUP BY DATE(paid_at) ORDER BY k',$p)]);
default:out(['error'=>'غير موجود'],404);
}}catch(Throwable $e){error_log($e);out(['error'=>'خطأ في الخادم'],500);}
