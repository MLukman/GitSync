<?php
( @include_once __DIR__.'/vendor/autoload.php' )
    or ( @include __DIR__.'/composer/install.php' )
    or die("Please run 'composer install' from command line interface");

// create new context
$context = new \GitSync\Context(__DIR__, 'https://github.com/MLukman/GitSync');

// create a new config
$config = new \GitSync\Config();

// add the context to the config
$config->addContext($context);

// create a new application
$app = new \GitSync\Application($config);

// create a new security provider
$security = new GitSync\Security\SimpleSecurityProvider();

// user with ROLE_ADMIN implicitly gets access to all contexts
$security->addUser('admin', 'admin', array('ROLE_ADMIN'));

// user with ROLE_USER needs to be given explicit access to specific contexts
$security->addUser('user01', 'user01', array('ROLE_USER'));

// ditto
$security->addUser('user02', 'user02', array('ROLE_USER'));

// Add user01 & user02 to the list of user id allowed access
$context->addAllowedUid('user01')->addAllowedUid('user02');

// add the security provider to the application
$app->addSecurityProvider($security, 'simple');

// debug mode
$app['debug'] = true;

// let it run!
$app->run();
