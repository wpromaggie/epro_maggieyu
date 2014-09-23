<?php

/*
 * static class for updating local cache of market info
 */

require_once(\epro\WPROPHP_PATH.'apis/apis.php');
util::load_lib('ppc');

define('DATA_CACHE_FORCE_UPDATE', 0x01);
define('DATA_CACHE_SET_CLIENT', 0x02);

class data_cache
{
	private static $dbg = false;
	private static $error_str = '';
	
	public static function dbg()
	{
		self::$dbg = true;
		db::dbg();
	}
	
	public static function get_error()
	{
		return self::$error_str;
	}
	
	/**
	 * update local cache of keyword info
	 * @param string $cl_id the client the info belongs to
	 * @param string $market google/yahoo/msn/etc
	 * @param string $ag_id the ad group id
	 * @return array - was cache updated, and the time of last update
	 */
	public static function update_keywords($cl_id, $market, $ag_id)
	{
		$opts = (func_num_args() == 4) ? func_get_arg(3) : array();
		if (!array_key_exists('force_update', $opts))  $opts['force_update'] = false;
		if (!array_key_exists('do_set_client', $opts)) $opts['do_set_client'] = true;

		$cl_id = util::get_account_object_key($cl_id);
		if ($cl_id === false) {
			return array(false);
		}
		ppc_lib::create_market_object_tables($market, $cl_id);
		
		list($ac_id, $ca_id, $kw_info_mod_time) = db::select_row("
			select account_id, campaign_id, kw_info_mod_time
			from {$market}_objects.ad_group_{$cl_id}
			where id = '$ag_id'
		");
		if (!$opts['force_update'] && ($kw_info_mod_time != '0000-00-00 00:00:00') && ((\epro\NOW - strtotime($kw_info_mod_time)) < util::DATA_CACHE_EXPIRE)) return array(false, $kw_info_mod_time);
		
		$class = $market.'_api';
		$api = new $class(1, $ac_id);
		if (self::$dbg) $api->debug();
		$keywords = $api->get_keywords($ag_id);
		if ($keywords === false) {
			self::$error_str = $api->get_error();
			return false;
		}
		
		$old_data = db::select("
			select *
			from {$market}_objects.keyword_{$cl_id}
			where ad_group_id = '{$ag_id}'
		", 'ASSOC', 'id');

		// google: deleted keywords are not returned so set everything we already have in the db to "Off"
		// yahoo: you have the option to include deleted.. we do not for consistency's sake
		// if keywords are returned, the status will be updated
		db::exec("
			update {$market}_objects.keyword_{$cl_id}
			set status = 'Deleted'
			where ad_group_id = '$ag_id'
		");
		
		for ($i = 0, $count = count($keywords); $i < $count; ++$i) {
			$kw = $keywords[$i];
			if (!$kw || empty($kw->id) || $kw->use == 'NEGATIVE') {
				continue;
			}
			$kw->db_set($market, $cl_id, $ac_id, $ca_id);
			if (empty($old_data[$kw->id])) {
				$cache_op = 'insert';
				$old_item = false;
			}
			else {
				$cache_op = 'update';
				$old_item = $old_data[$kw->id];
			}
			data_cache::check_callback($opts, $cache_op, 'Keyword', $old_item, $kw);
		}
		
		return self::finish_update("{$market}_objects.ad_group_{$cl_id}", "kw_info_mod_time", $ag_id);
	}
	
	/**
	 * update local cache of ad info
	 * @param string $cl_id the client the info belongs to
	 * @param string $market google/yahoo/msn/etc
	 * @param string $ag_id the ad group id
	 * @return array - was cache updated, and the time of last update
	 */
	public static function update_ads($cl_id, $market, $ag_id)
	{
		$opts = (func_num_args() == 4) ? func_get_arg(3) : array();
		if (!array_key_exists('force_update', $opts))  $opts['force_update'] = false;
		if (!array_key_exists('do_set_client', $opts)) $opts['do_set_client'] = true;
		
		$cl_id = util::get_account_object_key($cl_id);
		if ($cl_id === false) {
			return array(false);
		}
		ppc_lib::create_market_object_tables($market, $cl_id);

		list($ac_id, $ca_id, $ad_info_mod_time) = db::select_row("
			select account_id, campaign_id, ad_info_mod_time
			from {$market}_objects.ad_group_{$cl_id}
			where id = '$ag_id'
		");
		if (!$opts['force_update'] && ($ad_info_mod_time != '0000-00-00 00:00:00') && ((\epro\NOW - strtotime($ad_info_mod_time)) < util::DATA_CACHE_EXPIRE)) return array(false, $ad_info_mod_time);
		
		$class = $market.'_api';
		$api = new $class(1, $ac_id);
		if (self::$dbg) $api->debug();
		$ads = $api->get_ads($ag_id);
		if ($ads === false) {
			self::$error_str = $api->get_error();
			return false;
		}

		$old_data = db::select("
			select *
			from {$market}_objects.ad_{$cl_id}
			where ad_group_id = '{$ag_id}'
		", 'ASSOC', 'id');

		// google: deleted ads are not returned so set everything we already have in the db to "Off"
		// yahoo: you have the option to include deleted.. we do not for consistency's sake
		// if keywords are returned, the status will be updated
		db::exec("
			update {$market}_objects.ad_{$cl_id}
			set status = 'Deleted'
			where ad_group_id = '$ag_id'
		");
		
		for ($i = 0, $count = count($ads); $i < $count; ++$i) {
			$ad = &$ads[$i];
			if (!$ad || empty($ad->id)) {
				continue;
			}
			$ad->db_set($market, $cl_id, $ac_id, $ca_id);
			if (empty($old_data[$ad->id])) {
				$cache_op = 'insert';
				$old_item = false;
			}
			else {
				$cache_op = 'update';
				$old_item = $old_data[$ad->id];
			}
			data_cache::check_callback($opts, $cache_op, 'Ad', $old_item, $ad);
		}
		
		return self::finish_update("{$market}_objects.ad_group_{$cl_id}", "ad_info_mod_time", $ag_id);
	}
	
	/**
	 * update local cache of ad group info
	 * @param string $cl_id the client the info belongs to
	 * @param string $market google/yahoo/msn/etc
	 * @param string $ca_id the campaign id
	 * @return array - was cache updated, and the time of last update
	 */
	public static function update_ad_groups($cl_id, $market, $ca_id)
	{
		$opts = (func_num_args() == 4) ? func_get_arg(3) : array();
		if (!array_key_exists('force_update', $opts))  $opts['force_update'] = false;
		if (!array_key_exists('do_set_client', $opts)) $opts['do_set_client'] = true;
		
		$cl_id = util::get_account_object_key($cl_id);
		if ($cl_id === false) {
			return array(false);
		}
		ppc_lib::create_market_object_tables($market, $cl_id);

		list($ac_id, $ag_info_mod_time) = db::select_row("
			select account_id, ag_info_mod_time
			from {$market}_objects.campaign_{$cl_id}
			where id = '$ca_id'
		");
		if (!$opts['force_update'] && ($ag_info_mod_time != '0000-00-00 00:00:00') && ((\epro\NOW - strtotime($ag_info_mod_time)) < util::DATA_CACHE_EXPIRE)) return array(false, $ag_info_mod_time);
		
		$class = $market.'_api';
		$api = new $class(1, $ac_id);
		if (self::$dbg) $api->debug();
		$ad_groups = $api->get_ad_groups($ca_id);
		if ($ad_groups === false) {
			self::$error_str = $api->get_error();
			return false;
		}
		
		$old_data = db::select("
			select *
			from {$market}_objects.ad_group_{$cl_id}
			where campaign_id = '{$ca_id}'
		", 'ASSOC', 'id');

		// google: deleted ad groups are not returned so set everything we already have in the db to "Off"
		// yahoo: you have the option to include deleted.. we do not for consistency's sake
		// the ad groups that are returned will have their status updated
		db::exec("
			update {$market}_objects.ad_group_{$cl_id}
			set status = 'Deleted'
			where campaign_id = '$ca_id'
		");
		
		for ($i = 0, $count = count($ad_groups); $i < $count; ++$i) {
			$ag = &$ad_groups[$i];
			if (!$ag || empty($ag->id)) {
				continue;
			}
			$ag->db_set($market, $cl_id, $ac_id);
			if (empty($old_data[$ag->id])) {
				$cache_op = 'insert';
				$old_item = false;
			}
			else {
				$cache_op = 'update';
				$old_item = $old_data[$ag->id];
			}
			data_cache::check_callback($opts, $cache_op, 'Ad Group', $old_item, $ag);
		}
		
		return self::finish_update("{$market}_objects.campaign_{$cl_id}", "ag_info_mod_time", $ca_id);
	}
	
	/**
	 * update local cache of ad campaign? info
	 * @param string $cl_id the client the info belongs to
	 * @param string $market google/yahoo/msn/etc
	 * @param string $ac_id the account id
	 * @return array - was cache updated, and the time of last update
	 */
	public static function update_campaigns($cl_id, $market, $ac_id, $opts = array())
	{
		util::set_opt_defaults($opts, array(
			'force_update' => false,
			'do_set_client' => true
		));
		
		$cl_id = util::get_account_object_key($cl_id);
		if ($cl_id === false) {
			return array(false);
		}
		ppc_lib::create_market_object_tables($market, $cl_id);

		// check last time info was cached to see if we need to refresh
		$ca_info_mod_time = db::select_one("
			select ca_info_mod_time
			from eppctwo.{$market}_accounts
			where id = '$ac_id'
		");
		if (!$opts['force_update'] && ($ca_info_mod_time != '0000-00-00 00:00:00') && ((\epro\NOW - strtotime($ca_info_mod_time)) < util::DATA_CACHE_EXPIRE)) return array(false, $ca_info_mod_time);
		
		$class = $market.'_api';
		$api = new $class(1, $ac_id);
		if (self::$dbg) $api->debug();
		$campaigns = $api->get_campaigns();
		if ($campaigns === false) {
			self::$error_str = $api->get_error();
			return false;
		}
		
		$old_data = db::select("
			select *
			from {$market}_objects.campaign_{$cl_id}
		", 'ASSOC', 'id');
		
		db::exec("
			update {$market}_objects.campaign_{$cl_id}
			set status = 'Deleted'
			where account_id = '$ac_id'
		");

		for ($i = 0, $count = count($campaigns); $i < $count; ++$i) {
			$ca = &$campaigns[$i];
			if (!$ca || empty($ca->id)) {
				continue;
			}
			$ca->db_set($market, $cl_id, $ac_id);
			if (empty($old_data[$ca->id])) {
				$cache_op = 'insert';
				$old_item = false;
			}
			else {
				$cache_op = 'update';
				$old_item = $old_data[$ca->id];
			}
			data_cache::check_callback($opts, $cache_op, 'Campaign', $old_item, $ca);
		}
		
		return self::finish_update("eppctwo.{$market}_accounts", "ca_info_mod_time", $ac_id);
	}
	
	private static function finish_update($table, $mod_time_field, $id)
	{
		$now_str = date(util::DATE_TIME, \epro\NOW);
		db::update(
			$table,
			array($mod_time_field => $now_str),
			"id = :id",
			array('id' => $id)
		);
		return array(true, $now_str);
	}
	
	private static function check_callback($opts, $event, $data_type, $cur_data, $new_data)
	{
		if (isset($opts['callback_func']) || isset($opts['on_'.$event]))
		{
			$func = (isset($opts['on_'.$event])) ? $opts['on_'.$event] : $opts['callback_func'];
			if (isset($opts['callback_obj']))
			{
				$opts['callback_obj']->$func($event, $data_type, $cur_data, $new_data, (array_key_exists('callback_data', $opts)) ? $opts['callback_data'] : null);
			}
			else
			{
				$func($event, $data_type, $cur_data, $new_data, (array_key_exists('callback_data', $opts)) ? $opts['callback_data'] : null);
			}
		}
	}
}

?>