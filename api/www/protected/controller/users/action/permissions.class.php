<?php

class action_users_permissions extends response_object{
	protected static function GET($args=NULL){

		return array(array('message'=>'Nothing Bitch!','user_id'=>$args),200);
		//return array($message,$code); 
	}

	protected static function POST(){
		return array('nothing',200);
	}
	protected function do_something(){

	}

}

?>
