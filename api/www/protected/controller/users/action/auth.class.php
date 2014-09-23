<?php
/**
 * action_users_auth
 * @author Chimdi Azubuike
 * 
 * Used to CRUD users's authentication in the API
 */
class action_users_auth extends response_object{
	/**
	 * POST
	 * Create a new User
	 * @param $args['username'] - Mandatory
	 * @param $args['password'] - Mandatory
	 * @param $args['password_validation'] - Mandatory
	 * @param $args['first_name'] - Optional
	 * @param $args['last_name'] - Optional
	 * @param $args['primary_department'] - Optional
	 * @param $args['phone'] - Optional
	 * 
	 * @return $op_status
	 */	
	protected static function POST($args){

	}

	/**
	 * PUT
	 * Modify User
	 * User must be authenticated before using put. Session Header must be present else authorized access error (401)
	 * @param $args['password'] - Optional
	 * @param $args['first_name'] - Optional
	 * @param $args['last_name'] - Optional
	 * @param $args['primary_department'] - Optional
	 * @param $args['phone'] - Optional
	 * 
	 * @return $op_status
	 */	
	protected static function PUT($args){

	}

	/**
	 * GET
	 * User Exists
	 * @todo does this need authenticated access to check?
	 * 
	 * @param $args['username']
	 * 
	 * @return boolean
	 */	
	protected static function GET($args){
		$exists = mod_eppctwo_users::exists($args);
		return array($exists,200);
	}

	/**
	 * DELETE
	 * Remove User
	 * Requires Permissions & Authentication
	 * @param $args['username']
	 */
	protected static function DELETE($args){

	}
}
?>