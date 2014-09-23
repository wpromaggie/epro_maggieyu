<?php
/**
 * action_users_users
 */
class action_users_users extends response_object{
	protected static function PUT($args=NULL){

		return array(array('message'=>'Get--','user_id'=>$args),200);
		//return array($message,$code); 
	}

	protected static function GET($args=NULL){
		// BEFORE
		//$r = db::query("SHOW TABLES","ASSOC");
		// AFTER
		if($args)
			$r = mod_eppctwo_users::get_user($args);
		else	
			$r = mod_eppctwo_users::get_all_users();

		if($r){
			return array($r,200);
		}else{
			return array(false,500);
		}
	}

	protected function do_something(){

	}

}

?>
