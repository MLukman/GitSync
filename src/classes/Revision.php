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

    /**
     * Construct an instance based on a Commit object
     * @param \GitElephant\Objects\Commit $commit
     */
    public function __construct(\GitElephant\Objects\Commit $commit)
    {
        $this->commit = $commit;
    }

    /**
     * Get the original Commit object
     * @return \GitElephant\Objects\Commit
     */
    public function getCommit()
    {
        return $this->commit;
    }

    /**
     * Get the revision date
     * @return \DateTime
     */
    public function getDate()
    {
        $date = $this->commit->getDatetimeCommitter();
        $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        return $date;
    }

    /**
     * Get committer
     * @return \GitElephant\Objects\Author Committer object
     */
    public function getCommitter() {
        return $this->commit->getCommitter();
    }

    /**
     * Get the revision message
     * @return string
     */
    public function getMessage()
    {
        return $this->commit->getMessage();
    }

    /**
     * Get the revision tag, if any, default to the original Commit SHA digest
     * @param boolean $short Only if commit SHA digest is to be returned, true to get only first 8 characters, false to return all 40 characters
     * @return string
     */
    public function getRef($short = false)
    {
        $tags = $this->getTags();
        return (count($tags) ? $tags[0] : $this->getSHA($short));
    }

    /**
     * Get the revision SHA digest
     * @param boolean $short True to get only first 8 characters, false to return all 40 characters
     * @return string
     */
    public function getSHA($short = false)
    {
        return $this->commit->getSha($short);
    }

    /**
     * Add a tag to this revision
     * @param string $tag Tag name
     */
    public function addTag($tag)
    {
        $this->tags[$tag] = true;
    }

    /**
     * Get all tags for this revision
     * @return string[]
     */
    public function getTags()
    {
        return array_keys($this->tags);
    }
}