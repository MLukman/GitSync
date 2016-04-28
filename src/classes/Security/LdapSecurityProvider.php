<?php

namespace GitSync\Security;

use Symfony\Component\Ldap\LdapClient;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Http\EntryPoint\BasicAuthenticationEntryPoint;
use Symfony\Component\Security\Http\Firewall\BasicAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class LdapSecurityProvider implements SecurityProviderInterface
{
    /**
     * Authentication Provider
     * @var AuthenticationProviderInterface
     */
    protected $authenticationProvider;

    /**
     * Authentication Listener
     * @var ListenerInterface
     */
    protected $authenticationListener;

    /**
     *
     * @var \Symfony\Component\Security\Core\User\InMemoryUserProvider
     */
    public $userProvider;

    /**
     *
     * @var string
     */
    public $providerKey;

    /**
     *
     * @var \Symfony\Component\Ldap\LdapClientInterface
     */
    public $ldapClient;

    /**
     *
     * @var string
     */
    public $dnString;

    public function __construct($host, $port, $dnString)
    {
        $this->userProvider = new InMemoryUserProvider();
        $this->ldapClient   = new LdapClient($host, $port);
        $this->providerKey  = rand(1000, 9999);
        $this->dnString     = $dnString;
    }

    /**
     * 
     * @return AuthenticationProviderInterface
     */
    public function getAuthenticationProvider(\Silex\Application $app)
    {
        if (!$this->authenticationProvider) {
            $this->authenticationProvider = new LdapBindAuthenticationProvider($this->userProvider,
                $app['security.user_checker'], $this->providerKey,
                $this->ldapClient, $this->dnString);
        }
        return $this->authenticationProvider;
    }

    /**
     *
     * @return ListenerInterface
     */
    public function getAuthenticationListener(\Silex\Application $app)
    {
        //$app['security.token_storage'], $app['security.authentication_manager']
        if (!$this->authenticationListener) {
            $this->authenticationListener = new BasicAuthenticationListener($app['security.token_storage'],
                $app['security.authentication_manager'], $this->providerKey,
                new BasicAuthenticationEntryPoint('GitSync'));
        }
        return $this->authenticationListener;
    }

    public function addUser($userid, array $role = array('ROLE_ADMIN'))
    {
        $this->userProvider->createUser(new User($userid, null, $role));
    }
}