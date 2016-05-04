<?php

namespace GitSync\Security;

use Symfony\Component\Security\Core\Authentication\Provider\SimpleAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SimpleSecurityProvider implements SecurityProviderInterface, SimpleAuthenticatorInterface
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

    public function addUser($userid, $password, array $role = array('ROLE_USER'))
    {
        $this->userProvider->createUser(new User($userid, $password, $role));
    }

    /**
     *
     * @return AuthenticationProviderInterface
     */
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

    public function authenticateToken(TokenInterface $token,
                                      UserProviderInterface $userProvider,
                                      $providerKey)
    {
        if (($user = $userProvider->loadUserByUsername($token->getUsername())) && ($user->getPassword()
            == $token->getCredentials())) {
            return new UsernamePasswordToken(
                $user, $user->getPassword(), $providerKey, $user->getRoles()
            );
        }
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof UsernamePasswordToken && $token->getProviderKey()
            === $providerKey;
    }
}