<?php

namespace GitSync\Auth;

use Securilex\Authentication\Factory\BCryptAuthenticationFactory;
use Securilex\Authentication\User\MutableUserInterface;

class PasswordAuthMethod extends AuthMethodBase
{
    protected $factory = null;

    public function __construct(array $params)
    {
        parent::__construct($params);
        $this->factory = new BCryptAuthenticationFactory();
    }

    public function firstLogin(MutableUserInterface $user)
    {
        $this->prepareNewUser($user);
        return null;
    }

    public function getAuthenticationFactory()
    {
        return $this->factory;
    }

    static public function getName()
    {
        return 'Simple username & password';
    }

    static public function getParamConfigs()
    {
        return array();
    }

    static public function checkParams(array $params)
    {
        return null;
    }

    public function prepareNewUser(MutableUserInterface $user)
    {
        $this->factory->encodePassword($user, $user->getPassword());
    }
}