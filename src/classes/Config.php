<?php

namespace GitSync;

include_once __DIR__.'/../constants.php';

class Config implements \Silex\ServiceProviderInterface
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
     * \SQLite3
     * @var \SQLite3
     */
    protected $sqlite       = null;
    protected $queries      = array();
    protected $select_query = null;
    protected $update_query = null;
    protected $delete_query = null;

    public function __construct()
    {
        if (!file_exists(GITSYNC_DATA_DIR)) {
            mkdir(GITSYNC_DATA_DIR, 0750, true);
        }
        $this->sqlite = new \SQLite3(GITSYNC_DATA_DIR.'config.sqlite');
        $this->sqlite->exec("CREATE TABLE IF NOT EXISTS config (cfg_key TEXT CONSTRAINT config_pk PRIMARY KEY NOT NULL, cfg_val TEXT NULL)");
        $this->sqlite->exec("CREATE TABLE IF NOT EXISTS contexts (id TEXT CONSTRAINT context_pk PRIMARY KEY NOT NULL, path TEXT UNIQUE NOT NULL, remote_url TEXT NOT NULL, branch TEXT, remote_name TEXT, name TEXT)");
    }

    public function __destruct()
    {
        if ($this->sqlite) {
            $this->sqlite->close();
            $this->sqlite = null;
        }
    }

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

    public function query($key)
    {
        if (!isset($this->queries['cfg.select'])) {
            $this->queries['cfg.select'] = $this->sqlite->prepare("SELECT cfg_val FROM config WHERE cfg_key = :key");
        }
        $this->queries['cfg.select']->bindValue(':key', $key);
        if (($result = $this->queries['cfg.select']->execute()->fetchArray(SQLITE3_ASSOC))) {
            return unserialize($result['cfg_val']);
        }
        return null;
    }

    public function update($key, $val)
    {
        if (!isset($this->queries['cfg.update'])) {
            $this->queries['cfg.update'] = $this->sqlite->prepare("INSERT OR REPLACE INTO config (cfg_key, cfg_val) VALUES (:key, :val)");
        }
        $this->queries['cfg.update']->bindValue(':key', $key);
        $this->queries['cfg.update']->bindValue(':val', serialize($val));
        $this->queries['cfg.update']->execute();
    }

    public function delete($key)
    {
        if (!isset($this->queries['cfg.delete'])) {
            $this->queries['cfg.delete'] = $this->sqlite->prepare("DELETE FROM config WHERE cfg_key = :key");
        }
        $this->queries['cfg.delete']->bindValue(':key', $key);
        $this->queries['cfg.delete']->execute();
    }

    public function boot(\Silex\Application $app)
    {
        if (!isset($this->queries['ctx.select.all'])) {
            $this->queries['ctx.select.all'] = $this->sqlite->prepare("SELECT * FROM contexts");
        }
        $query  = $this->queries['ctx.select.all']->execute();
        while ($result = $query->fetchArray(SQLITE3_ASSOC)) {
            $this->addContext(new Context($result['path'], $result['remote_url'], $result['branch']
                        ?: 'master', $result['remote_name'] ?: 'origin', $result['id'], $result['name']));
        }
    }

    public function register(\Silex\Application $app)
    {
        $app['config'] = $this;
    }
}