<?php

namespace GitSync;

include_once __DIR__.'/../constants.php';

use GitElephant\GitBinary;
use GitElephant\Objects\Commit;
use GitElephant\Objects\Remote;
use Securilex\Authorization\SecuredAccessInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A Context represents a directory in the server's filesystem that will be
 * managed by a specific list of users via GitSync.
 */
class Context implements SecuredAccessInterface, \Serializable
{

    use \Securilex\Authorization\SecuredAccessTrait;
    /**
     * The temporary branch name to create if user sync with a commit that is not a head of any branch
     */
    const GS_BRANCH = 'gitsync';

    /**
     * Git repo manager object
     * @var Repository
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
     * @var Remote
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
     * Criteria to list revisions. If integer then limit by count, otherwise list revisions until a tag with same value is found.
     * @var string|integer
     */
    protected $list_revisions_until = 10;

    /**
     * The HEAD commit
     * @var Commit
     */
    private $head = null;

    /**
     * The remote HEAD commit
     * @var Commit
     */
    private $remote_head = null;

    /**
     * If the repo is initialized
     * @var boolean
     */
    private $is_initialized = null;

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
     * @param string $id The id of this context (default to the last part of the path)
     * @param string $name The user-friendly name of this context (default to the id)
     */
    public function __construct($path, $remote_url, $branch_name = 'master',
                                $remote_name = 'origin', $id = null,
                                $name = null)
    {
        if (($realpath = \realpath($path))) {
            $this->path = $realpath;
        } else if (mkdir($path, 0755, true)) {
            $this->path = \realpath($path);
        }
        $this->remote_url  = $remote_url;
        $this->remote_name = $remote_name;
        $this->branch_name = $branch_name;
        if ($id) {
            $this->id = \preg_replace('/[^a-zA-Z0-9\s]/', '-', $id);
        } else {
            $this->id = \basename($path);
        }
        $this->name = ($name ?: $this->id);
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
     * @return Repository
     */
    public function getRepo()
    {
        if (!$this->repo) {
            $gitbin = null;
            if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
                $gitbin = '"C:\Program Files\Git\bin\git.exe"';
            }

            $this->repo = new Repository($this->path, new GitBinary($gitbin));
        }
        return $this->repo;
    }

    /**
     * Retrieve an instance of \GitElephant\Objects\Remote object associated with
     * this context's current git repository state
     * @return Remote
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
     * @return boolean
     * @throws \Exception
     */
    public function isInitialized()
    {
        $this->checkStatus();
        return $this->is_initialized;
    }

    public function initialize($by = null)
    {
        if ($this->isInitialized()) {
            return;
        }
        $repo = $this->getRepo();
        $repo->init();
        try {
            $repo->addRemote($this->remote_name, $this->remote_url);
            $repo->fetch($this->remote_name, null, false);
            $repo->fetch($this->remote_name, null, true);
            $repo->stage();
            $repo->createBranch($this->branch_name, $this->getRemoteBranchName());
            $this->checkout($this->branch_name, $by);
        } catch (\Exception $e2) {
            $fs = new Filesystem();
            $fs->remove($this->path.'/.git/');
            throw $e2;
        }
        $this->is_initialized = true;
    }

    /**
     * Check if the working directory tree has modifications
     * @return bool
     */
    public function isDirty()
    {
        $this->checkStatus();
        return $this->is_dirty;
    }

    protected function checkStatus()
    {
        if (is_null($this->is_dirty)) {
            try {
                $this->is_dirty       = $this->getRepo()->isDirty();
                $this->is_initialized = file_exists($this->path.'/.git');
            } catch (\Exception $e) {
                if (!strpos($e->getMessage(), 'Not a git repository')) {
                    throw $e;
                }
                $this->is_dirty       = false;
                $this->is_initialized = false;
            }
        }
    }

