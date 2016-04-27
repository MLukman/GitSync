<?php

namespace GitSync;

define('ROOT_PATH', 'context_index');
define('LIB_DIR', realpath(__DIR__.'/../../'));
define('ROOT_DIR', dirname($_SERVER["SCRIPT_FILENAME"]));

class Application extends \Silex\Application
{

    use \Silex\Application\UrlGeneratorTrait;
    protected $config    = null;
    protected $firewalls = array(
        'secured' => array(
            'pattern' => '^/',
            'http' => true,
    ));

    public function __construct(\GitSync\Config $config)
    {
        parent::__construct();

        $app           = $this;
        $app['config'] = $config;

        $app->register(new \Silex\Provider\UrlGeneratorServiceProvider());
        $app->register(new \Silex\Provider\ServiceControllerServiceProvider());

        /* Security */
        $app->register(new \Silex\Provider\SecurityServiceProvider(),
            array('security.firewalls' => array()));

        /* Twig Template Engine */
        $app->register(new \Silex\Provider\TwigServiceProvider(),
            array(
            'twig.path' => __DIR__."/../views",
        ));

        /* Auth controllers */
        //$app->mount('/auth', new \GitSync\Provider\AuthControllerProvider());

        /* Root controller */
        $app->mount('/', new \GitSync\Provider\RootControllerProvider());
    }

    public function addSecurityProvider($id,
                                        Security\SecurityProviderInterface $provider)
    {
        $app = $this;

        $app['security.authentication_listener.factory.'.$id] = $app->protect(function ($name, $options) use ($app, $id, $provider) {

            // define the authentication provider object
            $app['security.authentication_provider.'.$name.'.'.$id] = $app->share(function () use ($app, $provider) {
                return $provider->getAuthenticationProvider($app);
            });

            // define the authentication listener object
            $app['security.authentication_listener.'.$name.'.'.$id] = $app->share(function () use ($app, $provider) {
                return $provider->getAuthenticationListener($app);
            });

            return array(
                // the authentication provider id
                'security.authentication_provider.'.$name.'.'.$id,
                // the authentication listener id
                'security.authentication_listener.'.$name.'.'.$id,
                // the entry point id
                null,
                // the position of the listener in the stack
                'pre_auth'
            );
        });

        //$this->firewalls['secured']['secure'] = true;
        $this->firewalls['secured'][$id] = true;

        $app['security.firewalls'] = $this->firewalls;

        /* if .htaccess file is missing */
        if (!file_exists(ROOT_DIR.'/.htaccess') && file_exists(LIB_DIR.'/.htaccess')) {
            copy(LIB_DIR.'/.htaccess', ROOT_DIR.'/.htaccess');
        }
    }
}