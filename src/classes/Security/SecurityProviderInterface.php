<?php

namespace GitSync\Security;

interface SecurityProviderInterface
{

    /**
     * Get Authentication Provider
     * @param \Silex\Application $app The application
     * @param string $providerKey Provider key (usually needed by the authentication provider)
     * @return \Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface
     */
    public function getAuthenticationProvider(\Silex\Application $app,
                                              $providerKey);

    /**
     * Get User Provider
     * @return \Symfony\Component\Security\Core\User\UserProviderInterface
     */
    public function getUserProvider();
}