    /**
     * Check if the current head is the latest commit on the branch
     * @return bool
     */
    public function isLatest()
    {
        if (!$this->isInitialized()) {
            return true;
        }
        $repo   = $this->getRepo();
        $branch = $repo->getBranch($this->branch_name);
        $head   = $this->getHead();
        return ($branch && $branch->getCurrent() && $head->getSha() == $branch->getSha())
            && $head->getDatetimeCommitter() >= $this->getRemoteHead()->getDatetimeCommitter();
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
        $branch = $repo->getBranch($this->branch_name);
        if (!$branch && $refSha == $repo->getCommit($this->getRemoteBranchName())->getSha()) {
            // if checkout to the head of a remote branch with no local branch yet
            $repo->createBranch($this->branch_name, $this->getRemoteBranchName());
            $repo->checkout($this->branch_name);
        } elseif ($refSha == $repo->getBranch($this->branch_name)->getSha()) {
            // checkout branch name to prevent detached head
            $repo->checkout($this->branch_name);
        } elseif ($refSha == $this->getRemoteHead()->getSha()) {
            // merge fast-forward
            $repo->checkout($this->branch_name)->merge(new RemoteBranch($repo, $this->remote_name, $this->branch_name), null, 'ff-only');
        } else {
            // to avoid detached head, create/re-create branch when checkout a commit
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
        $this->auditEvent($this->head, $old_head, $by);

        // store latest sha & tags
        $fn          = $this->path.'/.git/gitsync.latest';
        $data        = (file_exists($fn) ? json_decode(file_get_contents($fn), true)
                : array());
        $data['sha'] = $repo->getCommit()->getSha();
        $tags        = array();
        foreach ($repo->getTags() as $tag) {
            if ($tag->getSha() == $data['sha']) {
                $tags[] = $tag->getName();
            }
        }
        if (!empty($tags)) {
            $data['tags'] = $tags;
        }
        file_put_contents($fn, json_encode($data));
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
     * @return Commit
     */
    public function getHead()
    {
        if (!$this->head && $this->isInitialized()) {
            $this->head = $this->getRepo()->getCommit('HEAD');
        }
        return $this->head;
    }

    /**
     * Get the remote HEAD commit
     * @return Commit
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
     * @return Revision[]
     */
    public function getLatestRevisions()
    {
        if (is_int($this->list_revisions_until)) {
            $limit    = $this->list_revisions_until;
            $stopwhen = null;
        } else {
            $limit    = null;
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
        $branchlog = new BranchLog($repo, $this->getRemoteBranchName(), null, $limit);
        foreach ($branchlog as $commit) {
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
     * @return Modification[]
     */
    public function getModifications($recursive = false)
    {
        $modifications = array();
        $context       = $this;
        $recurse_find  = function($repo, $path) use (&$recurse_find, $context, $recursive, &$modifications) {
            foreach ($repo->getStatus()->all() as $status) {
                $modifications[] = new Modification($this, $status, $path);
                $fullpath        = \realpath($context->getPath().'/'.$path.$status->getName());
                if ($recursive && file_exists($fullpath.'/.git')) {
                    $subrepo = new Repository($fullpath, new GitBinary(strncasecmp(PHP_OS, 'WIN', 3)
                        == 0 ? '"C:\Program Files\Git\bin\git.exe"' : null));
                    $recurse_find($subrepo, str_replace('\\', '/', substr($fullpath, 1
                                + strlen($context->getPath()))).'/');
                }
            }
        };
        $recurse_find($this->getRepo(), '');

        return $modifications;
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
        return $this->path.'/.git/gitsync.log';
    }

    protected function auditEvent(Commit $new_head, Commit $old_head = null,
                                  $by = null)
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
            $audit = new Audit($by ?: '-', $event, $old_head ? sprintf('%s @ %s - %s', $old_head->getSha(true), $old_head->getDatetimeAuthor()->format('Ymd_Hi'), $old_head->getMessage())
                    : null, sprintf('%s @ %s - %s', $new_head->getSha(true), $new_head->getDatetimeAuthor()->format('Ymd_Hi'), $new_head->getMessage()));
            \fwrite($file, $audit->serialize()."\n");
            \fclose($file);
        }
    }

    public function getAuditLog()
    {
        $auditlog  = array();
        if (($auditfile = @\fopen($this->getAuditFile(), 'r'))) {
            while (($line = fgets($auditfile))) {
                $auditlog[] = Audit::deserialize($line);
            }
            \fclose($auditfile);
        }

        return array_reverse($auditlog);
    }

    public function serialize()
    {
        return serialize(array(
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'remote_url' => $this->remote_url,
            'remote_name' => $this->remote_name,
            'branch_name' => $this->branch_name,
            'allowedRoles' => $this->allowedRoles,
            'allowedUsernames' => $this->allowedUsernames,
        ));
    }

    public function unserialize($serialized)
    {
        $arr                    = unserialize($serialized);
        $this->id               = $arr['id'];
        $this->name             = $arr['name'];
        $this->path             = $arr['path'];
        $this->remote_url       = $arr['remote_url'];
        $this->remote_name      = $arr['remote_name'];
        $this->branch_name      = $arr['branch_name'];
        $this->allowedRoles     = $arr['allowedRoles'];
        $this->allowedUsernames = $arr['allowedUsernames'];
    }
}