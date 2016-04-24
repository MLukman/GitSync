<?php

namespace GitSync\Service;

class AclManager extends \GitSync\Base\Service
{
    protected $routes = array();
    protected $public = array();

    protected function initialize()
    {

    }

    public function allow($routeName, $role)
    {
        if (is_array($role)) {
            foreach ($role as $r) {
                $this->allow($routeName, $r);
            }
        } elseif (is_array($routeName)) {
            foreach ($routeName as $r) {
                $this->allow($r, $role);
            }
        } else {
            if (!isset($this->routes[$routeName])) {
                $this->routes[$routeName] = array();
            }
            $this->routes[$routeName][$role] = true;
        }
        return $this; // for chain calls
    }

    public function allowAll($routeName)
    {
        if (is_array($routeName)) {
            foreach ($routeName as $r) {
                $this->allowAll($r);
            }
        } else {
            $this->public[$routeName] = true;
        }
        return $this; // for chain calls
    }

    public function checkPermission($routeName, $role)
    {
        if (isset($this->public[$routeName])) {
            return true;
        } else {
            if (isset($this->routes[$routeName]) && isset($this->routes[$routeName][$role])) {
                return true;
            }
            foreach ($this->routes as $route => $roles) {
                if (strpos($routeName, $route) === 0 && isset($roles[$role])) {
                    return true;
                }
            }
        }
        return false;
    }
}