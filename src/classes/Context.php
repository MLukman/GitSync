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
     * @var \GitElephant\Repository
     */
    protected $repo;

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
     * Constructor
     * @param string $path The filesystem path pointing to the directory
     * @param string $remote_url The git remote URL
     * @param string $branch The branch name to track, default to 'master'
     */
    public function __construct($path, $remote_url, $branch = 'master')
    {
        $this->path        = realpath($path);
        $this->remote_url  = $remote_url;
        $this->branch      = $branch;
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
     * Retrieve an instance of \GitElephant\Repository object associated with
     * this context's path
     * @return \GitElephant\Repository
     */
    public function getRepo()
    {
        if (!$this->repo) {
            $gitbin = null;
            if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
                $gitbin = '"C:\Program Files\Git\bin\git.exe"';
            }
            $this->repo = new \GitElephant\Repository($this->path,
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
                $repo->hardReset('origin/'.$this->branch);
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
        return $repo->getCommit('HEAD') == $repo->getLog('master', null, 1)->first();
    }
}