<?php

namespace GitSync\Provider;

class RepoControllerProvider implements \Silex\ControllerProviderInterface
{

    public function connect(\Silex\Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['repo.controller'] = $app->share(function() use ($app) {
            return new \GitSync\Controller\Repo($app);
        });

        $controllers->get('/{repoid}/init/', 'repo.controller:init')->bind('repo_init');
        $controllers->get('/{repoid}/checkout/{ref}', 'repo.controller:checkout')->bind('repo_checkout');
        $controllers->get('/{repoid}/', 'repo.controller:details')->bind('repo_details');
        $controllers->get('/', 'repo.controller:index')->bind('repo_index');

        $app['acl']
            ->allowAll('repo_')
        ;
        return $controllers;
    }
}