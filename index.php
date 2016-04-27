<?php
define('AUTOLOAD', __DIR__.'/vendor/autoload.php');
if (!file_exists(AUTOLOAD)) {
    include __DIR__.'/composer/install.php' ||
        die("Please run 'composer install' from command line interface");
} else {
    require_once AUTOLOAD;
}

$config = new \GitSync\Config();
$config->addContext(new \GitSync\Context(__DIR__,
    'https://github.com/MLukman/GitSync'));

$app = new \GitSync\Application($config);

$app['debug'] = true;
$app->run();
