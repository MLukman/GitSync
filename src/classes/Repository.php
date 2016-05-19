<?php

namespace GitSync;

/**
 * Description of Repository
 */
class Repository extends \GitElephant\Repository
{

    public function reset($ref = 'HEAD', $method = 'mixed')
    {
        $this->getCaller()->execute(Command\ResetCommand::getInstance($this)->reset($ref,
                $method), true, null, array(0, 1));

        return $this;
    }

    public function clean($double_force = false)
    {
        $this->getCaller()->execute(Command\CleanCommand::getInstance($this)->clean($double_force),
            true, null, array(0, 1));

        return $this;
    }
}