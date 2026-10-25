<?php
$vendor=getenv('FLARUM_VENDOR_DIR') ?: __DIR__.'/../../../../vendor';
require $vendor.'/autoload.php';
spl_autoload_register(static function(string $class):void { $prefix='EdgeInfinity\\Dice\\';if(str_starts_with($class,$prefix)) require __DIR__.'/../src/'.substr($class,strlen($prefix)).'.php'; });
