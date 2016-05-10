<?php

namespace GitSync;

include_once __DIR__.'/../constants.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler as StreamLogger;

/**
 * A Context represents a directory in the server's filesystem that will be
 * managed by a specific list of users via GitSync.
 */
class Context
{

    use \GitSync\Security\SecuredAccessTrait;
    const GS_BRANCH = 'gitsync';

    /**
     * Git repo manager object
     * @var \GitSync\Repository
     */
    protected $repo;

    /**
     *
     * @var string
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $name;

    /**
     * Filesystem path pointing to the directory
     * @var string
     */
    protected $path;

    /**
     * The remote object
     * @var \GitElephant\Objects\Remote
     */
    protected $remote;

    /**
     * The git remote URL
     * @var string
     */
    protected $remote_url;

    /**
     * The git remote name
     * @var string
     */
    protected $remote_name;

    /**
     * The branch name to track
     * @var string
     */
    protected $branch;

    /**
     * Logger
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * Log files directory
     * @var string
     */
    protected $logdir = GITSYNC_LIB_DIR.'/logs';

    /**
     * Constructor
     * @param string $path The filesystem path pointing to the directory
     * @param string $remote_url The git remote URL
     * @param string $branch The branch name to track, default to 'master'
     * @param string $name The user-friendly name of this context (default to the id)
     * @param string $id The id of this context (default to the last part of the path)
     */
    public function __construct($path, $remote_url, $branch = 'master',
                                $name = null, $id = null)
    {
        $this->path       = \realpath($path);
        $this->remote_url = $remote_url;
        $this->branch     = $branch;
        if ($id) {
            $this->id = \preg_replace('/[^a-zA-Z0-9\s]/', '-', $id);
        } else {
            $this->id = \basename($path);
        }
        $this->name    = ($name ? : $this->id);
        $this->logfile = $this->id.'.log';
    }

