<?php

namespace GitSync;

// Directory where index.php is running from
define('LIB_DIR', realpath(__DIR__.'/../../'));

// Directory where index.php is running from
define('ROOT_DIR', dirname($_SERVER["SCRIPT_FILENAME"]));

define('ROOT_PATH', 'context_index');

class Application extends \Silex\Application
{

    use \Silex\Application\UrlGeneratorTrait;
    protected $config    = null;
    protected $firewalls = array(
        'login' => array(
            'pattern' => '^/auth/login$',
        ),
        'secured' => array(
            'pattern' => '^/',
            'form' => array(
                'login_path' => '/auth/login',
                'check_path' => '/admin/login_check'
            ),
            'logout' => true,
    ));

    public function __construct(\GitSync\Config $config)
    {
        parent::__construct();

        $app           = $this;
        $app['config'] = $config;

        $app->register(new \Silex\Provider\UrlGeneratorServiceProvider());
        $app->register(new \Silex\Provider\ServiceControllerServiceProvider());
        $app->register(new \Silex\Provider\SessionServiceProvider());
        $app->register(new \Silex\Provider\MonologServiceProvider(),
            array(
            'monolog.logfile' => LIB_DIR.'/logs/application.log',
            'monolog.level' => \Monolog\Logger::WARNING,
        ));

        /* Twig Template Engine */
        $app->register(new \Silex\Provider\TwigServiceProvider(),
            array(
            'twig.path' => __DIR__."/../views",
        ));

        /* Root controller */
        $app->mount('/', new \GitSync\Provider\RootControllerProvider());

        /* if .htaccess file is missing */
        if (!file_exists(ROOT_DIR.'/.htaccess') && file_exists(LIB_DIR.'/.htaccess')) {
            copy(LIB_DIR.'/.htaccess', ROOT_DIR.'/.htaccess');
        }

        $app['security.firewalls'] = array(
            'login' => array(
                'pattern' => '^/auth/login$',
            ),
            'secured' => array(
                'pattern' => '^/',
            )
        );
    }

    public function addSecurityProvider(Security\SecurityProviderInterface $provider,
                                        $id = null)
    {
        $app = $this;

        if (!$app->isSecurityEnabled()) {
            /* Register SecurityServiceProvider */
            $app->register(new \Silex\Provider\SecurityServiceProvider(),
                array('security.firewalls' => array()));

            /* Auth controllers */
            $app->mount('/auth', new \GitSync\Provider\AuthControllerProvider());
        }

        $id = $id ? : 'secure'.rand(100, 999);

        $app['security.authentication_listener.factory.'.$id] = $app->protect(function ($name, $options) use ($app, $id, $provider) {

            $app['security.authentication_provider.'.$name.'.'.$id] = $app->share(function () use ($app, $provider, $name) {
                return $provider->getAuthenticationProvider($app, $name);
            });

            $app['security.authentication_listener.'.$name.'.'.$id] = $app['security.authentication_listener.form._proto']($name,
                $options);

            $app['security.entry_point.'.$name.'.form'] = $app['security.entry_point.form._proto']($name,
                $options);

            $app['security.context_listener.'.$name] = $app['security.context_listener._proto']($name,
                array($provider->getUserProvider()));

            return array(
                'security.authentication_provider.'.$name.'.'.$id, // the authentication provider id
                'security.authentication_listener.'.$name.'.'.$id, // the authentication listener id
                'security.entry_point.'.$name.'.form', // the entry point id
                'pre_auth' // the position of the listener in the stack
            );
        });

        $this->firewalls['secured'][$id] = array(
            'login_path' => '/auth/login',
            'check_path' => '/admin/login_check'
        );

        $app['security.firewalls'] = $this->firewalls;
    }

    public function isSecurityEnabled()
    {
        return isset($this['user']);
    }

    public function user()
    {
        return (isset($this['user']) ? $this['user'] : null);
    }

    public function uid()
    {
        return (($user = $this->user()) ? $user->getUsername() : null);
    }

    public function isGranted($role)
    {
        return ($this->user() && $this['security.authorization_checker']->isGranted($role));
    }
}