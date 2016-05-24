<?php

namespace GitSync\Controller;

use GitElephant\Objects\Commit;
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

        $auditlog  = array();
        if (($auditfile = @\fopen($this->getAuditFile($context), 'r'))) {
            while (($line = fgets($auditfile))) {
                $auditlog[] = \GitSync\Audit::deserialize($line);
            }
            \fclose($auditfile);
        }


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
            $context->fetch();
        }
        return new RedirectResponse($this->app->path('context_index'));
    }

    public function checkout(Request $request, $ctxid)
    {
        $context = $this->getContext($ctxid);
        if (($refstr  = $request->request->get('ref'))) {
            $old_head = $context->getHead();
            $context->checkout($refstr);
            $new_head = $context->getHead();
            $this->auditEvent($context, $old_head, $new_head);
        }
        return new RedirectResponse($request->request->get('redirect') ? : $this->app->path('context_details',
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
    protected function getContext($name, $skip_security = false)
    {
        $context = $this->app['config']->getContext($name);
        if (!$context) {
            throw new NotFoundHttpException();
        }
        if ($this->app->isSecurityEnabled() && !$skip_security) {
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

    protected function auditEvent(\GitSync\Context $context, Commit $old_head,
                                  Commit $new_head)
    {
        $event = ($new_head->getSha() == $old_head->getSha() ?
                'RESET' : ($new_head->getDatetimeAuthor() > $old_head->getDatetimeAuthor()
                        ? 'UPDATE' : 'ROLLBACK'));
        if ($new_head->getSha() == $context->getRepo()->getCommit($context->getBranchName())) {
            $event .= ' TO LATEST';
        }
        $audit     = new \GitSync\Audit($this->app->uid(), $event,
            sprintf('%s @ %s - %s', $old_head->getSha(true),
                $old_head->getDatetimeAuthor()->format('Ymd_Hi'),
                $old_head->getMessage()),
            sprintf('%s @ %s - %s', $new_head->getSha(true),
                $new_head->getDatetimeAuthor()->format('Ymd_Hi'),
                $new_head->getMessage()));
        $auditfile = \fopen($this->getAuditFile($context), 'a');
        \fwrite($auditfile, $audit->serialize()."\n");
        \fclose($auditfile);
    }

    protected function getAuditFile(\GitSync\Context $context)
    {
        return $this->app['config']->getLogDir().'/'.$context->getId().'.audit';
    }
}