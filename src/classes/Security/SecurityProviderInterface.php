<?php

namespace GitSync\Security;

interface SecurityProviderInterface
{

    public function getAuthenticationProvider(\Silex\Application $app,
                                              $providerKey);

    public function getUserProvider();
}