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

$security = new \GitSync\Security\LdapSecurityProvider("10.41.86.223", 389,
    "uid={username},ou=People,o=Telekom");
$security->userProvider->createUser(new \Symfony\Component\Security\Core\User\User('S52514',
    null, array('ROLE_ADMIN')));
//$app->addSecurityProvider('ldap', $security);

$app['debug'] = true;
$app->run();
