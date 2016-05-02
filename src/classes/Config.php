<?php

namespace GitSync;

class Config
{
    /**
     * Contexts to be managed
     * @var \GitSync\Context[]
     */
    protected $contexts = array();

    /**
     * Directory where view files are located
     * @var string
     */
    public $viewsDir = __DIR__."/../views";

    /**
     * Base view file
     * @var string
     */
    public $baseView = 'base';

    /**
     * Context index view
     * @var string
     */
    public $contextIndexView = 'context_index';

    /**
     * Context details view
     * @var string
     */
    public $contextDetailsView = 'context_details';

    /**
     * Context init view
     * @var string
     */
    public $contextInitView = 'context_init';

    /**
     * Add context
     * @param \GitSync\Context $config
     */
    public function addContext(\GitSync\Context $config)
    {
        $this->contexts[$config->getId()] = $config;
    }

    /**
     * Get all contexts
     * @return \GitSync\Context[]
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * Get context with the given id
     * @param string $id The id of the context to retrieve
     * @return \GitSync\Context
     */
    public function getContext($id)
    {
        return (isset($this->contexts[$id]) ? $this->contexts[$id] : null);
    }
}