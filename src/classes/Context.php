<?php

namespace GitSync;

/**
 * A Context represents a directory in the server's filesystem that will be
 * managed by a specific list of users via GitSync.
 */
class Context
{
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
     * The git remote URL
     * @var string
     */
    protected $remote_url;

    /**
     * The branch name to track
     * @var string
     */
    protected $branch;

    /**
     * The list of user ids who can manage this context
     * @var string[]
     */
    protected $allowedUids;

    /**
     * Constructor
     * @param string $path The filesystem path pointing to the directory
     * @param string $remote_url The git remote URL
     * @param string $branch The branch name to track, default to 'master'
     * @param array $allowedUids The list of user ids who can manage this context
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
        $this->name = ($name ? : $this->id);
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
     * Get remote URL
     * @return string
     */
    public function getRemoteUrl()
    {
        return $this->remote_url;
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
        $repo = $this->getRepo();
        try {
            return $repo->getRemote('gitsync');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), "remote doesn't exist")) {
                return $repo->getRemote('origin');
            }
            throw $e;
        }
    }

    /**
     * Allow user id to access this context
     * @param string $uid User id to allow access
     */
    public function addAllowedUid($uid)
    {
        $this->allowedUids[] = $uid;
    }

    /**
     * Check if a specific user id is allowed to access this context
     * @param string $uid User id
     * @return bool
     */
    public function isUidAllowed($uid)
    {
        return in_array($uid, $this->allowedUids);
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
                $repo->reset('origin/'.$this->branch, 'hard');
                $repo->checkout($this->branch);
                $repo->updateSubmodule(true, true, true);
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
        $repo = $this->getRepo();
        return $repo->getCommit('HEAD') == $repo->getBranch($this->branch)->getLastCommit();
    }

    public function fetch()
    {
        $this->getRepo()->fetch($this->getRemote()->getName());
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
        foreach ($repo->getLog($this->branch, null, $limit) as $commit) {
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
}