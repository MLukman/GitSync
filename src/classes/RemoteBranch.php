<?php

namespace GitSync;

use GitElephant\Command\BranchCommand;
use GitElephant\Exception\InvalidBranchNameException;

/**
 * An object representing a git remote branch
 *
 * @author MLukman <anatilmizun@gmail.com>
 */
class RemoteBranch extends \GitElephant\Objects\Branch
{

    /**
     * Class constructor
     *
     * @param \GitElephant\Repository $repository
     * @param string $remote_name Remote name, e.g. 'origin'
     * @param string $branch_name Branch name, e.g. 'master'
     * 
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \GitElephant\Exception\InvalidBranchNameException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function __construct(\GitElephant\Repository $repository,
                                $remote_name = 'origin', $branch_name = 'master')
    {
        $this->repository = $repository;
        $this->setName(trim($remote_name).'/'.trim($branch_name));
        $this->setFullRef('refs/remotes/'.$this->getName());
        $this->createFromCommand();
    }

    /**
     * Get the branch properties from command
     *
     * @throws \InvalidArgumentException
     */
    private function createFromCommand()
    {
        $branchName  = 'remotes/'.$this->getName();
        $command     = BranchCommand::getInstance($this->getRepository())->listBranches(true);
        $outputLines = $this->repository->getCaller()->execute($command)->getOutputLines(true);
        foreach ($outputLines as $outputLine) {
            $matches = static::getMatches($outputLine);
            if ($branchName === $matches[1]) {
                $this->parseOutputLine($outputLine);
                return;
            }
        }
        throw new InvalidBranchNameException(sprintf('The %s branch doesn\'t exists',
            $branchName));
    }
}