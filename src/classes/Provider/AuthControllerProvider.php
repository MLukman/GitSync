<?php

namespace GitSync\Provider;

class AuthControllerProvider implements \Silex\ControllerProviderInterface
{

    public function connect(\Silex\Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['auth.controller'] = $app->share(function() use ($app) {
            return new \GitSync\Controller\Auth($app);
        });

        $controllers->get('/login', 'auth.controller:login')->bind('login');

        return $controllers;
    }
}