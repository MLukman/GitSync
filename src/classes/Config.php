<?php

namespace GitSync;

include_once __DIR__.'/../constants.php';

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
     * Context presync view
     * @var string
     */
    public $contextPresyncView = 'context_presync';

    /**
     * Log files directory
     * @var string
     */
    protected $logdir = GITSYNC_ROOT_DIR.'/logs';

    /**
     * Add context
     * @param \GitSync\Context $context
     */
    public function addContext(\GitSync\Context $context)
    {
        $this->contexts[$context->getId()] = $context;
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

    /**
     * Get log directory
     * @return string
     */
    public function getLogDir()
    {
        return $this->logdir;
    }

    /**
     * Set log directory
     * @param string $newlogdir Log directory
     */
    public function setLogDir($newlogdir)
    {
        $this->logdir = $newlogdir;
        foreach ($this->contexts as $context) {
            $context->setLogDir($this->logdir);
        }
    }

    public function saveContextsToFile($fullfilepath)
    {
        return (file_put_contents($fullfilepath, serialize($this->contexts)) > 0);
    }
}