<?php

namespace GitSync;

include_once __DIR__.'/../constants.php';

class Application extends \Silex\Application
{

    use \Silex\Application\UrlGeneratorTrait;
    /**
     * The configuration
     * @var \GitSync\Config
     */
    protected $config = null;

    /**
     * The firewall settings for Symfony security module
     * @var array
     */
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
            'monolog.logfile' => $config->getLogDir().'/application.log',
            'monolog.level' => \Monolog\Logger::WARNING,
        ));

        /* Twig Template Engine */
        $app->register(new \Silex\Provider\TwigServiceProvider(),
            array(
            'twig.path' => realpath($config->viewsDir),
        ));

        /* Root controller */
        $app->mount('/', new \GitSync\Provider\RootControllerProvider());

        /* if .htaccess file is missing */
        if (!file_exists(GITSYNC_ROOT_DIR.'/.htaccess') && file_exists(GITSYNC_LIB_DIR.'/.htaccess')) {
            copy(GITSYNC_LIB_DIR.'/.htaccess', GITSYNC_ROOT_DIR.'/.htaccess');
        }
    }

    /**
     * Add & activate a security provider
     * @param \GitSync\Security\SecurityProviderInterface $provider
     * @param string $id
     */
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

    /**
     * Check if security is enabled
     * @return boolean
     */
    public function isSecurityEnabled()
    {
        return isset($this['user']);
    }

    /**
     * Get the logged in user, null if security is not enabled
     * @return \Symfony\Component\Security\Core\User\UserInterface
     */
    public function user()
    {
        return (isset($this['user']) ? $this['user'] : null);
    }

    /**
     * Get the user id, null if security is not enabled
     * @return string
     */
    public function uid()
    {
        return (($user = $this->user()) ? $user->getUsername() : null);
    }

    /**
     * Check if the currently logged in user has the needed role
     * @param string $role The needed role
     * @return boolean
     */
    public function isGranted($role)
    {
        return ($this->user() && $this['security.authorization_checker']->isGranted($role));
    }
}