<?php

namespace GitSync\Base;

class ContentController extends Controller
{
    private $_contexts = null;

    public function __construct(\GitSync\Application $app)
    {
        parent::__construct($app);
        $this->setCurrent($app['request']->getRequestUri());
    }

    public function render($view, array $context = null)
    {
        $this->context->title       = $this->page_title;
        $this->context->breadcrumbs = $this->breadcrumbs;
        $this->context->content     = $this->page_content;
        $this->context->contexts    = $this->getAllContexts();
        return parent::render($view, $context);
    }

    protected function setCurrent($current)
    {
        $this->context->current = $current;
    }

    protected function getAllContexts()
    {
        if (is_null($this->_contexts)) {
            $this->_contexts = array();
            foreach ($this->app['config']->getContexts() as $context) {
                if ($context->checkAccess($this->app)) {
                    $this->_contexts[$context->getId()] = $context;
                }
            }
        }
        return $this->_contexts;
    }
}