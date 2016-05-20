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
        return $this->renderDisplay($this->app['config']->contextIndexView,
                array(
                'contexts' => $this->getAllContexts(),
        ));
    }

    public function details(Request $request, $ctxid)
    {
        $context = $this->getContext($ctxid);

        if (!$context->isInitialized()) {
            return $this->renderDisplay($this->app['config']->contextInitView,
                    array(
                    'ctxid' => $ctxid,
                    'path' => $context->getPath(),
            ));
        }

        $repo      = $context->getRepo();
        $head      = $context->getHead();
        $revisions = $context->getLatestRevisions();

        /* If HEAD is not in the list of revisions (most probably due to the directory
         * is on a different branch) then add HEAD to the top of the list
         */
        $showHead = true;
        foreach ($revisions as $rev) {
            if ($rev->getCommit()->getSha() == $head->getSha()) {
                $showHead = false;
                break;
            }
        }
        if ($showHead) {
            array_unshift($revisions, new \GitSync\Revision($head));
        }

        /* Display */
        return $this->renderDisplay($this->app['config']->contextDetailsView,
                array(
                'ctxid' => $ctxid,
                'context' => $context,
                'head' => $head,
                'modifications' => $context->getModifications(true),
                'revisions' => $revisions,
        ));
    }

    public function refresh(Request $request, $ctxid)
    {
        if (($context = $this->getContext($ctxid))) {
            $context->fetch();
        }
        return new RedirectResponse($this->app->path('context_details',
                array('ctxid' => $ctxid)));
    }

    public function refreshAll(Request $request)
    {
        foreach ($this->app['config']->getContexts() as $context) {
            $context->fetch();
        }
        return new RedirectResponse($this->app->path('context_index'));
    }

    public function checkout(Request $request, $ctxid)
    {
        $context = $this->getContext($ctxid);
        if (($refstr  = $request->request->get('ref'))) {
            $context->checkout($refstr);
        }
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

    protected function getAllContexts()
    {
        if ($this->app->isSecurityEnabled() && !$this->app->isGranted('ROLE_ADMIN')) {
            $uid = $this->app->uid();

            $contexts = array();
            foreach ($this->app['config']->getContexts() as $context) {
                if ($context->isUidAllowed($uid)) {
                    $contexts[] = $context;
                }
            }
            return $contexts;
        }
        return $this->app['config']->getContexts();
    }
}