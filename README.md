![GitSync logo](ui/gitsync.png)

[![Packagist Version](https://img.shields.io/packagist/v/mlukman/gitsync.svg)](https://packagist.org/packages/mlukman/gitsync) [![Packagist License](https://img.shields.io/packagist/l/mlukman/gitsync.svg)](https://packagist.org/packages/mlukman/gitsync) [![GitHub issues](https://img.shields.io/github/issues/MLukman/GitSync.svg?maxAge=2592000)](https://github.com/MLukman/GitSync)

# Introduction

GitSync is a PHP tool that will sync any directory on your server with a Git repository. It provides GUI for server admins to synchronize a directory with any commit (i.e. revision) of the source code with a click of a button.

# Installation

## Pre-requisites

1. PHP 5.4 and above running on Apache2.
2. Git already installed and added to the PATH on your machine.
3. Composer already installed and added to the PATH on your machine.

## Using Git

Execute the following commands:

    mkdir /path/to/GitSync
    cd /path/to/GitSync
    git clone https://github.com/MLukman/GitSync .
	composer install

Then, customize `index.php` to match your directories. Refer the section on 'Customization' below.

## Using composer

Execute the following commands:

    mkdir /path/to/GitSync
    cd /path/to/GitSync
    composer require mlukman/gitsync

Then, either copy `index.php` from folder `vendor/mlukman/gitsync/` or create your own file using the following section on 'Customization'.

# Customization

## Basic Setup
At the minimum, `index.php` is the only file that you need to modify in order to customize your installation of GitSync.

First, the file must have the following file at or near the beginning of the file:

	require __DIR__.'/vendor/autoload.php';

Next, you need to instantiate a `\GitSync\Config` object:

	$config = new \GitSync\Config();

To add a directory to the list of directories that GitSync manages, instantiate a `\GitSync\Context` object and add it to the `$config` object using `addContext()` method:

	$context01 = new \GitSync\Context('\path\to\directory', 'http://remote.url/repo.git', 'branchname');
    $config->addContext($context01);

Repeat for as many directories as you want.

Finally, instantiate a `\GitSync\Application` object while passing the `$config` object and let it run.

	$app = new \GitSync\Application($config);
	$app->run();

That's the basic working setup.

## Secure Setup

Of course, GitSync without security is like begging to be hacked, so GitSync utilizes [Securilex](https://github.com/MLukman/Securilex) security module. Refer Securilex's [README](https://github.com/MLukman/Securilex/blob/master/README.md) for detailed usage of the module.

To enable secure mode, you just have to call `\GitSync\Application::activateSecurity` method, passing an instance of `\Securilex\DriverInterface` to the method.

### `\Securilex\Driver\SimpleDriver`

	// create a new driver
    $driver = new \Securilex\Driver\SimpleDriver();

    // user with ROLE_ADMIN implicitly gets access to all contexts
    $driver->addUser('admin', 'admin', array('ROLE_ADMIN'));

    // user with ROLE_USER needs to be given explicit access to specific contexts
    $driver->addUser('user01', 'user01', array('ROLE_USER'));

    // ditto
    $driver->addUser('user02', 'user02', array('ROLE_USER'));

    // Add user01 & user02 to the list of user id allowed access
    $context->addAllowedUid('user01')->addAllowedUid('user02');

    // Activate security using the driver
    $app->activateSecurity($driver);

### `\Securilex\Driver\LdapDriver`

Using LdapSecurityProvider is similar but you need to provide the host, port and distinguised name (DN) string:

	// create a new driver
	$driver = new \Securilex\Driver\LdapDriver("ldap.mycompany.com", 389, "uid={username},ou=People,o=MyCompany");

	// No password needed when adding user to LdapSecurityProvider 
	$driver->addUser('JohnDoe', array('ROLE_ADMIN'));

    // Activate security using the driver
    $app->activateSecurity($driver);

### Powered by:

[![Silex logo](ui/logo-silex.png)](http://silex.sensiolabs.org)

[![GitElephant logo](https://github.com/matteosister/GitElephant/raw/1.1.0/graphics/gitelephant_600.png)](http://gitelephant.cypresslab.net/)

