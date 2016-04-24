<?php

namespace GitSync;

use Symfony\Component\HttpFoundation\Request;

class Application extends \Silex\Application
{

    use \Silex\Application\UrlGeneratorTrait;
    protected $config = null;

    public function __construct(\GitSync\Config $config)
    {
        parent::__construct();
        $app           = $this;
        $app['config'] = $config;

        $app->register(new \Silex\Provider\UrlGeneratorServiceProvider());
        $app->register(new \Silex\Provider\ServiceControllerServiceProvider());

        /* Access Control */
        $this['acl'] = $app->share(function () use ($app) {
            return new \GitSync\Service\AclManager($app);
        });

        /* Twig Template Engine */
        $app->register(new \Silex\Provider\TwigServiceProvider(),
            array(
            'twig.path' => __DIR__."/../views",
        ));

        /* Auth controllers */
        $app->mount('/auth', new \GitSync\Provider\AuthControllerProvider());

        /* Repo controllers */
        $app->mount('/repo', new \GitSync\Provider\RepoControllerProvider());

        /* Root controller */
        $app->mount('/', new \GitSync\Provider\RootControllerProvider());

        /* Security */
        $app->before(function(Request $request, \Silex\Application $app) {
            if (!($routeName = $request->get('_route')) ||
                $app['acl']->checkPermission($routeName, 'test')) {
                return; // allow access
            }
        });
    }
}