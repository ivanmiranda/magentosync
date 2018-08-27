<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/catchErrors.php';
define('PATH_ROOT', __DIR__);
$app = new Sincco\Sfphp\App;
$app->run();