<?php

namespace GitSync\Provider;

class ConfigControllerProvider implements \Silex\ControllerProviderInterface
{

    public function connect(\Silex\Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['config.controller'] = $app->share(function() use ($app) {
            return new \GitSync\Controller\Config($app);
        });

        $app['setup.controller'] = $app->share(function() use ($app) {
            return new \GitSync\Controller\Setup($app);
        });

        $controllers->get('/', 'config.controller:contexts')->bind('config_contexts');
        $controllers->get('/add', 'config.controller:contextAdd')->bind('config_context_add');
        $controllers->post('/add', 'config.controller:contextAdd')->bind('config_context_add_post');
        $controllers->get('/setup', 'setup.controller:setup')->bind('config_setup');
        $controllers->post('/setup', 'setup.controller:setup')->bind('config_setup_post');
        $controllers->get('/users', 'config.controller:users')->bind('config_users');
        $controllers->get('/users/add', 'config.controller:userAdd')->bind('config_user_add');

        return $controllers;
    }
}