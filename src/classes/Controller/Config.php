<?php

namespace GitSync\Controller;

use GitSync\Application;
use GitSync\Base\ContentController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class Config extends ContentController
{

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->setCurrent($app->path('config_contexts'));
    }

    public function contexts(Request $request)
    {
        return $this->render('config_contexts', array(
                'contexts' => $this->app['config']->getContexts(),
        ));
    }

    public function contextAdd(Request $request)
    {
        $error     = '';
        $step      = $request->request->get('step', null);
        $path      = '';
        $repo_info = null;

        switch ($step) {
            case null;
                break;
            case 0:
            case 1:
                $path = trim($request->request->get('path'));
                if (empty($path)) {
                    $error = 'Path is required';
                    break;
                }
                $path = realpath($path);
                if (empty($path)) {
                    $error = 'Path must already exist';
                    break;
                }

                if (!file_exists("$path/.git")) {
                    $error = 'Path must already be a Git repository';
                    break;
                }

                if ($this->app['config']->getContextByPath($path)) {
                    $error = 'The provided path is already managed by this GitSync';
                    break;
                }

                $repo = new \GitElephant\Repository($path, Application::newGitBinary());

                $remote = null;
                try {
                    $remote = $repo->getRemote('origin');
                } catch (Exception $ex) {
                    $remotes = $repo->getRemotes();
                    if (count($remotes) == 0) {
                        $error = 'The Git repository must already be setup with a remote repository to sync with';
                    } else {
                        $remote = $remotes[0];
                    }
                }

                if ($remote) {
                    $repo_info = array(
                        'remote' => $remote->getName(),
                        'url' => $remote->getFetchURL(),
                        'branch' => $repo->getMainBranch()->getName(),
                    );

                    $id = basename($path);
                    if ($this->app['config']->getContext($id)) {
                        $c   = 2;
                        $cid = sprintf('%s%d', $id, $c);
                        while ($this->app['config']->getContext($cid)) {
                            $c++;
                        }
                        $repo_info['id'] = $cid;
                    } else {
                        $repo_info['id'] = $id;
                    }

                    if ($step == 1) {
                        $this->app['config']->saveContext($path, $repo_info['url'], $repo_info['branch'], $repo_info['remote'], $repo_info['id']);
                        return new RedirectResponse($this->app->path('config_contexts'));
                    }

                    $step++;
                }

                break;
        }

        return $this->render('config_context_add', array(
                'step' => $step,
                'path' => $path,
                'repo' => $repo_info,
                'error' => $error,
        ));
    }

    public function users(Request $request)
    {
        $this->setCurrent($this->app->path('config_users'));
        return $this->render('config_users', array(
                'users' => $this->app['userProvider']->selectAll(),
                'contexts' => $this->app['config']->getContexts(),
        ));
    }

    public function userAdd(Request $request)
    {
        $this->setCurrent($this->app->path('config_users'));
        $step  = $request->request->get('step', 0);
        $error = '';

        if ($step == 1) {
            $username  = trim($request->request->get('username'));
            $password  = $request->request->get('password');
            $password2 = $request->request->get('password2');
            $role      = $request->request->get('role');
            if (empty($username)) {
                $error = 'Username is required';
            } elseif ($this->app['userProvider']->loadUserByUsername($username, false)) {
                $error = 'Username already exists';
            } elseif ($password != $password2) {
                $error = 'Passwords mismatch';
            } else {
                $user = $this->app['userProvider']->createUser($username, $password, array(
                    $role));
                $this->app['authMethod']->prepareNewUser($user);
                $this->app['userProvider']->saveUser($user);
                return new RedirectResponse($this->app->path('config_users'));
            }
        } else {
            $step++;
        }

        return $this->render('config_user_add', array(
                'step' => $step,
                'error' => $error,
        ));
    }
}