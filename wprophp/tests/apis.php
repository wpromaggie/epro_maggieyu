<?php
require('../wpro.php');
define('APIS_PATH', '../apis/');

class mmmmmm_yes
{
	public function Get($s)
	{
		return APIS_PATH;
	}
};

$wpro['paths'] = new mmmmmm_yes();

require(APIS_PATH.'apis.php');
require(\epro\COMMON_PATH.'common_funcs.php');
require(\epro\COMMON_PATH.'data_cache.php');
require_once('../wpro.php');
require_once('../db.php');
require_once('../strings.php');
require_once('../curl.php');

$market = 'g';

$markets = array(
	'g' => 'google',
	'y' => 'yahoo',
	'm' => 'msn',
);

$market_name = $markets[$market];

db::dbg();

switch ($market)
{
	case ('g'):
		#$ac_id = '9315955910'; // g,belly bella
		#$ac_id = '9293484972'; // g,rawhide
		#$ac_id = '2362686239'; // g,perfomance mcc
		#$ac_id = '7354061337'; // walking company (revenue in report test)
		
		#$ac_id = '4021687990'; // QL 10
		#$ag_id = '1260979706'; // williamatuning.com
		
		$ac_id = '9293484972';
		$ca_id = '39440560';
/*
		$cl_id = '32846'; // uneedcorp
		$ac_id = '2693046831'; // QL 15
		$ca_id = '21907405';
*/
		break;
	
	case ('y'):
/*
		$cl_id = '7'; // bizquest
		$ac_id = '1817447437'; // y,bizquest
		$ca_id = '187175011';  // y,bizquest,buyers
		$ag_id = '373401399';  // y,bizquest,buyers,Find businesses for sale at
*/

		#$ac_id = '3752123660'; // y,FTD Flowers
		
		#$ac_id = '8211163240'; // y,Smarti Pants Toyz
		#$ca_id = '1680531512'; // only campaign
		
		#$ac_id = '20739505538'; // ql lal clients 1
		#$ca_id = '7345367512'; // ktest3
		
		#$ac_id = '8586579860'; // ql 11
		#$ag_id = '13772951900'; // flix66.com
		
/*
		$cl_id = '32846'; // uneedcorp
		$ac_id = '22040745585'; // ql 15
		$ca_id = '4447118512';
*/
		
		#$ac_id = '3764260540'; // y,umg nktob
		
		#$ac_id = '23172241519'; // y,umg j-mell
		
		$ac_id = '20190567624'; // ql lal 11
		$ag_id = '16706607900';
		break;
	
	case ('m'):
		#$ac_id = '58651'; // m,belly bella
		#$ac_id = '241290'; // m,den-mat
		#$ac_id = '35643'; // m,battery depot
		
		/*
		 * soap requests and responses
http://msdn.microsoft.com/en-us/library/bb126204.aspx

118047  	X0756650  	Optics HQ - External  	USD  	mkuperman  	kuperman16
218732 	X0238349 	Storage Guardian 	USD 	storageg 	wp786sto
228081 	X1817370 	Chrsity Towels 	GBP 	christy1850 	YSChristy07
231494 	X0960683 	Special Teas 	USD 	specialteas 	wp773spe
23809 	X0787832 	new technology products 	USD 	arepair 	wp258slu
241290 	X1648183 	Den-Mat Holdings 	USD 	lumineers 	cerinate
242481 	X0298714 	MQI 	USD 	modelqi 	wp674mqi
253846 	X0501203 	Sue DeBrule 	USD 	suedebrule 	sue1201
264571 	X0401997 	Jewish TV Network 	USD 	jewishtvnetwork 	network1201
290662 	X1381203 	Pictage 	USD 	pictage 	francisco1201
294172 	X1913064 	Debt Consolidation Service 	USD 	wjolly1234 	highlight
299927 	X2108421 	Wine.com 	USD 	winedotcom 	dr1nkw1ne
337220 	X0607176 	MBT Law 	USD 	mbtlaw 	mbt123
337392 	X0706404 	LifeFone 	USD 	lifefone_api 	msn12345
35643 	X1976441 	batterydepot.com 	USD 	depotwp 	wpr0m0t3
379400 	X0292588 	Home Everything 	USD 	homeeverything 	homeev1201
44311 	X2100437 	makun, Inc. 	USD 	marimary 	marysville
486450 	X1536859 	thinkvacuums 	USD 	ab102359 	vacuums
486593 	X1824575 	Oncor Insurance Services 	USD 	oncor1 	familydirect
549305 	X1422410 	Health Care Apparel 	USD 	healthcareapparel 	healthcare909
566048 	X1846444 	Madison Healthcare Insurance Services, Inc. 	USD 	madisonhealth 	madison909
571263 	X1268520 	SoCal Edison 	USD 	socaledison 	edison909
621319 	X1352876 	LTLprints.com - Larger Than Life prints 	USD 	ltlprints 	sell1fast
79613 	X1043429 	HvacPartsShop.com 	USD 	Jonathan120265 	Million22
*/
		$ac_id = '118047'; // Optics HQ - External
		break;
}

