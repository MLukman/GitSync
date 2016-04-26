<?php

namespace GitSync\Security;

use Symfony\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider;

class LdapSecurityProvider implements SecurityProviderInterface
{
    protected $app;
    protected $authenticationProvider;
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
        $this->userProvider = new \Symfony\Component\Security\Core\User\InMemoryUserProvider();
        $this->userChecker  = new \Symfony\Component\Security\Core\User\UserChecker();
        $this->ldapClient   = new \Symfony\Component\Ldap\LdapClient($host,
            $port);
        $this->providerKey  = rand(1000, 9999);
        $this->dnString     = $dnString;
    }

    /**
     * 
     * @return \Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface
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
     * @return \Symfony\Component\Security\Http\Firewall\ListenerInterface
     */
    public function getAuthenticationListener(\Silex\Application $app)
    {
        //$app['security.token_storage'], $app['security.authentication_manager']
        if (!$this->authenticationListener) {
            $this->authenticationListener = new \Symfony\Component\Security\Http\Firewall\DigestAuthenticationListener($app['security.token_storage'],
                $this->userProvider, $this->providerKey,
                new \Symfony\Component\Security\Http\EntryPoint\DigestAuthenticationEntryPoint('GitSync',
                md5(rand(0, 999))));
        }
        return $this->authenticationListener;
    }
}