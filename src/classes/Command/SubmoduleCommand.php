<?php

namespace GitSync\Command;

/**
 * Description of SubmoduleCommand
 *
 * @author S52514
 */
class SubmoduleCommand extends \GitElephant\Command\SubmoduleCommand
{
    const SUBMODULE_FOREACH_COMMAND = 'foreach';

    public function foreachCmd($cmd)
    {
        $this->clearAll();
        $this->addCommandName(sprintf('%s %s', self::SUBMODULE_COMMAND,
                self::SUBMODULE_FOREACH_COMMAND));
        $this->addCommandArgument(cmd);

        return $this->getCommand();
    }
}