<?php

namespace GitSync\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Context extends \GitSync\Base\Controller
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

    public function details(Request $request, $ctxid)
    {
        $context = $this->getContext($ctxid);
        if (!$context->isInitialized()) {
            return $this->renderDisplay('repo_init',
                    array(
                    'ctxid' => $ctxid,
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
                'ctxid' => $ctxid,
                'context' => $context,
                'head' => $context->getHead(),
                'repoStatus' => $repo->getStatus()->all(),
                'revisions' => $context->getLatestRevisions(),
        ));
    }

    public function checkout(Request $request, $ctxid, $ref)
    {
        $context = $this->getContext($ctxid);
        $repo    = $context->getRepo();
        if ($repo->isDirty()) {
            $repo->reset('HEAD', 'hard');
            $repo->clean();
        }
        $repo->checkout($ref);
        $repo->updateSubmodule(true, true, true);
        return new RedirectResponse($this->app->path('repo_details',
                array('ctxid' => $ctxid)));
    }

    public function init(Request $request, $ctxid)
    {
        $context = $this->getContext($ctxid);
        $context->isInitialized(true);
        return new RedirectResponse($this->app->path('repo_details',
                array('ctxid' => $ctxid)));
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