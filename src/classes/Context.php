<?php

namespace GitSync;

include_once __DIR__.'/../constants.php';

use GitElephant\Objects\Commit;

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
    protected $branch_name;

    /**
     * Log files directory
     * @var string
     */
    protected $logdir = null;

    /**
     * Criteria to list revisions. If integer then limit by count, otherwise list revisions until a tag with same value is found.
     * @var string|integer
     */
    protected $list_revisions_until = 10;

    /**
     * The HEAD commit
     * @var \GitElephant\Objects\Commit
     */
    private $head = null;

    /**
     * The remote HEAD commit
     * @var \GitElephant\Objects\Commit
     */
    private $remote_head = null;

    /**
     * If the repo is dirty
     * @var boolean
     */
    private $is_dirty = null;

    /**
     * Constructor
     * @param string $path The filesystem path pointing to the directory
     * @param string $remote_url The git remote URL
     * @param string $branch_name The branch name to track, default to 'master'
     * @param string $name The user-friendly name of this context (default to the id)
     * @param string $id The id of this context (default to the last part of the path)
     */
    public function __construct($path, $remote_url, $branch_name = 'master',
                                $remote_name = 'origin', $name = null,
                                $id = null)
    {
        $this->path        = \realpath($path);
        $this->remote_url  = $remote_url;
        $this->remote_name = $remote_name;
        $this->branch_name = $branch_name;
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
    public function getBranchName()
    {
        return $this->branch_name;
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
            $this->remote = $this->getRepo()->getRemote($this->remote_name);
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
        return $this->remote_name;
    }

    /**
     * Get remote branch name
     * @return string
     */
    public function getRemoteBranchName()
    {
        return $this->remote_name.'/'.$this->branch_name;
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
                $repo->addRemote($this->remote_name, $this->remote_url);
                $repo->fetch($this->remote_name, null, false);
                $repo->fetch($this->remote_name, null, true);
                $repo->stage();
                $repo->createBranch($this->branch_name,
                    $this->getRemoteBranchName());
                $this->checkout($this->branch_name);
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
        if (is_null($this->is_dirty)) {
            $this->is_dirty = $this->getRepo()->isDirty();
        }
        return $this->is_dirty;
    }

    /**
     * Check if the current head is the latest commit on the branch
     * @return bool
     */
    public function isLatest()
    {
        $repo   = $this->getRepo();
        $branch = $repo->getBranch($this->branch_name);
        $head   = $this->getHead();
        return $branch->getCurrent() && $head->getSha() == $branch->getSha() && $head->getDatetimeCommitter()
            >= $this->getRemoteHead()->getDatetimeCommitter();
    }

    /**
     * Fetch latest commits from remote
     */
    public function fetch()
    {
        $repo = $this->getRepo();
        $repo->fetch($this->remote_name, null, false);
        $repo->fetch($this->remote_name, null, true);
    }

    /**
     * Checkout specific commit or tag or reference
     * @param string $ref
     */
    public function checkout($ref, $by = null)
    {
        // reset and clean first
        $this->resetAndClean();

        // store old head for auditing
        $old_head = $this->getHead();

        $repo   = $this->getRepo();
        $refSha = $repo->getCommit($ref)->getSha();
        if ($refSha == $repo->getCommit($this->branch_name)->getSha()) {
            // checkout branch name to prevent detached head
            $repo->checkout($this->branch_name);
        } elseif ($refSha == $this->getRemoteHead()->getSha()) {
            // merge fast-forward
            $repo->checkout($this->branch_name)->merge(new RemoteBranch($repo,
                $this->remote_name, $this->branch_name), null, 'ff-only');
        } else {
            // to avoid detached head, create/re-create branch when checkout a commit
            $repo->checkout($ref);
            if ($repo->getBranch(self::GS_BRANCH)) {
                $repo->deleteBranch(self::GS_BRANCH, true);
            }
            $repo->createBranch(self::GS_BRANCH, $ref)->checkout(self::GS_BRANCH);
        }

        // reset and clean again
        $this->resetAndClean();

        // update submodules
        try {
            $repo->updateSubmodule(true, true, true);
        } catch (\Exception $e) {
            // old version of Git <1.8.1.6 don't have --force flag
            $repo->updateSubmodule(true, true);
        }

        $this->head = $this->getRepo()->getCommit('HEAD');
        $this->auditEvent($old_head, $this->head, $by);
    }

    /**
     * "git reset --hard",
     * then "git clean -d -f -f"
     * and then "git submodule foreach git clean -d -f -f"
     */
    public function resetAndClean()
    {
        $repo = $this->getRepo();
        // reset any changes
        if ($this->isDirty()) {
            $repo->reset('HEAD', 'hard');
            $repo->clean(true, true);
        }
    }

    /**
     * Get the HEAD commit
     * @return \GitElephant\Objects\Commit
     */
    public function getHead()
    {
        if (!$this->head) {
            $this->head = $this->getRepo()->getCommit('HEAD');
        }
        return $this->head;
    }

    /**
     * Get the remote HEAD commit
     * @return \GitElephant\Objects\Commit
     */
    public function getRemoteHead()
    {
        if (!$this->remote_head) {
            $this->remote_head = $this->getRepo()->getCommit($this->getRemoteBranchName());
        }
        return $this->remote_head;
    }

    /**
     * Get the list of last few commits in the selected branch
     * @return \GitSync\Revision[]
     */
    public function getLatestRevisions()
    {
        if (is_int($this->list_revisions_until)) {
            $limit    = $this->list_revisions_until;
            $stopwhen = null;
        } else {
            $limit    = 100;
            $stopwhen = $this->list_revisions_until;
        }
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
        $continue  = true;
        foreach ($repo->getLog($this->getRemoteBranchName(), null, $limit) as $commit) {
            if ($continue) {
                $rev = new Revision($commit);
                $sha = $commit->getSHA();
                if (isset($tags[$sha])) {
                    foreach ($tags[$sha] as $tag) {
                        $tagname = $tag->getName();
                        if ($tagname == $stopwhen) {
                            $continue = false;
                        }
                        $rev->addTag($tagname);
                    }
                }
                $revisions[] = $rev;
            }
        }
        return $revisions;
    }

    /**
     * Get list of modifications
     * @param bool $recursive true to recurse submodules; default to false, showing only modified submodule folders
     * @return \GitSync\Modification[]
     */
    public function getModifications($recursive = false)
    {
        $modifications = array();
        $context       = $this;
        $recurse_find  = function($repo, $path) use (&$recurse_find, $context, $recursive, &$modifications) {
            foreach ($repo->getStatus()->all() as $status) {
                $modifications[] = new \GitSync\Modification($this, $status,
                    $path);
                $fullpath        = \realpath($context->getPath().'/'.$path.$status->getName());
                if ($recursive && file_exists($fullpath.'/.git')) {
                    $subrepo = new \GitSync\Repository($fullpath,
                        new \GitElephant\GitBinary(strncasecmp(PHP_OS, 'WIN', 3)
                        == 0 ? '"C:\Program Files\Git\bin\git.exe"' : null));
                    $recurse_find($subrepo,
                        str_replace('\\', '/',
                            substr($fullpath, 1 + strlen($context->getPath()))).'/');
                }
            }
        };
        $recurse_find($this->getRepo(), '');

        return $modifications;
    }

    /**
     * Set log files directory
     * @param string $newlogdir
     */
    public function setLogDir($newlogdir)
    {
        if (is_dir($newlogdir) && is_writable($newlogdir)) {
            $this->logdir = $newlogdir;
        }
    }

    /**
     * Set the criteria to list revisions.
     * If integer then limit by count, otherwise list revisions until a tag with same value is found.
     * @param string|integer $list_revisions_until
     */
    public function setListRevisionUntil($list_revisions_until)
    {
        $this->list_revisions_until = $list_revisions_until;
    }

    protected function getAuditFile()
    {
        return $this->logdir ? $this->logdir.'/'.$this->id.'.log' : null;
    }

    protected function auditEvent(Commit $old_head, Commit $new_head, $by)
    {
        if (($fn   = $this->getAuditFile()) && ($file = \fopen($fn, 'a'))) {
            // event name
            $event = '';
            if (!$old_head) {
                $event = 'INIT';
            } elseif ($new_head->getSha() == $old_head->getSha()) {
                $event = 'RESET';
            } elseif ($new_head->getDatetimeAuthor() > $old_head->getDatetimeAuthor()) {
                $event = 'UPDATE';
            } else {
                $event = 'ROLLBACK';
            }
            if ($new_head->getSha() == $this->getRepo()->getCommit($this->branch_name)) {
                $event .= ' TO LATEST';
            }
            $audit = new Audit($by ? : '-', $event,
                $old_head ? sprintf('%s @ %s - %s', $old_head->getSha(true),
                        $old_head->getDatetimeAuthor()->format('Ymd_Hi'),
                        $old_head->getMessage()) : null,
                sprintf('%s @ %s - %s', $new_head->getSha(true),
                    $new_head->getDatetimeAuthor()->format('Ymd_Hi'),
                    $new_head->getMessage()));
            \fwrite($file, $audit->serialize()."\n");
            \fclose($file);
        }
    }

    public function getAuditLog()
    {
        $auditlog  = array();
        if (($auditfile = @\fopen($this->getAuditFile(), 'r'))) {
            while (($line = fgets($auditfile))) {
                $auditlog[] = \GitSync\Audit::deserialize($line);
            }
            \fclose($auditfile);
        }
        return $auditlog;
    }
}