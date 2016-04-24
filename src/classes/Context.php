<?php

namespace GitSync;

class Context
{
    protected $path;
    protected $repo;
    protected $remote_url;
    protected $branch;

    public function __construct($path, $remote_url, $branch = 'master')
    {
        $this->path       = realpath($path);
        $this->remote_url = $remote_url;
        $this->branch     = $branch;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getRemoteUrl()
    {
        return $this->remote_url;
    }

    public function getBranch()
    {
        return $this->branch;
    }

    /**
     *
     * @return \GitElephant\Repository
     */
    public function getRepo()
    {
        if (!$this->repo) {
            $this->repo = new \GitElephant\Repository($this->path,
                new \GitElephant\GitBinary('"C:\Program Files\Git\bin\git.exe"')
            );
        }
        return $this->repo;
    }

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
}