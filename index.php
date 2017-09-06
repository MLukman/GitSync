<?php
( @include_once __DIR__.'/vendor/autoload.php' )
    or ( @include __DIR__.'/composer/install.php' )
    or die("Please run 'composer install' from command line interface");

GitSync\Application::execute(true);
