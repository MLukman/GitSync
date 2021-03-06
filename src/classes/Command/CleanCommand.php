<?php

namespace GitSync\Command;

/**
 * Description of CleanCommand
 */
class CleanCommand extends \GitElephant\Command\BaseCommand
{
    const GIT_CLEAN = 'clean';

    public function clean($double_force = false)
    {
        $this->clearAll();
        $this->addCommandName(self::GIT_CLEAN);
        $this->addCommandArgument('-d');
        $this->addCommandArgument('-f');
        if ($double_force) {
            $this->addCommandArgument('-f');
        }

        return $this->getCommand();
    }
}