// localhost
mysql_connect(DB_HOST, 'eppctwo', '3ppc7w0');
mysql_select_db('eppctwo');
$api_info = mysql_fetch_assoc(mysql_query("select * from {$market}_api_accounts where company=1"));

/*
db::use_db('wpro2point0');
$time = db::time_query("select name from clients, urls where url like '%money%' && clients.id=urls.client");
echo "t=$time\n";
return;
*/

// databeast
#mysql_connect('192.168.9.101', 'wpte', 'o1201');
#mysql_select_db('eppc');

/*
// yahoo test response
$response = '<soap:Envelope happy="yes sir\"more stuff" bob joe= xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" something=nothing at leats they say xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><soap:Header><yns:remainingQuota xmlns:yns="http://marketing.ews.yahooapis.com/V4">65749</yns:remainingQuota><yns:quotaUsedForThisRequest xmlns:yns="http://marketing.ews.yahooapis.com/V4">1</yns:quotaUsedForThisRequest><yns:commandGroup xmlns:yns="http://marketing.ews.yahooapis.com/V4">Marketing</yns:commandGroup><yns:timeTakenMillis xmlns:yns="http://marketing.ews.yahooapis.com/V4">165</yns:timeTakenMillis><yns:sid xmlns:yns="http://marketing.ews.yahooapis.com/V4">ac2-bcp1adsrvcs-006.ysm.ac2.yahoo.com</yns:sid><yns:stime xmlns:yns="http://marketing.ews.yahooapis.com/V4">Tue Jun 24 09:57:58 PDT 2008</yns:stime></soap:Header><soap:Body><getCampaignsByAccountIDResponse xmlns="http://marketing.ews.yahooapis.com/V4"><out xmlns="http://marketing.ews.yahooapis.com/V4"><Campaign><ID>2416165011</ID><accountID>1817447437</accountID><advancedMatchON>true</advancedMatchON><campaignOptimizationON>false</campaignOptimizationON><contentMatchON>true</contentMatchON><createTimestamp>2007-07-06T16:39:54.301-07:00</createTimestamp><deleteTimestamp xsi:nil="true" /><description xsi:nil="true" /><endDate xsi:nil="true" /><lastUpdateTimestamp>2007-07-06T16:39:54.301-07:00</lastUpdateTimestamp><name>Buyer-Categories</name><sponsoredSearchON>true</sponsoredSearchON><startDate>2007-07-06T00:00:00-07:00</startDate><status>On</status><watchON>false</watchON></Campaign><Campaign><ID>4012742011</ID><accountID>1817447437</accountID><advancedMatchON>true</advancedMatchON><campaignOptimizationON>false</campaignOptimizationON><contentMatchON>true</contentMatchON><createTimestamp>2007-11-08T06:45:33.334-08:00</createTimestamp><deleteTimestamp xsi:nil="true" /><description xsi:nil="true" /><endDate xsi:nil="true" /><lastUpdateTimestamp>2007-11-08T06:45:33.334-08:00</lastUpdateTimestamp><name>Franchise-Brands3</name><sponsoredSearchON>true</sponsoredSearchON><startDate>2007-11-08T00:00:00-08:00</startDate><status>On</status><watchON>false</watchON></Campaign><Campaign><ID>187176511</ID><accountID>1817447437</accountID><advancedMatchON>false</advancedMatchON><campaignOptimizationON>false</campaignOptimizationON><contentMatchON>true</contentMatchON><createTimestamp>2007-01-04T20:53:17.243-08:00</createTimestamp><deleteTimestamp xsi:nil="true" /><description xsi:nil="true" /><endDate xsi:nil="true" /><lastUpdateTimestamp>2007-06-07T18:01:36.927-07:00</lastUpdateTimestamp><name>buyer-cities</name><sponsoredSearchON>true</sponsoredSearchON><startDate>2007-01-04T20:53:17.243-08:00</startDate><status>On</status><watchON>true</watchON></Campaign><Campaign><ID>187175511</ID><accountID>1817447437</accountID><advancedMatchON>false</advancedMatchON><campaignOptimizationON>false</campaignOptimizationON><contentMatchON>true</contentMatchON><createTimestamp>2007-01-04T20:53:17.243-08:00</createTimestamp><deleteTimestamp xsi:nil="true" /><description xsi:nil="true" /><endDate xsi:nil="true" /><lastUpdateTimestamp>2007-06-25T14:19:37.341-07:00</lastUpdateTimestamp><name>buyer-states</name><sponsoredSearchON>true</sponsoredSearchON><startDate>2007-01-04T20:53:17.243-08:00</startDate><status>On</status><watchON>true</watchON></Campaign><Campaign><ID>187175011</ID><accountID>1817447437</accountID><advancedMatchON>false</advancedMatchON><campaignOptimizationON>false</campaignOptimizationON><contentMatchON>true</contentMatchON><createTimestamp>2007-01-04T20:53:17.243-08:00</createTimestamp><deleteTimestamp xsi:nil="true" /><description xsi:nil="true" /><endDate xsi:nil="true" /><lastUpdateTimestamp>2007-06-25T14:20:14.311-07:00</lastUpdateTimestamp><name>buyers</name><sponsoredSearchON>true</sponsoredSearchON><startDate>2007-01-04T20:53:17.243-08:00</startDate><status>On</status><watchON>true</watchON></Campaign><Campaign><ID>187174011</ID><accountID>1817447437</accountID><advancedMatchON>false</advancedMatchON><campaignOptimizationON>false</campaignOptimizationON><contentMatchON>true</contentMatchON><createTimestamp>2007-01-04T20:53:17.243-08:00</createTimestamp><deleteTimestamp xsi:nil="true" /><description xsi:nil="true" /><endDate xsi:nil="true" /><lastUpdateTimestamp>2007-06-25T14:27:55.603-07:00</lastUpdateTimestamp><name>franchise</name><sponsoredSearchON>true</sponsoredSearchON><startDate>2007-01-04T20:53:17.243-08:00</startDate><status>On</status><watchON>true</watchON></Campaign><Campaign><ID>187176011</ID><accountID>1817447437</accountID><advancedMatchON>false</advancedMatchON><campaignOptimizationON>false</campaignOptimizationON><contentMatchON>true</contentMatchON><createTimestamp>2007-01-04T20:53:17.243-08:00</createTimestamp><deleteTimestamp xsi:nil="true" /><description xsi:nil="true" /><endDate xsi:nil="true" /><lastUpdateTimestamp>2007-06-25T14:47:15.234-07:00</lastUpdateTimestamp><name>franchise-brands</name><sponsoredSearchON>true</sponsoredSearchON><startDate>2007-01-04T20:53:17.243-08:00</startDate><status>On</status><watchON>true</watchON></Campaign><Campaign><ID>187173511</ID><accountID>1817447437</accountID><advancedMatchON>false</advancedMatchON><campaignOptimizationON>false</campaignOptimizationON><contentMatchON>true</contentMatchON><createTimestamp>2007-01-04T20:53:17.243-08:00</createTimestamp><deleteTimestamp xsi:nil="true" /><description xsi:nil="true" /><endDate xsi:nil="true" /><lastUpdateTimestamp>2007-06-25T14:34:28.016-07:00</lastUpdateTimestamp><name>franchise-brands 2</name><sponsoredSearchON>true</sponsoredSearchON><startDate>2007-01-04T20:53:17.243-08:00</startDate><status>On</status><watchON>true</watchON></Campaign><Campaign><ID>187174511</ID><accountID>1817447437</accountID><advancedMatchON>false</advancedMatchON><campaignOptimizationON>false</campaignOptimizationON><contentMatchON>true</contentMatchON><createTimestamp>2007-01-04T20:53:17.243-08:00</createTimestamp><deleteTimestamp xsi:nil="true" /><description xsi:nil="true" /><endDate xsi:nil="true" /><lastUpdateTimestamp>2007-06-25T14:47:15.631-07:00</lastUpdateTimestamp><name>sellers</name><sponsoredSearchON>true</sponsoredSearchON><startDate>2007-01-04T20:53:17.243-08:00</startDate><status>On</status><watchON>true</watchON></Campaign></out></getCampaignsByAccountIDResponse></soap:Body></soap:Envelope>';
$doc = new xml_doc($response);
print_r(api_translate::standardize_header('y', $doc->get('Header')));

// google test response
$response = file_get_contents('g_response.txt');
$doc = new xml_doc($response);
print_r(api_translate::standardize_header('g', $doc->get('Header')));
*/

