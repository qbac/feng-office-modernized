<?php

  /**
  * PermissionGroups
  *
  * @author Diego Castiglioni <diego.castiglioni@fengoffice.com>
  */
  class PermissionGroups extends BasePermissionGroups {
    
    static function getNonPersonalPermissionGroups($order = '`name` ASC') {
    	return self::instance()->findAll(array("conditions" => "`contact_id` = 0 AND `parent_id` != 0 AND `type`='roles'", "order" => $order));
    }
    static function getNonPersonalSameLevelPermissionsGroups($order = '`name` ASC') {
    	return self::instance()->findAll(array("conditions" => "`contact_id` = 0 AND `parent_id` != 0 AND `type`='roles' AND `id` >= ".logged_user()->getUserType(), "order" => $order));
    }
    static function getParentId($group_id){
    	return self::instance()->findById($group_id)->getParentId();
    }
    
    static function getGuestPermissionGroups() {
    	return self::instance()->findAll(array("conditions" => "parent_id IN (SELECT p.id FROM ".TABLE_PREFIX."permission_groups p WHERE p.name='GuestGroup')"));
    }
    
    static function getNonRolePermissionGroups() {
		$order = '`name` ASC';
        return self::instance()->findAll(array("conditions" => "`type` = 'user_groups'",  "order" => $order));
    }
    
  } // PermissionGroups 

?>