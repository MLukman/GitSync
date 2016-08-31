<?php

namespace GitSync\Command;

/**
 * Log command with --graph argument
 */
class LogGraphCommand extends \GitElephant\Command\LogCommand
{

    public function showLog($ref, $path = null, $limit = null, $offset = null,
                            $firstParent = false)
    {
        parent::showLog($ref, $path, $limit, $offset, $firstParent);
        $this->addCommandArgument('--graph');
        return $this->getCommand();
    }
}