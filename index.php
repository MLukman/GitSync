<?php
define('AUTOLOAD', __DIR__.'/vendor/autoload.php');
if (!file_exists(AUTOLOAD)) {
    include __DIR__.'/composer/install.php';
} else {
    require_once AUTOLOAD;
}

$config = new \GitSync\Config();
$config->addContext('GitSync',
    new \GitSync\Context(__DIR__, 'https://github.com/MLukman/GitSync',
    'with-vendor'));

$app = new \GitSync\Application($config);

$app['debug'] = true;
$app->run();