    /**
     * Get id
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get path
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get branch name
     * @return string
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * Retrieve an instance of \GitSync\Repository object associated with
     * this context's path
     * @return \GitSync\Repository
     */
    public function getRepo()
    {
        if (!$this->repo) {
            $gitbin = null;
            if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
                $gitbin = '"C:\Program Files\Git\bin\git.exe"';
            }
            $this->repo = new \GitSync\Repository($this->path,
                new \GitElephant\GitBinary($gitbin)
            );
        }
        return $this->repo;
    }

    /**
     * Retrieve an instance of \GitElephant\Objects\Remote object associated with
     * this context's current git repository state
     * @return \GitElephant\Objects\Remote
     * @throws \Exception
     */
    public function getRemote()
    {
        if (!$this->remote) {
            $this->remote = $this->getRepo()->getRemote('origin');
        }
        return $this->remote;
    }

    /**
     * Get remote URL
     * @return string
     */
    public function getRemoteUrl()
    {
        return $this->remote_url;
    }

    /**
     * Get remote name
     * @return string
     */
    public function getRemoteName()
    {
        if (!$this->remote_name) {
            $this->remote_name = $this->getRemote()->getName();
        }
        return $this->remote_name;
    }

    /**
     * Get remote branch name
     * @return string
     */
    public function getRemoteBranch()
    {
        return $this->getRemoteName().'/'.$this->branch;
    }

    /**
     * Check if the directory has been initialized as a git repo
     * @param bool $autoinit True to auto-initialized using remote url
     * @return boolean
     * @throws \Exception
     */
    public function isInitialized($autoinit = false)
    {
        $repo = $this->getRepo();
        try {
            $repo->getIndexStatus();
        } catch (\Exception $e) {
            if (!strpos($e->getMessage(), 'Not a git repository')) {
                throw $e;
            }
            if (!$autoinit) {
                return false;
            }
            $repo->init();
            try {
                $repo->addRemote('origin', $this->remote_url);
                $repo->fetch('origin');
                $repo->fetch('origin', null, true);
                $repo->createBranch($this->branch, $this->getRemoteBranch());
                $repo->stage();
                $repo->checkout($this->branch);
                $repo->reset();
            } catch (\Exception $e2) {
                $fs = new \Symfony\Component\Filesystem\Filesystem();
                $fs->remove(realpath($this->path.'/.git/'));
                throw $e2;
            }
        }
        return true;
    }

    /**
     * Check if the working directory tree has modifications
     * @return bool
     */
    public function isDirty()
    {
        return $this->getRepo()->isDirty();
    }

    /**
     * Check if the current head is the latest commit on the branch
     * @return bool
     */
    public function isLatest()
    {
        $repo   = $this->getRepo();
        $branch = $repo->getBranch($this->branch);
        return $branch->getCurrent() && $this->getHead()->getSha() == $repo->getCommit($this->getRemoteBranch())->getSha();
    }

    /**
     * Log message
     * @param int $level One of the constants in \Monolog\Logger class (e.g Logger::INFO)
     * @param string $message The message
     * @param array $context Parameters
     * @param string $uid User id
     */
    public function log($level, $message, array $context = array(), $uid = null)
    {
        if (!$this->logger) {
            $this->logger = new Logger('logger',
                array(
                new StreamLogger($this->logdir.'/'.$this->logfile)));
        }
        if ($uid) {
            $context['userid'] = $uid;
        }
        $this->logger->log($level, $message, $context);
    }

    /**
     * Fetch latest commits from remote
     */
    public function fetch()
    {
        $this->getRepo()->fetch($this->getRemoteName(), $this->branch, true);
    }

    /**
     * Checkout specific commit or tag or reference
     * @param string $ref
     */
    public function checkout($ref, $new_branch = null)
    {
        $repo = $this->getRepo();
        // reset any changes
        if ($repo->isDirty()) {
            $repo->reset('HEAD', 'hard');
            $repo->clean();
        }

        // checkout branch name to prevent detached head
        if ($repo->getCommit($ref)->getSha() == $repo->getBranch($this->branch)->getSha()) {
            $repo->checkout($this->branch);
            if ($repo->getBranch(self::GS_BRANCH)) {
                $repo->deleteBranch(self::GS_BRANCH, true);
            }
        } else {
            // to avoid detached head, create/re-create branch when checkout a commit
            $repo->checkout($ref);
            if ($repo->getBranch(self::GS_BRANCH)) {
                $repo->deleteBranch(self::GS_BRANCH, true);
            }
            $repo->createBranch(self::GS_BRANCH, $ref);
            $repo->checkout(self::GS_BRANCH);
        }

        // update submodules
        try {
            $repo->updateSubmodule(true, true, true);
        } catch (\Exception $e) {
            // old version of Git <1.8.1.6 don't have --force flag
            $repo->updateSubmodule(true, true);
        }
        $this->log(Logger::INFO, "Successfully sync directory with a revision",
            array('ref' => $ref));
    }

    /**
     * Get the HEAD commit
     * @return \GitElephant\Objects\Commit
     */
    public function getHead()
    {
        return $this->getRepo()->getCommit('HEAD');
    }

    /**
     * Get the list of last few commits in the selected branch
     * @param int $limit Number of commits to return, default to 10
     * @return \GitSync\Revision[]
     */
    public function getLatestRevisions($limit = 10)
    {
        $repo = $this->getRepo();
        $tags = array();
        foreach ($repo->getTags() as $tag) {
            $sha = $tag->getSha();
            if (!isset($tags[$sha])) {
                $tags[$sha] = array();
            }
            $tags[$sha][] = $tag;
        }
        $revisions = array();
        foreach ($repo->getLog($this->getRemoteBranch(), null, $limit) as $commit) {
            $rev = new Revision($commit);
            $sha = $commit->getSHA();
            if (isset($tags[$sha])) {
                foreach ($tags[$sha] as $tag) {
                    $rev->addTag($tag->getName());
                }
            }
            $revisions[] = $rev;
        }
        return $revisions;
    }

    public function setLogDir($newlogdir)
    {
        if (is_dir($newlogdir) && is_writable($newlogdir)) {
            $this->logdir = $newlogdir;
        }
    }
}