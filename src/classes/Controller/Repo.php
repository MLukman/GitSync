<?php

namespace GitSync\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Repo extends \GitSync\Base\Controller
{

    public function __construct(\Silex\Application $app)
    {
        parent::__construct($app);
        ini_set('max_execution_time', 300);
    }

    public function index(Request $request)
    {
        return $this->renderDisplay('repo_index',
                array(
                'contexts' => $this->app['config']->getContexts(),
        ));
    }

    public function details(Request $request, $repoid)
    {
        $context = $this->getContext($repoid);
        if (!$context->isInitialized()) {
            return $this->renderDisplay('repo_init',
                    array(
                    'repoid' => $repoid,
                    'path' => $context->getPath(),
            ));
        }

        $repo = $context->getRepo();

        /*
          try {
          $remote = $repo->getRemote('origin');
          if ($remote->getFetchURL() != $context->getRemoteUrl()) {
          $repo->addRemote('gitsync', $context->getRemoteUrl());
          $remote = $repo->getRemote('gitsync');
          }
          } catch (\Exception $e) {
          if (strpos($e->getMessage(), "remote doesn't exist")) {
          $repo->addRemote('origin', $context->getRemoteUrl());
          $remote = $repo->getRemote('origin');
          } else {
          throw $e;
          }
          } */

        return $this->renderDisplay('repo_details',
                array(
                'repoid' => $repoid,
                'path' => $context->getPath(),
                'isDirty' => $repo->isDirty(),
                'head' => $repo->getCommit(),
                'repoStatus' => $repo->getStatus()->all(),
                'commits' => $repo->getLog('master', null, 25)->toArray(),
        ));
    }

    public function checkout(Request $request, $repoid, $ref)
    {
        $context = $this->getContext($repoid);
        $repo    = $context->getRepo();
        $repo->fetch($context->getRemote()->getName());
        if ($repo->isDirty()) {
            $repo->hardReset();
        }
        $repo->checkout($ref);
        $repo->updateSubmodule(true, true, true);
        return new RedirectResponse($this->app->path('repo_details',
                array('repoid' => $repoid)));
    }

    public function init(Request $request, $repoid)
    {
        $context = $this->getContext($repoid);
        $context->isInitialized(true);
        return new RedirectResponse($this->app->path('repo_details',
                array('repoid' => $repoid)));
    }

    /**
     *
     * @param type $name
     * @return \GitSync\Context
     */
    protected function getContext($name)
    {
        return $this->app['config']->getContext($name);
    }
}