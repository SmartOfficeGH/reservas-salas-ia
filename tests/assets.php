<?php
declare(strict_types=1);
require __DIR__.'/../private/app/bootstrap.php';
$root=sys_get_temp_dir().'/reservas-assets-'.bin2hex(random_bytes(8));mkdir($root);
$original=$_SERVER['SCRIPT_FILENAME'];$n=0;
function assetCheck(bool $ok):void{global $n;if(!$ok)throw new RuntimeException('Asset version check failed');$n++;}
try {
    $_SERVER['SCRIPT_FILENAME']=$root.'/index.php';
    foreach(['estilos.css','corporativo.css','app.js','compartir.js'] as $name){
        file_put_contents($root.'/'.$name,'contenido inicial');$before=assetUrl($name);
        assetCheck($before===$name.'?v='.hash('sha256','contenido inicial'));
        assetCheck(assetUrl($name)===$before);
        file_put_contents($root.'/'.$name,'contenido actualizado');assetCheck(assetUrl($name)!==$before);
    }
    try{assetUrl('../config.php');throw new LogicException('Unexpected asset');}catch(RuntimeException){assetCheck(true);}
    unlink($root.'/app.js');try{assetUrl('app.js');throw new LogicException('Missing asset accepted');}catch(RuntimeException){assetCheck(true);}
    echo "ASSETS: $n comprobaciones de hash estable, cambio de contenido y recursos permitidos.\n";
} finally {$_SERVER['SCRIPT_FILENAME']=$original;foreach(glob($root.'/*') as $f)unlink($f);rmdir($root);}
