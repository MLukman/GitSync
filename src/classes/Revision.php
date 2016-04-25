<?php

namespace GitSync;

class Revision
{
    /**
     *
     * @var \GitElephant\Objects\Commit
     */
    protected $commit;

    /**
     *
     * @var string[]
     */
    protected $tags = array();

    public function __construct(\GitElephant\Objects\Commit $commit)
    {
        $this->commit = $commit;
    }

    public function getDate()
    {
        return $this->commit->getDatetimeCommitter();
    }

    public function getSHA($short = false)
    {
        return $this->commit->getSha($short);
    }

    public function getMessage()
    {
        return $this->commit->getMessage();
    }

    public function getCommit()
    {
        return $this->commit;
    }

    public function addTag($tag)
    {
        $this->tags[$tag] = true;
    }

    public function getTags()
    {
        return array_keys($this->tags);
    }
}