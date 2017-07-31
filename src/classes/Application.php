<?php

namespace GitSync;

include_once __DIR__.'/../constants.php';

use Monolog\Logger;
use Securilex\Authentication\Factory\AuthenticationFactoryInterface;
use Securilex\Authentication\User\SQLite3UserProvider;
use Securilex\Authorization\SecuredAccessVoter;
use Securilex\Firewall;
use Securilex\ServiceProvider;
use Silex\Application\UrlGeneratorTrait;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * The main Application class for GitSync. This class is the entrypoint for all
 * request handlings within GitSync.
 */
class Application extends \Silex\Application
{
    const AUTH_METHODS = array(
        'password' => 'GitSync\Auth\PasswordAuthMethod',
        'ldap' => 'GitSync\Auth\LdapAuthMethod',
    );

    use UrlGeneratorTrait;
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
    protected $firewall     = null;
    protected $auth_methods = array();

    public function __construct(Config $config = null)
    {
        parent::__construct();

        $app = $this;
        if ($config) {
            $app->register($config);
        } else {
            $app->register(new Config());
            $config = $app['config'];
        }

        $logdir = $config->getLogDir();
        if (!is_dir($logdir) && !mkdir($logdir, 0755, true) || !is_writable($logdir)) {
            throw new \Exception('Log directory cannot be created or is not writable');
        }

        $app->register(new UrlGeneratorServiceProvider());
        $app->register(new ServiceControllerServiceProvider());
        $app->register(new SessionServiceProvider());
        $app->register(new MonologServiceProvider(), array(
            'monolog.logfile' => $logdir.'/application.log',
            'monolog.level' => Logger::WARNING,
        ));

        /* Twig Template Engine */
        $app->register(new TwigServiceProvider(), array(
            'twig.path' => realpath($config->viewsDir),
        ));

        /* Root controller */
        $app->mount('/', new Provider\RootControllerProvider());
        $app->mount('/config', new Provider\ConfigControllerProvider());

        /* if .htaccess file is missing */
        if (!file_exists(GITSYNC_ROOT_DIR.'/.htaccess') && file_exists(GITSYNC_LIB_DIR.'/.htaccess')) {
            copy(GITSYNC_LIB_DIR.'/.htaccess', GITSYNC_ROOT_DIR.'/.htaccess');
        }
    }

    public function getAuthenticationMethod($id = null)
    {
        $methods = static::AUTH_METHODS;
        if (empty($id)) {
            foreach ($methods as $id => $meth) {
                if (!isset($this->auth_methods[$id])) {
                    $this->getAuthenticationMethod($id);
                }
            }
            return $this->auth_methods;
        } else if (isset($methods[$id])) {
            if (!isset($this->auth_methods[$id])) {
                $this->auth_methods[$id] = new $methods[$id]($this['config']->query('auth.params.'.$id)
                        ?: array());
            }
            return $this->auth_methods[$id];
        }
        return null;
    }

    /**
     * Activate security using the provided Authentication Factory and User Provider.
     * It is possible to use multiple pairs of Authentication Factory and User Provider
     * by calling this method multiple times.
     *
     * @param AuthenticationFactoryInterface $authFactory
     * @param UserProviderInterface $userProvider
     */
    public function activateSecurity(AuthenticationFactoryInterface $authFactory,
                                     UserProviderInterface $userProvider)
    {
        if (!$this->security) {
            $this->security = new ServiceProvider();
            $this->firewall = new Firewall('/', '/login/');
            $this->firewall->addAuthenticationFactory($authFactory, $userProvider);
            $this->security->addFirewall($this->firewall);
            $this->security->addAuthorizationVoter(new SecuredAccessVoter());
            $this->register($this->security);

            /* Auth controller */
            $this['auth.controller'] = $this->share(function() {
                return new Controller\Auth($this);
            });

            /* Add routes */
            $this->match('/login/', 'auth.controller:login')->bind('login');
        } else {
            $this->firewall->addAuthenticationFactory($authFactory, $userProvider);
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
        return ($this->user() && $this['securilex']->isGranted($role));
    }

    public function getContexts($checkAccess = true)
    {
        $contexts = array();
        foreach ($this['config']->getContexts() as $context) {
            if (!$checkAccess || $context->checkAccess($this)) {
                $contexts[$context->getId()] = $context;
            }
        }
        return $contexts;
    }

    public function run(Request $request = null)
    {
        $this['userProvider'] = new SQLite3UserProvider(new \SQLite3(GITSYNC_DATA_DIR.'config.sqlite'));
        $this['userProvider']->setUserClass('\Securilex\Authentication\User\SimpleMutableUser');
        if ($this['config']->query('setup.done') && ($method_name          = $this['config']->query('auth.method'))
            && ($auth_method          = $this->getAuthenticationMethod($method_name))) {
            $this['authMethod'] = $auth_method;
            $authFactory        = $this['authMethod']->getAuthenticationFactory($this['config']->query('auth.params.'.$method_name));
            $this->activateSecurity($authFactory, $this['userProvider']);
        } else if (!$this->security) {
            $this->before(function(Request $request, \Silex\Application $app) {
                if (substr($request->get('_route'), 0, 12) !== "config_setup") {
                    return new RedirectResponse($app->path('config_setup'));
                }
            });
        }
        parent::run($request);
    }

    static public function execute($debug = false)
    {
        $app          = new static();
        $app['debug'] = $debug;
        $app->run();
    }
}