<?php

namespace GitSync;

include_once __DIR__.'/../constants.php';

use GitSync\Controller\Auth;
use GitSync\Provider\RootControllerProvider;
use Monolog\Logger;
use Securilex\Authentication\AuthenticationFactoryInterface;
use Securilex\Authorization\SecuredAccessVoter;
use Securilex\Firewall;
use Securilex\ServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * The main Application class for GitSync. This class is the entrypoint for all
 * request handlings within GitSync.
 */
class Application extends \Silex\Application
{

    use \Silex\Application\UrlGeneratorTrait;
    /**
     * The configuration
     * @var Config
     */
    protected $config = null;

    /**
     * The security provider
     * @var ServiceProvider
     */
    protected $security = null;

    /**
     * The firewall
     * @var Firewall
     */
    protected $firewall = null;

    public function __construct(Config $config)
    {
        parent::__construct();

        $app           = $this;
        $app['config'] = $config;

        $logdir = $config->getLogDir();
        if (!is_dir($logdir) && !mkdir($logdir, 0755, true) || !is_writable($logdir)) {
            throw new \Exception('Log directory cannot be created or is not writable');
        }

        $app->register(new UrlGeneratorServiceProvider());
        $app->register(new ServiceControllerServiceProvider());
        $app->register(new SessionServiceProvider());
        $app->register(new MonologServiceProvider(),
            array(
            'monolog.logfile' => $logdir.'/application.log',
            'monolog.level' => Logger::WARNING,
        ));

        /* Twig Template Engine */
        $app->register(new TwigServiceProvider(),
            array(
            'twig.path' => realpath($config->viewsDir),
        ));

        /* Root controller */
        $app->mount('/', new RootControllerProvider());

        /* if .htaccess file is missing */
        if (!file_exists(GITSYNC_ROOT_DIR.'/.htaccess') && file_exists(GITSYNC_LIB_DIR.'/.htaccess')) {
            copy(GITSYNC_LIB_DIR.'/.htaccess', GITSYNC_ROOT_DIR.'/.htaccess');
        }
    }

    /**
     * Activate security using the provided Authentication Factory and User Provider
     * @param AuthenticationFactoryInterface $authFactory
     * @param UserProviderInterface $userProvider
     */
    public function activateSecurity(AuthenticationFactoryInterface $authFactory,
                                     UserProviderInterface $userProvider)
    {
        if (!$this->security) {
            $this->security = new ServiceProvider();
            $this->firewall = new Firewall('/', '/login/');
            $this->firewall->addAuthenticationFactory($authFactory,
                $userProvider);
            $this->security->addFirewall($this->firewall);
            $this->security->addAuthorizationVoter(new SecuredAccessVoter());
            $this->register($this->security);

            /* Auth controller */
            $this['auth.controller'] = $this->share(function() {
                return new Auth($this);
            });

            /* Add routes */
            $this->match('/login/', 'auth.controller:login')->bind('login');
        } else {
            $this->firewall->addAuthenticationFactory($authFactory,
                $userProvider);
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
     * @return UserInterface
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