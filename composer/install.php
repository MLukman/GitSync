<?php
define('EXTRACT_DIRECTORY', __DIR__."/extractedComposer");

ini_set('max_execution_time', 600);
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

print "<pre>Installing GitSync dependencies for the first time ...\n\n";
flush();

if (!file_exists(EXTRACT_DIRECTORY.'/vendor/autoload.php') == true) {
    if (ini_get('phar.readonly')) {
        print "Error: unable to proceed. Please set phar.readonly = 0 in php.ini and then reload this page.</pre>";
        exit;
    }
    $composerPhar = new Phar(__DIR__."/composer.phar");
    $composerPhar->extractTo(EXTRACT_DIRECTORY);
}

//This requires the phar to have been extracted successfully.
require_once (EXTRACT_DIRECTORY.'/vendor/autoload.php');

//Use the Composer classes
use Composer\Console\Application;

//Create the commands
$input  = new Symfony\Component\Console\Input\StringInput('install');
$output = new Symfony\Component\Console\Output\StreamOutput(fopen('php://output',
        'w'));

//Create the application and run it with the commands
$application = new Application();
$application->add(new Composer\Command\InstallCommand());
$application->setAutoExit(false);
$application->run($input, $output);

print "Cleaning up ...\n\n";
flush();
$fs = new \Symfony\Component\Filesystem\Filesystem();
$fs->remove(EXTRACT_DIRECTORY);

print "Done. Please reload this page.</pre>";
flush();
exit;
