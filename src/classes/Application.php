<?php

namespace GitSync;

include_once __DIR__.'/../constants.php';

use Symfony\Component\HttpFoundation\RedirectResponse;

class Application extends \Silex\Application
{

    use \Silex\Application\UrlGeneratorTrait;
    /**
     * The configuration
     * @var \GitSync\Config
     */
    protected $config = null;

    /**
     * The security provider
     * @var \Securilex\ServiceProvider
     */
    protected $security = null;

    /**
     * The firewall
     * @var \Securilex\Firewall
     */
    protected $firewall = null;

    public function __construct(\GitSync\Config $config)
    {
        parent::__construct();

        $app           = $this;
        $app['config'] = $config;

        $logdir = $config->getLogDir();
        if (!is_dir($logdir) && !mkdir($logdir, 0755, true) || !is_writable($logdir)) {
            throw new \Exception('Log directory cannot be created or is not writable');
        }

        $app->register(new \Silex\Provider\UrlGeneratorServiceProvider());
        $app->register(new \Silex\Provider\ServiceControllerServiceProvider());
        $app->register(new \Silex\Provider\SessionServiceProvider());
        $app->register(new \Silex\Provider\MonologServiceProvider(),
            array(
            'monolog.logfile' => $logdir.'/application.log',
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
     * Add & activate a security
     * @param \GitSync\Security\SecurityProviderInterface $provider
     * @param string $id
     */
    public function activateSecurity(\Securilex\DriverInterface $driver)
    {
        if (!$this->security) {
            $this->security = new \Securilex\ServiceProvider();
            $this->firewall = new \Securilex\Firewall('/', $driver, '/login/',
                '/login/doLogin');
            $this->security->addFirewall($this->firewall);
            $this->register($this->security);

            /* Auth controller */
            $this['auth.controller'] = $this->share(function() {
                return new \GitSync\Controller\Auth($this);
            });

            /* Add routes */
            $this->match('/login/', 'auth.controller:login')->bind('login');
            $this->match('/login/doLogin')->run(function() {
                new RedirectResponse($this->path('context_index'));
            })->bind('doLogin');
        } else {
            $this->firewall->addDriver($driver);
        }
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