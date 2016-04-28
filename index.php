<?php
(@include_once __DIR__.'/vendor/autoload.php')
    or ( @include __DIR__.'/composer/install.php')
    or die("Please run 'composer install' from command line interface");

$config = new \GitSync\Config();
$config->addContext(new \GitSync\Context(__DIR__,
    'https://github.com/MLukman/GitSync'));

$app = new \GitSync\Application($config);

$app['debug'] = true;
$app->run();
