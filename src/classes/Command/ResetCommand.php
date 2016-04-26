<?php

namespace GitSync\Command;

/**
 * Reset command generator
 *
 * @author Muhammad Lukman Nasarruddin <anatilmizun@gmail.com>
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

        // only accept method listed in static $methods array, default to 'mixed'
        $method_option = (isset(self::$methods[$method]) ? self::$methods[$method]
                    : self::$methods['mixed']);
        $this->addCommandArgument('--'.$method_option);
        $this->addCommandArgument($ref ? : 'HEAD');

        return $this->getCommand();
    }
}