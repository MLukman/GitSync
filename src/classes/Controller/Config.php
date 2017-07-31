<?php

namespace GitSync\Controller;

use GitSync\Base\ContentController;
use Symfony\Component\HttpFoundation\Request;

class Config extends ContentController
{

    public function __construct(\GitSync\Application $app)
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
            case 0:
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

                $repo = new \GitElephant\Repository($path, new \GitElephant\GitBinary(
                    (strncasecmp(PHP_OS, 'WIN', 3) == 0) ?
                    '"C:\Program Files\Git\bin\git.exe"' : null));

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
}