<?php

namespace GitSync\Provider;

class RootControllerProvider implements \Silex\ControllerProviderInterface
{

    public function connect(\Silex\Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['root.controller'] = $app->share(function() use ($app) {
            return new \GitSync\Controller\Root($app);
        });

        $controllers->get('/', 'root.controller:index')->bind('root');
        $app['acl']
            ->allowAll('root')
        ;
        return $controllers;
    }
}