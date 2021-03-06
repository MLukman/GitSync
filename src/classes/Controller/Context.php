<?php

namespace GitSync\Controller;

use GitSync\Revision;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class Context extends \GitSync\Base\ContentController
{

    public function __construct(\GitSync\Application $app)
    {
        parent::__construct($app);
        ini_set('max_execution_time', 300);
    }

    public function index(Request $request)
    {
        return $this->render($this->app['config']->contextIndexView);
    }

    public function status(Request $request)
    {
        $outputs = array();
        foreach ($this->getAllContexts() as $context) {
            $outputs[$context->getId()] = array(
                'init' => $context->isInitialized(),
                'dirty' => $context->isDirty(),
                'latest' => $context->isLatest(),
            );
        }
        return new JsonResponse($outputs);
    }

    public function details(Request $request, $ctxid)
    {
        $s       = \microtime(true);
        $context = $this->getContext($ctxid);

        $this->setCurrent($this->app->path('context_details', array('ctxid' => $ctxid)));

        if (!$context->isInitialized()) {
            return $this->render($this->app['config']->contextInitView, array(
                    'ctxid' => $ctxid,
                    'path' => $context->getPath(),
            ));
        }

        $head = $context->getHead();
        //print \microtime(true) - $s; exit;

        /* Display */
        return $this->render($this->app['config']->contextDetailsView, array(
                'ctxid' => $ctxid,
                'context' => $context,
                'head' => $head,
                'modifications' => $context->getModifications(true),
                'auditlog' => $context->getAuditLog(),
        ));
    }

    public function revisions(Request $request, $ctxid)
    {
        $outputs   = array();
        $context   = $this->getContext($ctxid);
        $head      = $context->getHead();
        $latest    = $context->isLatest();
        $revisions = $context->getLatestRevisions();

        $dirty   = $context->isDirty();
        $headSHA = $head->getSha();

        $revToArray = (function (Revision $rev) {
                return array(
                    'timestamp' => $rev->getDate()->getTimestamp(),
                    'ref' => $rev->getRef(),
                    'tags' => $rev->getTags(),
                    'sha' => $rev->getSHA(true),
                    'committer' => $rev->getCommitter()->getName(),
                    'message' => $rev->getMessage()->getFullMessage(),
                );
            });
        foreach ($revisions as $rev) {
            $output             = $revToArray($rev);
            $output['active']   = ($rev->getCommit()->getSha() == $headSHA);
            $output['inbranch'] = true;
            $outputs[]          = $output;
        }
        if (!isset($revisions[$headSHA])) {
            $output             = $revToArray(new Revision($head));
            $output['active']   = true;
            $output['inbranch'] = false;
            array_unshift($outputs, $output);
        }

        return new JsonResponse(array(
            'latest' => $latest,
            'dirty' => $dirty,
            'revisions' => array_values($outputs)
        ));
    }

    public function refresh(Request $request, $ctxid)
    {
        if (($context = $this->getContext($ctxid))) {
            $context->fetch();
        }
        return new RedirectResponse($request->query->get('redirect') ?: $this->app->path('context_details', array(
                'ctxid' => $ctxid)));
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

    public function presync(Request $request, $ctxid, $ref)
    {
        $context = $this->getContext($ctxid);
        $diff    = $context->getRepo()->getDiff($ref, 'HEAD');
        return $this->render($this->app['config']->contextPresyncView, array(
                'ctxid' => $ctxid,
                'ref' => $ref,
                'context' => $context,
                'modifications' => $context->getModifications(true),
                'diff' => $diff,
        ));
    }

    public function presyncAjax(Request $request, $ctxid, $ref)
    {
        $context = $this->getContext($ctxid);
        $diff    = $context->getRepo()->getDiff($ref, 'HEAD');
        return new JsonResponse(array(
            'modifications' => $context->getModifications(true),
            'diff' => $this->diffToArray($diff),
        ));
    }

    protected function diffToArray(\GitElephant\Objects\Diff\Diff $diff)
    {
        $diffline = array(
            'added' => '+ ',
            'deleted' => '- ',
            'unchanged' => '  ',
        );
        $outputs  = array();
        foreach ($diff as $d) {
            $output = array(
                'mode' => $d->getMode(),
                'orig_path' => $d->getOriginalPath(),
                'dest_path' => $d->getDestinationPath(),
                'chunks' => array(),
            );
            foreach ($d as $c) {
                $chunk = array();
                foreach ($c as $line) {
                    $chunk[] = $diffline[$line->getType()].((string) $line);
                }
                $output['chunks'][] = implode("\n", $chunk);
            }
            $outputs[] = $output;
        }
        return $outputs;
    }

    public function dosync(Request $request, $ctxid, $ref)
    {
        $context = $this->getContext($ctxid);
        $context->checkout($ref, $this->app->uid());
        return new RedirectResponse($request->request->get('redirect') ?: $this->app->path('context_details', array(
                'ctxid' => $ctxid)));
    }

    public function init(Request $request, $ctxid)
    {
        $this->getContext($ctxid)->initialize($this->app->uid());
        return new RedirectResponse($this->app->path('context_details', array('ctxid' => $ctxid)));
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
            throw new AccessDeniedException();
        }
        return $context;
    }
}