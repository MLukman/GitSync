<?php

namespace GitSync\Security;

trait SecuredAccessTrait
{
    /**
     * The list of user roles who can access
     * @var string[]
     */
    protected $allowedRoles = array('ROLE_ADMIN');

    /**
     * The list of user ids who can manage this context
     * @var string[]
     */
    protected $allowedUids = array();

    /**
     * Allow user role to access
     * @param string $role User role to allow access
     * @return self $this object (to allow method chaining)
     */
    public function addAllowedRole($role)
    {
        $this->allowedRoles[] = $role;
        return $this;
    }

    /**
     * Check if a specific user role is allowed to access
     * @param string $role User role
     * @return bool
     */
    public function isRoleAllowed($role)
    {
        return $role ? in_array($role, $this->allowedRoles) : false;
    }

    /**
     * Allow user id to access
     * @param string $uid User id to allow access
     * @return self $this object (to allow method chaining)
     */
    public function addAllowedUid($uid)
    {
        $this->allowedUids[] = $uid;
        return $this;
    }

    /**
     * Check if a specific user id is allowed to access this context
     * @param string $uid User id
     * @return bool
     */
    public function isUidAllowed($uid)
    {
        return $uid ? in_array($uid, $this->allowedUids) : false;
    }
}