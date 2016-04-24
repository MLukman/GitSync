<?php

namespace GitSync\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Auth extends \GitSync\Base\Controller
{

    public function login(Request $request, $error = null)
    {
        return $this->renderDisplay('login',
                array(
                'post' => $request->request,
                'error' => $error,
        ));
    }

    public function doLogin(Request $request)
    {
        $p = $request->request;
        if ($this->app['auth']->login($p->get('username'), $p->get('password'))) {
            return new RedirectResponse($p->get('origin_uri'));
        }
        return $this->login($request, 'Invalid username/password');
    }

    public function logout(Request $request)
    {
        $this->app['auth']->logout();
        return new RedirectResponse($request->server->get('HTTP_REFERER') ? : $request->getBasePath());
    }
}