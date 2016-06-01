<?php

namespace GitSync\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Context extends \GitSync\Base\Controller
{

    public function __construct(\GitSync\Application $app)
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
        $auditlog  = $context->getAuditLog();


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
                'auditlog' => $auditlog,
        ));
    }

    public function refresh(Request $request, $ctxid)
    {
        if (($context = $this->getContext($ctxid))) {
            $context->fetch();
        }
        return new RedirectResponse($request->query->get('redirect') ? : $this->app->path('context_details',
                    array('ctxid' => $ctxid)));
    }

    public function refreshAll(Request $request)
    {
        foreach ($this->app['config']->getContexts() as $context) {
            if ($context->isInitialized()) {
                $context->fetch();
            }
        }
        return new RedirectResponse($this->app->path('context_index'));
    }

    public function checkout(Request $request, $ctxid)
    {
        $context = $this->getContext($ctxid);
        if (($refstr  = $request->request->get('ref'))) {
            $context->checkout($refstr, $this->app->uid());
        }
        return new RedirectResponse($request->request->get('redirect') ? : $this->app->path('context_details',
                    array('ctxid' => $ctxid)));
    }

    public function init(Request $request, $ctxid)
    {
        $this->getContext($ctxid)->initialize($this->app->uid());
        return new RedirectResponse($this->app->path('context_details',
                array('ctxid' => $ctxid)));
    }

    /**
     *
     * @param type $name
     * @return \GitSync\Context
     */
    protected function getContext($name, $skip_security = false)
    {
        $context = $this->app['config']->getContext($name);
        if (!$context) {
            throw new NotFoundHttpException();
        }
        if (!$skip_security && !$context->checkAccess($this->app)) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
        }
        return $context;
    }

    protected function getAllContexts()
    {
        $contexts = array();
        foreach ($this->app['config']->getContexts() as $context) {
            if ($context->checkAccess($this->app)) {
                $contexts[$context->getId()] = $context;
            }
        }
        return $contexts;
    }
}