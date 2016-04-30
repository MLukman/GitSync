<?php

namespace GitSync\Security;

use Symfony\Component\Ldap\LdapClient;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\User;

class LdapSecurityProvider implements SecurityProviderInterface
{
    /**
     * Authentication Provider
     * @var AuthenticationProviderInterface
     */
    protected $authenticationProvider;

    /**
     *
     * @var \Symfony\Component\Security\Core\User\InMemoryUserProvider
     */
    protected $userProvider;

    /**
     *
     * @var \Symfony\Component\Ldap\LdapClientInterface
     */
    protected $ldapClient;

    /**
     *
     * @var string
     */
    protected $dnString;

    public function __construct($host, $port, $dnString)
    {
        $this->userProvider = new InMemoryUserProvider();
        $this->ldapClient   = new LdapClient($host, $port);
        $this->dnString     = $dnString;
    }

    /**
     * 
     * @return AuthenticationProviderInterface
     */
    public function getAuthenticationProvider(\Silex\Application $app,
                                              $providerKey)
    {
        if (!$this->authenticationProvider) {
            $this->authenticationProvider = new LdapBindAuthenticationProvider($this->userProvider,
                $app['security.user_checker'], $providerKey, $this->ldapClient,
                $this->dnString);
        }
        return $this->authenticationProvider;
    }

    public function addUser($userid, array $role = array('ROLE_ADMIN'))
    {
        $this->userProvider->createUser(new User($userid, null, $role));
    }

    public function getUserProvider()
    {
        return $this->userProvider;
    }
}