<?php

namespace GitSync\Security;

use Symfony\Component\Security\Core\Authentication\Provider\SimpleAuthenticationProvider;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\User;

class SimpleSecurityProvider implements SecurityProviderInterface
{
    /**
     * Authentication Provider
     * @var \Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface
     */
    protected $authenticationProvider;

    /**
     *
     * @var \Symfony\Component\Security\Core\User\InMemoryUserProvider
     */
    protected $userProvider;

    public function __construct()
    {
        $this->userProvider = new InMemoryUserProvider();
    }

    /**
     * Add a user to the list of authenticated users
     * @param type $userid User id
     * @param type $password Plain password
     * @param array $role The array of user roles: ROLE_USER, ROLE_ADMIN, ROLE_SUPERADMIN
     */
    public function addUser($userid, $password, array $role = array('ROLE_USER'))
    {
        $this->userProvider->createUser(new User($userid, $password, $role));
    }

    public function getAuthenticationProvider(\Silex\Application $app,
                                              $providerKey)
    {
        if (!$this->authenticationProvider) {
            $this->authenticationProvider = new SimpleAuthenticationProvider($this,
                $this->userProvider, $providerKey);
        }
        return $this->authenticationProvider;
    }

    public function getUserProvider()
    {
        return $this->userProvider;
    }
}