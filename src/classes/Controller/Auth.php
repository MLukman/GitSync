<?php

namespace GitSync\Controller;

use Symfony\Component\HttpFoundation\Request;

class Auth extends \GitSync\Base\Controller
{

    public function login(Request $request)
    {
        return $this->renderDisplay('login',
                array(
                'error' => $this->app['security.last_error']($request),
                'last_username' => $this->app['session']->get('_security.last_username'),
        ));
    }
}