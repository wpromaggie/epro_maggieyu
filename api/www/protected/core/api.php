<?php
require (dirname(__FILE__).'/vendor/autoload.php');
require(dirname(__FILE__).'/../../../../common/env.php');


//(\epro\COMMON_PATH.'log.php');
require(\epro\COMMON_PATH.'db.php');
//require(\epro\COMMON_PATH.'rs/rs.php'); @TODO: migrate rs to new autoload then reverse integrate with legacy cgi
require(\epro\COMMON_PATH.'util.php');

define('epro\API_ROOT', \epro\API_PATH.'www/');
define('epro\API_PROTECTED', \epro\API_ROOT.'protected/');
define('epro\API_CONTROLELR', \epro\API_PROTECTED.'controller/');
define('epro\API_CORE', \epro\API_PROTECTED.'core/');

//Temporary RS migation - after update return to \epro\COMMON_PATH
require(\epro\API_CORE.'rs/rs.php');

use Slim\Slim;

/**
 * class api
 * child class of \Slim\Slim initializes database and necessary dependencies
 */
class api extends \Slim\Slim{
    private static $start_time, $user, $is_error, $uri;

    protected $req_method;
    protected $routes_tree = array(); //routes linked list
    protected $exact_match_route = array();

    public function __construct(array $userSettings = array()){
        api::init();
		set_time_limit(0);
		db::connect(\epro\DB_HOST, \epro\DB_USER, \epro\DB_PASS, \epro\DB_NAME);
        
        parent::__construct($userSettings);
        $this->req_method = $_SERVER['REQUEST_METHOD'];

        spl_autoload_register(array('api','enet_autoload'));
        register_shutdown_function(array('api','api_shutdown'));
    }   

    public static function api_shutdown(){
        $now = microtime(true);
        $req_data = array(
                'interface'=>'api',
                'user'=>self::$user,
                'context'=>'rest',
                'start_time'=>self::$start_time,
                'end_time'=>$now,
                'elapsed'=>($now - self::$start_time),
                'hostname'=>gethostname(),
                'max_memory'=>memory_get_peak_usage(),
                'url'=>$_SERVER['REQUEST_URI']
        );
        mod_log_request::create($req_data);
    }
    
	public static function init(){
        self::$start_time = microtime(true);
	}


    /**
     * add_route()
     * 
     * Add Route allows the \Slim\Slim to pre-identify a route at runtime without executing
     * the underlying route request method \Slim\Slim::mapRoute()
     * add_route() initializes 2 types of arrays.
     * 1. hash map of the url for direct match lookup O(1)
     * 2. linked list builder that assembles a tree where the path represents a node in the tree
     *      This is characterized by the use of a pointer that reference the current position
     *      in the tree until it find the end of the url path array or it finds a url variable 
     *      denoted by ":string"
     *      Finally the tree elemnt will receive a new branch "array" with a path/method pair
     *          array('path'=>value,'method'=>value)
     *  Example: $app->add_route('/accounting/report/:attributes','action_accounting_report');
     *
     */
    public function add_route(){
         $args = func_get_args();
        //Add path/class pair to exact match hash map
        $this->exact_match_route[sha1($args[0])] = $args;

        //Build Linked List tree
        $parts = explode('/',$args[0]);
        array_shift($parts); //remove first forward slash

        $pointer =& $this->routes_tree;
        for($i=0;$i<count($parts);$i++){
            if(!isset($pointer[$parts[$i]]) && !preg_match('/\:\w+/',$parts[$i])){
                $pointer[$parts[$i]] = NULL;//array($parts[$i]=>NULL);
            }

            //If path variable ":attributes" is present in the declaration, skip
            if(preg_match('/\:\w+/',$parts[$i]))
                continue;

            $pointer =& $pointer[$parts[$i]];
        }
        $pointer['_routes'][] = array(
            'path'=>$args[0],
            'method'=>$args[1]
        );
    }

    /**
     * get_route_tree()
     * print to log the contents of the tree
     */
    public function get_route_tree(){
        //Logger(array('exact_match'=>$this->exact_match_route));
        Logger(array('tree'=>$this->routes_tree));
    }

