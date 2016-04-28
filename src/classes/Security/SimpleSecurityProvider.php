<?php

namespace GitSync\Security;

use Symfony\Component\Security\Core\Authentication\Provider\SimpleAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\EntryPoint\BasicAuthenticationEntryPoint;
use Symfony\Component\Security\Http\Firewall\BasicAuthenticationListener;

class SimpleSecurityProvider implements SecurityProviderInterface, \Symfony\Component\Security\Core\Authentication\SimpleAuthenticatorInterface
{
    /**
     * Authentication Provider
     * @var \Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface
     */
    protected $authenticationProvider;

    /**
     * Authentication Listener
     * @var \Symfony\Component\Security\Http\Firewall\ListenerInterface
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

    public function __construct()
    {
        $this->userProvider = new InMemoryUserProvider();
        $this->providerKey  = rand(1000, 9999);
    }

    /**
     *
     * @return AuthenticationProviderInterface
     */
    public function getAuthenticationProvider(\Silex\Application $app)
    {
        if (!$this->authenticationProvider) {
            $this->authenticationProvider = new SimpleAuthenticationProvider($this,
                $this->userProvider, $this->providerKey);
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

    public function addUser($userid, $password,
                            array $role = array('ROLE_ADMIN'))
    {
        $this->userProvider->createUser(new User($userid, $password, $role));
    }
}