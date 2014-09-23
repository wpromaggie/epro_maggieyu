<?php
/**
 * goc_A116167982 importer for uknowkids.com
 *
 */
if(!class_exists('base_oc'))
	include_once(epro\CLI_PATH.'workers/import_offline_data/base_oc.import.php');

class goc_A116167982 extends base_oc{
	public static $table,$job;

	public static function init(){
		self::$market = 'g';
		parent::init();
	}


    public static function run(){
        list($job) = func_get_args();
    	self::$job = $job;
        self::init();

        //API URL:  https://api.familyconnect.net/call/
        $url = 'https://api.familyconnect.net/call/';   
        //Client Id was given to Wpromote by uknow.com
        $wpromote_client_id = 6;
    
        //Date range will consist of 14 day intervals
        $date_start = date('Y-m-d',strtotime('- 14 days'));
        $date_end = date('Y-m-d');



        /* UKNOW API POST PARAMETERS */
        $post_params = array(
            'function'=>'report/parent/adwordsGCLID',
            'token'=>md5(date('Y-m-d').'uknowreport'),
            'withAdwordsGCLID'=>1,
            'clientId'=>$wpromote_client_id
        );  
    
        //http_build_query
        $post = ''; 
        foreach($post_params as $k=>$v){
            $post .= "{$k}={$v}&";
        }   
        $post = substr($post,0,-1);
        e($post);
    
        e('running custom curl');
        list($s,$r) = self::post($url,$post_params);
        e(array('s'=>$s,'results'=>$r));
//      e(array_slice(json_decode($r,true),0,10));

        $data = json_decode($r,true);
        date_default_timezone_set('America/New_York');

        //Manually setting this accounts google account id
        $google_account_id = 8578600529;

        foreach($data['data'] as $k=>$v){
//          e($v);
            $io = array('id'=>substr(sha1(mt_rand().''.time()),0,30),
                        'action'=>'add',
                        'utm'=>'',
                        'g_acct_id'=>$google_account_id);

            $other = array();
            foreach($v as $field => $value){
                switch($field){ 
                    case 'Google_Click_Id':
                        $io['gclid'] = $value;
                        break;
                    case 'Conversion_Name':
                        $io['conversion_name'] = $value;
                        break;
                    case 'Conversion_Value':
                        $io['conversion_value'] = $value;
                        break;
                    case 'Conversion_Time':
                        $io['conversion_time'] = date('Ymd His e',strtotime($value));
                        break;
                    case 'adwordsSource':
                        $other['utm_source'] = $value;
                        break;
                    case 'adwordsTerm':
                        $other['utm_term'] = $value;
                        break;
                    default:
                        break;
                }   
            }   
            $io['utm'] = json_encode($other);
            self::$job->update_details("Writing to table ".self::$table);
            db::insert("`g_objects`.`offline_conversion_A116167982`",$io);

        }   
        date_default_timezone_set('America/Los_Angeles');

    }

    private static function post($url,$post){
        self::$job->update_details("Requesting data from client API");
        $c = curl_init();
    
        curl_setopt($c, CURLOPT_USERAGENT, 'Mozilla');
        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);  

        /* HTTP REQUEST SETTINGS */
        curl_setopt($c,CURLOPT_URL,$url);
        curl_setopt($c,CURLOPT_POST,TRUE);
        curl_setopt($c,CURLOPT_POSTFIELDS,$post);

        $response = curl_exec($c);
        $http_status_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);
        self::$job->update_details("HTTP Response {$http_status_code}");

        return array($http_status_code,$response);
    } 

}

?>