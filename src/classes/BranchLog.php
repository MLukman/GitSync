<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GitSync;

use GitElephant\Objects\Commit;
use GitElephant\Objects\Log;
use GitElephant\Utilities;
use GitSync\Command\LogGraphCommand;

/**
 * Description of BranchLog
 *
 * @author S52514
 */
class BranchLog implements \ArrayAccess, \Countable, \Iterator
{
    /**
     * @var \GitElephant\Repository
     */
    private $repository;

    /**
     * the commits related to this log
     *
     * @var array
     */
    private $commits = array();

    /**
     * the cursor position
     *
     * @var int
     */
    private $position = 0;

    /**
     * static method to generate standalone log
     *
     * @param \GitElephant\Repository $repository  repo
     * @param array                   $outputLines output lines from command.log
     *
     * @return Log
     */
    public function __construct(\GitElephant\Repository $repository,
                                $ref = 'HEAD', $path = null, $limit = 15,
                                $offset = null, $firstParent = false)
    {
        $this->repository = $repository;
        $this->createFromCommand($ref, $path, $limit, $offset, $firstParent);
    }

    private function createFromCommand($ref, $path, $limit, $offset,
                                       $firstParent)
    {
        $command     = LogGraphCommand::getInstance($this->getRepository())->showLog($ref,
            $path, null, $offset, $firstParent);
        $outputLines = $this->getRepository()->getCaller()->execute($command)->getOutputLines(true);
        $this->parseOutputLines($outputLines, $limit);
    }

    private function parseOutputLines($outputLines, $limit)
    {
        $this->commits = array();
        $commits       = Utilities::pregSplitFlatArray($outputLines,
                '/^['.preg_quote('*_|\ ').']+commit (\w+)$/');
        foreach ($commits as $commitOutputLinesRaw) {
            $commitOutputLines = array();
            if (substr($commitOutputLinesRaw[0], 0, 1) != '*') {
                continue;
            }
            $shift = strpos($commitOutputLinesRaw[0], 'commit');
            foreach ($commitOutputLinesRaw as $line) {
                $commitOutputLines[] = substr($line, $shift);
            }
            $this->commits[] = Commit::createFromOutputLines($this->getRepository(),
                    $commitOutputLines);
            if (count($this->commits) == $limit) {
                break;
            }
        }
    }

    /**
     * Get array representation
     *
     * @return array
     */
    public function toArray()
    {
        return $this->commits;
    }

    /**
     * Get the first commit
     *
     * @return Commit|null
     */
    public function first()
    {
        return $this->offsetGet(0);
    }

    /**
     * Get the last commit
     *
     * @return Commit|null
     */
    public function last()
    {
        return $this->offsetGet($this->count() - 1);
    }

    /**
     * Get commit at index
     *
     * @param int $index the commit index
     *
     * @return Commit|null
     */
    public function index($index)
    {
        return $this->offsetGet($index);
    }

    /**
     * ArrayAccess interface
     *
     * @param int $offset offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->commits[$offset]);
    }

    /**
     * ArrayAccess interface
     *
     * @param int $offset offset
     *
     * @return Commit|null
     */
    public function offsetGet($offset)
    {
        return isset($this->commits[$offset]) ? $this->commits[$offset] : null;
    }

    /**
     * ArrayAccess interface
     *
     * @param int   $offset offset
     * @param mixed $value  value
     *
     * @throws \RuntimeException
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Can\'t set elements on logs');
    }

    /**
     * ArrayAccess interface
     *
     * @param int $offset offset
     *
     * @throws \RuntimeException
     */
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Can\'t unset elements on logs');
    }

    /**
     * Countable interface
     *
     * @return int|void
     */
    public function count()
    {
        return count($this->commits);
    }

    /**
     * Iterator interface
     *
     * @return Commit|null
     */
    public function current()
    {
        return $this->offsetGet($this->position);
    }

    /**
     * Iterator interface
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Iterator interface
     *
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Iterator interface
     *
     * @return bool
     */
    public function valid()
    {
        return $this->offsetExists($this->position);
    }

    /**
     * Iterator interface
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Repository setter
     *
     * @param \GitElephant\Repository $repository the repository variable
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    /**
     * Repository getter
     *
     * @return \GitElephant\Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }
}