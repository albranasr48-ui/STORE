<?php
// عدّل بيانات الاتصال بقاعدة البيانات
const DB_HOST='localhost',DB_NAME='restaurant',DB_USER='root',DB_PASS='';
const PERMS=[
 'cashier'=>['orders_view','orders','pay','reports','tables'],
 'kitchen'=>['orders_view','kitchen'],
 'reception'=>['orders_view','orders','tables']];
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
session_start();
function db(){static $p;return $p??=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}
function out($d,$c=200){http_response_code($c);header('Content-Type: application/json; charset=utf-8');echo json_encode($d,JSON_UNESCAPED_UNICODE);exit;}
function has($p){$r=$_SESSION['u']['role']??'';return $r==='admin'||in_array($p,PERMS[$r]??[]);}
function need($perm){if(empty($_SESSION['u']))out(['error'=>'auth'],401);foreach(explode('|',$perm) as $p)if(has($p))return;out(['error'=>'ليست لديك صلاحية'],403);}
function me(){if(empty($_SESSION['u']))return new stdClass;$u=$_SESSION['u'];$u['perms']=$u['role']==='admin'?['*']:PERMS[$u['role']]??[];return $u;}
