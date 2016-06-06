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

// To add security, first instantiate a user provider which provides the list of users to authenticate from
// The simplest is InMemoryUserProvider which we can use to hard-code the users
$userProvider = new \Symfony\Component\Security\Core\User\InMemoryUserProvider();

// Create users
// ROLE_ADMIN can access all contexts
$userProvider->createUser(new \Symfony\Component\Security\Core\User\User('admin',
    'admin123', array('ROLE_ADMIN')));

// Or for fine-grained access control, give it ROLE_USER
$userProvider->createUser(new \Symfony\Component\Security\Core\User\User('user01',
    'user01', array('ROLE_USER')));
// and specifically allow it to access each context
$context->addAllowedUsername('user01');

// Then instantiate an authentication factory that can authenticate the users
// For plain text passwords, you can use
$authFactory = new \Securilex\Authentication\Factory\PlaintextPasswordAuthenticationFactory();

// Finally, activate security by passing both the authentication factory and user provider
$app->activateSecurity($authFactory, $userProvider);

// debug mode
$app['debug'] = true;

// let it run!
$app->run();
