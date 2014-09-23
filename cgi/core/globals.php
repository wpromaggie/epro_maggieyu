<?php

/*
 * static class to hold commonly used variables
 * very useful to keep track of things and also avoid use of the keyword global whenever these are needed
 */

class g
{
	public static $module;
	public static $company;
	public static $client_id, $cl_type;
	public static $pages, $p1, $p2, $p3, $p4, $p5;
	
	/** action taken by user
	 * 
	 * @var string
	 */
	public static $go;
        
        public static $group_id; // used to refrence ad groups for socialboost
}

?>