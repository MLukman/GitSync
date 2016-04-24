<?php

namespace GitSync\Base;

/**
 * Base class for all services
 */
abstract class Service
{
    protected $app;

    public function __construct(\Silex\Application $app)
    {
        $this->app = $app;
        $this->initialize();
    }

    abstract protected function initialize();
}