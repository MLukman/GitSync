<?php

namespace GitSync\Base;

/**
 * Base class for all page controllers
 */
class Controller
{
    /**
     * @var \Silex\Application
     */
    public $app            = null;
    public $page_title     = "GitSync";
    public $page_content   = "";
    public $context        = null;
    protected $breadcrumbs = array();

    public function __construct(\Silex\Application $app)
    {
        $this->app        = $app;
        //$this->page_title = \GitSync\Config::$default_page_title;

        $this->context              = new \stdClass();
        $this->context->baseUIPath  = $app->path('root');
        $this->context->_CONTROLLER = $this;
        $this->context->extra_js    = array();
        $this->context->extra_css   = array();
    }

    public function display($base_template = null)
    {
        $breadcount = count($this->breadcrumbs);
        if ($breadcount > 0) {
            $this->breadcrumbs[$breadcount - 1]['current'] = true;
        }
        $this->context->title       = $this->page_title;
        $this->context->breadcrumbs = $this->breadcrumbs;
        $this->context->content     = $this->page_content;
        return $this->render($base_template ? $base_template : 'base');
    }

    public function render($view, Array $context = null)
    {
        return $this->twig->render("$view.twig", $this->mergeContext($context));
    }

    public function renderDisplay($view, Array $context = null,
                                  $base_template = null)
    {
        $this->page_content = $this->render($view, $context);
        return $this->display($base_template);
    }

    protected function mergeContext(Array $additional_context = null)
    {
        $context = array();
        foreach ($this->context as $key => $value) {
            $context[$key] = $value;
        }
        if ($additional_context) {
            $context = array_merge($context, $additional_context);
        }
        return $context;
    }

    public function addJS($file, $tag = null)
    {
        $this->context->extra_js[$tag ? : $file] = $file;
    }

    public function addCSS($file, $tag = null)
    {
        $this->context->extra_css[$tag ? : $file] = $file;
    }

    public function __get($name)
    {
        return $this->app[$name];
    }
}