    /**
     * set_route()
     * 
     * api uses two methods to store routes for which set_route can retrieve the 
     * route/class pair. 
     * 1. hash map direct lookup O(1) - @param this->exact_match_route
     * 2. linked list lookup where the node is traversed until it finds the
     *      closest path in the array to the desired path. 
     *      For paths with variables, there may be additional iteration of 
     *      \Slim\Slim::mapRoute() to find the closest match
     */
    public function set_route(){
        $uri = $_SERVER['REQUEST_URI'];
        if(isset($this->exact_match_route[sha1($uri)])){
            //find exact match with hash table lookup
            $matched_route = $this->exact_match_route[sha1($uri)];
            $this->route($matched_route);
        }else{
            //traverse the array nodes using $cursor as a pointer to the 
            //current position in the tree
            $uri_parts = explode('/',$uri);
            $cursor = $this->routes_tree;
            foreach($uri_parts as $idx => $part){
                if(isset($cursor[$part])){
                    $cursor = $cursor[$part];
                }
            }

            $node_elements =& $cursor['_routes'];
            if(!empty($node_elements)){
                foreach($node_elements as $element){
                    $this->select_method($element['path'],$element['method'],$this->req_method);
                }
            }
        }
    }

    /** 
     * route()
     * used specficially to route traffic to class::method where method is defined by the REQUESTOR METHOD
     */
    public function route(){
        $args = func_get_args();
        $path = $args[0][0];
        $class = $args[0][1];
        return $this->select_method($path,$class,$this->req_method);

    }   
    /**
     * select_method(array $arg)
     * args contain method and class to be routed
     */
    private function select_method($path,$class,$method){

        $args = array(
            $path,
            array($class,$method),
        );
        Logger(array('args_sent_to_map_route'=>$args));

        if(!method_exists($class,$method)){
           	Logger("{$class}::{$method} Method does not exist");
            $args[1] = array('api','not_found');
        }
        Logger(array('args_sent_to_map_route'=>$args));
        switch($this->req_method){
            case 'GET':  
                return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_GET, \Slim\Http\Request::METHOD_HEAD);
            case 'PUT':
                return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_PUT); 
            case 'POST':
                return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_POST);    
            case 'DELETE':
                return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_DELETE);
            case 'OPTIONS':
                return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_OPTIONS);
            default:
                Logger(array('failure no routeable path'));
                return 400;
        }   
    }

    /**
     * autoload
     * model: mod_{class_name}
     * controllers/views: ctrl_{controller_name}
     * controller-actions: action_{action_name}
     * 
     * default to slim
     */
    private function enet_autoload($class_string){
    	$parts = explode('_',$class_string);
    	$type = array_shift($parts);
		$ctrl_group = array_shift($parts);
		$class_name = implode('_',$parts);

		self::autoload($class_string);
		$filename = \epro\API_PROTECTED;
		switch($type){
			case 'ctrl':
				$fliename .= "/controller/{$ctrl_group}/{$class_name}.class.php";
				break;
			case 'action':
				$filename .= "/controller/{$ctrl_group}/action/{$class_name}.class.php";
				break;
			case 'mod':
				$filename .= "/models/{$ctrl_group}/{$class_name}.rs.php";
				break;
			default:
				$filename .= "/components/{$class_string}.class.php";
				break;
		}
		//Logger(array('filename'=>$filename,'class_string'=>$class_string));
		if(file_exists($filename)){
			include_once($filename);
		}else{
			self::autoload($class_string);
		}
	}

    public static function not_found(){
    	echo json_encode(array('404'=>'Not Found'));
    }
}



function Logger($message){
    if(is_array($message))
        $message = json_encode($message);
    else if(is_object($message))
        $message = json_encode($message);

    $trace = debug_backtrace();
    $line = $trace[0]['line'];
    $funct = $trace[1]['function'];
    error_log(sprintf("[%s][%s][%s] - %s", date('Y-m-d H:i:s'),$funct,$line,$message));
}

?>
