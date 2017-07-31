<?php

namespace GitSync\Controller;

use GitSync\Base\ContentController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class Setup extends ContentController
{

    public function setup(Request $request)
    {
        $error  = '';
        $step   = $request->request->get('step', 0);
        $method = $request->request->get('method', null);

        $methods = array();
        foreach (\GitSync\Application::AUTH_METHODS as $m => $mm) {
            $methods[$m] = array(
                'name' => $mm::getName(),
                'params' => $mm::getParamConfigs(),
            );
        }
        $params = $request->request->get('params', array());

        $auth = (!empty($method) ?
            \GitSync\Application::AUTH_METHODS[$method] : null);
        switch ($step) {
            case 0:
                if (!$auth) {
                    break;
                }
                if (empty($auth::getParamConfigs())) {
                    $step = 2;
                } else {
                    $step = 1;
                }
                break;

            case 1:
                if (($errors = $auth::checkParams($params))) {
                    $error = 'Parameter errors: '.join(', ', array_values($errors));
                } else {
                    $step = 2;
                }
                break;

            case 2:
                $username  = trim($request->request->get('username'));
                $password  = $request->request->get('password');
                $password2 = $request->request->get('password2');
                if (empty($username)) {
                    $error = 'Username is required';
                } elseif ($password != $password2) {
                    $error = 'Passwords mismatch';
                } else {
                    $user        = $this->app['userProvider']->createUser($username, $password, array(
                        'ROLE_ADMIN'));
                    $params      = $request->request->get('params', array());
                    $auth_method = new $auth($params);
                    $error       = $auth_method->firstLogin($user);
                    if (empty($error)) {
                        $this->app['userProvider']->saveUser($user);
                        $this->app['config']->update('auth.method', $method);
                        $this->app['config']->update('auth.params.'.$method, $params);
                        $this->app['config']->update('setup.done', true);
                        return new RedirectResponse($this->app->path('context_index'));
                    }
                }
                break;

            default:
                $step++;
        }
        return $this->render('config_setup', array(
                'step' => $step,
                'error' => $error,
                'methods' => $methods,
                'method' => (isset($methods[$method]) ? $methods[$method] : null),
        ));
    }
}