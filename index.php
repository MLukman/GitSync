<?php
require_once __DIR__.'/vendor/autoload.php';

$config = new \GitSync\Config();
$config->addContext('GitSync',
    new \GitSync\Context(__DIR__,
    'https://github.com/MLukman/GitSync'));

$app = new \GitSync\Application($config);

$app['debug'] = true;
$app->run();
