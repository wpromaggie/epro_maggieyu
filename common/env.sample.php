<?php
namespace epro;

# DEV, STAGE, PROD
define('epro\ENV', 'DEV');

define('epro\HOSTNAME', 'cool-host');
define('epro\NUM_NODES', 1);
define('epro\MAX_JOBS', 5);

define('epro\DB_HOST', 'localhost');
define('epro\DB_USER', 'aoefjiae');
define('epro\DB_PASS', 'goodpassword');
define('epro\DB_NAME', 'a_db_name');

define('epro\DOMAIN', 'epro.local');
define('epro\WPRO_DOMAIN', 'wpromote.local');
define('epro\SAP_DOMAIN', 'sap.local');
define('epro\MEDIA_PATH', '/home/kedakick/src/work/wp-media/');
define('epro\MEDIA_DOMAIN', 'wpromedia.local');

# todo? move to billing module
define('epro\BILLING_KEYFILE_PATH', '/path/to/billing_keyfile.pem');
define('epro\BACKUP_KEYFILE_PATH', '/path/to/backup_keyfile');

define('epro\CRYPT_KEY', 'aolifjaiofjeifjaefjeajfefj');
define('epro\CRYPT_SALT', 'oaofijeF*#F*l;8srdsvp49mz;9ovu;Wptv0sm4t9pvzu;e49pvun4');

// class env
// {
// 	// db
// 	const DB_HOST = 'localhost';
// 	const DB_USER = 'bro';
// 	const DB_PASS = 'goodpassword';
// 	const DB_NAME = 'adbname';
	
// 	// e2 and friends
// 	const E2_FS_PATH = 'C:\\work\\e2\\';
// 	const CLI_PATH = 'C:\\work\\e2scripts\\';
// 	const COMMON_PATH = 'C:\\work\\e2common\\';
// 	const WPRO_PATH = 'C:\\work\\wproPHP\\';
// 	const BILLING_KEYFILE_PATH = 'C:\\work\\e2\\local\\3888.pem';
// 	const REPORTS_PATH = 'C:\\work\\e2\\reports\\';
// 	const E2_URL_PATH = '/work/e2/';
// 	const E2_DOMAIN = 'localhost';
// 	const E2_FULL_URL = 'localhost/work/e2/';
// 	const TMP_PATH = 'C:\\win32\\tmp\\';
	
// 	// i can't stop partying
// 	const PARTYON_FS_PATH = 'C:\\work\\party_on\\';
// 	const PARTYON_URL_PATH = '/work/party_on/';
// 	const PARTYON_DOMAIN = 'localhost';
// 	const PARTYON_FULL_URL = 'localhost/work/party/';
	
// 	// you just got served
// 	const SAP_FS_PATH = 'C:\\work\\sap\\';
// 	const SAP_URL_PATH = '/work/sap/';
// 	const SAP_DOMAIN = 'localhost';
// 	const SAP_FULL_URL = 'localhost/work/sap/';
	
// 	// external stuff
// 	const WPRO_URL = 'www.wpromote.com/';

// 	// media server stuff
//     const MEDIA_FS_PATH = 'C:\\wamp\\www\\wpmedia\\';
// 	const MEDIA_FULL_URL = 'wpmedia.local/';
// }

?>