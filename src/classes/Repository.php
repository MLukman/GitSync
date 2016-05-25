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

    public function clean($double_force = false, $clean_submodule = false)
    {
        $clean_cmd = Command\CleanCommand::getInstance($this)->clean($double_force);
        $this->getCaller()->execute($clean_cmd, true, null, array(0, 1));

        if ($clean_submodule) {
            $this->foreachSubmodule($clean_cmd);
        }

        return $this;
    }

    public function foreachSubmodule($cmd)
    {
        $this->getCaller()->execute(Command\SubmoduleCommand::getInstance($this)->foreachCmd($cmd),
            true, null, array(0, 1));

        return $this;
    }
}