<?php

namespace GitSync\Auth;

use Securilex\Authentication\Factory\AuthenticationFactoryInterface;
use Securilex\Authentication\User\MutableUserInterface;

abstract class AuthMethodBase
{
    protected $params = array();

    /**
     * @return string Friendly name of the authentication method
     */
    static public function getName()
    {
        return basename(get_class());
    }

    /**
     * @return array Parameters = array of array(id, label, placeholder)
     */
    static public function getParamConfigs()
    {
        return array();
    }

    /**
     * Check if params are valid.
     * Return array of error messages using param names as keys.
     * Return null if no error.
     * @param array $params
     * @return array|null
     */
    static public function checkParams(array $params)
    {
        return null;
    }

    public function __construct(array $params)
    {
        if (!empty($this->checkParams($params))) {
            throw new \Exception('Invalid parameters');
        }
        $this->params = $params;
    }

    /**
     * @return AuthenticationFactoryInterface
     */
    abstract public function getAuthenticationFactory();

    /**
     * Handle first time login from setup page
     * @param type $username
     * @param type $password
     * @return string Empty string (or null) if no error, otherwise return error string
     */
    abstract public function firstLogin(MutableUserInterface $user);

    abstract public function prepareNewUser(MutableUserInterface $user);
}