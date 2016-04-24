<?php

namespace GitSync;

class Config
{
    protected $authServices = array();
    protected $contexts  = array();

    public function addContext($name, \GitSync\Context $config)
    {
        $this->contexts[$name] = $config;
    }

    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     *
     * @param type $name
     * @return \GitSync\Context
     */
    public function getContext($name)
    {
        return (isset($this->contexts[$name]) ? $this->contexts[$name] : null);
    }
}