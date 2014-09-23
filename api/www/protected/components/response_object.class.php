<?php
use Slim\Slim;

class response_object{
	protected static $ac; //allowable conditions
	protected static $body;


	public static function __callStatic($method,$args){
		self::$body = self::get_body();
		list($message, $status_code) = call_user_func_array(array(get_called_class(),$method),$args);

		//
		$app = Slim::getInstance();
		$app->response->headers->set('Content-Type','application/json');
		$app->response->headers->set("Access-Control-Allow-Origin: *");
		$app->response->setStatus($status_code);

		echo json_encode(array('message'=>$message,'status'=>$status_code,'server_time'=>date('UTC')));
	}

	/**
	 * 
	 */
	protected static function parse_conditions($args){
		$conds = explode('&',$args);
		$allowed = array();
		foreach($conds as $cond){
			if(!preg_match("/\=/",$cond))
				continue;

			$kv = explode('=',$cond); //key=value
			if(!isset(self::$ac[$kv[0]]))
				self::$ac[$kv[0]] = $kv[1];

		}
	}

	/**
	 * get_body()
	 * 
	 * is called on every response via the decorator function __callStatic
	 * the results is passed to a protected variable self::$body for use 
	 * by any subclassed object
	 * 
	 * Transforms www-form and json to associateive array
	 *
	 * @return mixed[]
	 */
	protected static function get_body(){
		$app = Slim::getInstance();
	        // FORM
	    if($app->request->getContentType() === "application/x-www-form-urlencoded"){
            // work only with application/x-www-form-urlencoded
            if(strtolower($method) === 'post')
                $body = $app->request->post();
            else
                $body = $app->request->put();
        }else{
			// Assuming JSON but it works for every content type
			if($app->request->getContentType() === 'application/json')
				$body = json_decode($app->request->getBody(),true);
			else
				$body = $app->request->getBody();
		}
			return $body;
	}//end of get_body method


}
?>