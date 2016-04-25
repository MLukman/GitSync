<?php

namespace GitSync\Command;

/**
 * Description of ResetCommand
 */
class ResetCommand extends \GitElephant\Command\BaseCommand
{
    const GIT_RESET = 'reset';

    static protected $methods = array(
        'mixed',
        'soft',
        'hard',
        'merge',
        'keep');

    public function reset($ref = 'HEAD', $method = 'mixed')
    {
        $this->clearAll();
        $this->addCommandName(self::GIT_RESET);
        if ($hard) {
            $method_option = (isset(self::$methods[$method]) ? self::$methods[$method]
                        : self::$methods['mixed']);
            $this->addCommandArgument('--'.$method_option);
        }
        $this->addCommandArgument($ref ? : 'HEAD');

        return $this->getCommand();
    }
}