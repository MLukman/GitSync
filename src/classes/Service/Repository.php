<?php

namespace GitSync\Service;

use GitElephant\Repository as GitRepo;

class Repository extends \GitSync\Base\Service
{
    protected $activeRepo = null;

    protected function initialize()
    {
        
    }

    public function setActiveRepository(\GitSync\Context $context = null)
    {
        if ($context) {
            $this->activeRepo = new GitRepo('/path/to/git/repository');

        }else {
        $this->activeRepo = null;
        }
    }

    protected function checkActiveRepo()
    {
        if (!$this->activeRepo) {
            throw new Exception("There is no active repository", 500);
        }
    }
}