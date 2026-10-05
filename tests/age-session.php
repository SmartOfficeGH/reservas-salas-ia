<?php
declare(strict_types=1);
// Modifica únicamente una sesión de pruebas para comprobar su caducidad sin esperar.
$config=require __DIR__.'/../private/config.php';
if($config['environment']!=='local'||!str_ends_with($config['db']['name'],'_test'))exit(2);
$id=$argv[1]??'';$kind=$argv[2]??'';
if(!preg_match('/^[A-Za-z0-9,-]{16,128}$/D',$id)||!in_array($kind,['idle','absolute'],true))exit(2);
session_save_path(__DIR__.'/../private/storage/sessions');
if(!is_file(session_save_path().'/sess_'.$id))exit(2);
session_id($id);session_start();
if(!isset($_SESSION['uid']))exit(2);
if($kind==='idle')$_SESSION['last_seen']=time()-1801;
else {$_SESSION['started']=time()-28801;$_SESSION['last_seen']=time();}
session_write_close();
