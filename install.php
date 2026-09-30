<?php
// شغّله مرة واحدة من المتصفح ثم احذفه
require __DIR__.'/api/config.php';$db=db();
foreach(array_filter(array_map('trim',explode(';',file_get_contents(__DIR__.'/database/schema.sql')))) as $q)$db->exec($q);
header('Content-Type: text/html; charset=utf-8');
if($db->query('SELECT COUNT(*) FROM users')->fetchColumn())die('النظام مثبّت مسبقًا. احذف install.php');
$db->prepare('INSERT INTO users(username,name,pass,role) VALUES("admin","المدير",?,"admin")')->execute([password_hash('admin123',PASSWORD_DEFAULT)]);
foreach(['الوجبات','البرجر','البيتزا','المشروبات','الحلويات','إضافات'] as $i=>$n)$db->prepare('INSERT INTO categories(name,sort) VALUES(?,?)')->execute([$n,$i]);
for($i=1;$i<=20;$i++)$db->exec("INSERT INTO rtables VALUES($i)");
echo 'تم التثبيت. الدخول: 123— غيّر كلمة المرور فورًا واحذف هذا الملف.';
