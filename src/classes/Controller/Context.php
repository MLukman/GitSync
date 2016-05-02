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
        if ($this->app->isSecurityEnabled() && !$this->app->isGranted('ROLE_ADMIN')) {
            $uid = $this->app->uid();

            $contexts = array();
            foreach ($this->app['config']->getContexts() as $context) {
                if ($context->isUidAllowed($uid)) {
                    $contexts[] = $context;
                }
            }
        } else {
            $contexts = $this->app['config']->getContexts();
        }

        return $this->renderDisplay('repo_index',
                array(
                'contexts' => $contexts,
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

        return $this->renderDisplay('repo_details',
                array(
                'ctxid' => $ctxid,
                'context' => $context,
                'head' => $context->getHead(),
                'repoStatus' => $repo->getStatus()->all(),
                'revisions' => $context->getLatestRevisions(),
        ));
    }

    public function refresh(Request $request)
    {
        foreach ($this->app['config']->getContexts() as $context) {
            $context->fetch();
        }
        return new RedirectResponse($this->app->path('context_index'));
    }

    public function checkout(Request $request, $ctxid, $ref)
    {
        $context = $this->getContext($ctxid)->checkout($ref);
        return new RedirectResponse($this->app->path('context_details',
                array('ctxid' => $ctxid)));
    }

    public function init(Request $request, $ctxid)
    {
        $context = $this->getContext($ctxid);
        $context->isInitialized(true);
        return new RedirectResponse($this->app->path('context_details',
                array('ctxid' => $ctxid)));
    }

    /**
     *
     * @param type $name
     * @return \GitSync\Context
     */
    protected function getContext($name)
    {
        $context = $this->app['config']->getContext($name);
        if ($this->app->isSecurityEnabled()) {
            if (!$this->app->isGranted('ROLE_ADMIN') && !$context->isUidAllowed($this->app->uid())) {
                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
            }
        }
        return $context;
    }
}