// html text
#$response = file_get_contents('xml_test.html');

$class = $market.'_api';
$api = new $class(1, $ac_id);

$api->debug();
#$api->whoami();

#$api->refresh_accounts(1); // needs the company
#$r = $api->get_accounts();
#$r = $api->get_account();
#$r = $api->get_campaigns();
#$r = $api->get_ad_groups($ca_id);
#$r = $api->get_keywords($ag_id);
$r = $api->get_ads($ag_id);
#$r = $api->get_active_ads($ag_id);

#$r = $api->validate_report($request);
#$r = $api->run_account_report('2008-08-13');
#$r = $api->run_master_report('2009-09-01');

/*
$d = array(
	'ac_id' => $ac_id,
	'name' => '7785231/ocotillo-golf-resort',
	'budget' => '5000000',
	'period' => 'Daily',
	'date' => date('Y-m-d')
);
$r = $api->add_campaign($d);
*/

#$r = $api->get_campaign($ca_id);

#$r = $api->estimate_traffic('man utd 2009');

/*
$ca = array(
	'id' => $ca_id,
	'budget' => ''
);
$d = array(
	'budget' => '2.00'
);
$api->update_campaign_daily_spend_limit($ca, $d);
*/

if ($r)
{
	var_dump($r);
}
else
{
	echo "Error: ".print_r($api->get_error(), true);
}



?>