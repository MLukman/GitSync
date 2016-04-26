<?php

namespace GitSync\Security;

interface SecurityProviderInterface
{

    public function getAuthenticationProvider(\Silex\Application $app);

    public function getAuthenticationListener(\Silex\Application $app);
}