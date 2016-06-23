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
        $controllers->get('/ctx/{ctxid}/sync/{ref}',
            'context.controller:presync')->bind('context_presync');
        $controllers->post('/ctx/{ctxid}/sync/{ref}',
            'context.controller:dosync')->bind('context_dosync');
        $controllers->get('/ctx/{ctxid}/refresh/', 'context.controller:refresh')->bind('context_refresh');
        $controllers->get('/ctx/{ctxid}/', 'context.controller:details')->bind('context_details');
        $controllers->get('/refresh/', 'context.controller:refreshAll')->bind('context_refresh_all');
        $controllers->get('/', 'context.controller:index')->bind('context_index');

        return $controllers;
    }
}