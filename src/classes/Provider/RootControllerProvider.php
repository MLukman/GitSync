<?php

namespace GitSync\Provider;

class RootControllerProvider implements \Silex\ControllerProviderInterface
{

    public function connect(\Silex\Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['context.controller'] = $app->share(function() use ($app) {
            return new \GitSync\Controller\Context($app);
        });

        $controllers->get('/ctx/{ctxid}/init/', 'context.controller:init')->bind('context_init');
        $controllers->get('/ctx/{ctxid}/checkout/{ref}',
            'context.controller:checkout')->bind('context_checkout');
        $controllers->get('/ctx/{ctxid}/', 'context.controller:details')->bind('context_details');
        $controllers->get('/refresh/', 'context.controller:refresh')->bind('context_refresh');
        $controllers->get('/', 'context.controller:index')->bind('context_index');

        return $controllers;
    }
}