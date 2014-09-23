<?php

/*
 * handles includes and object instantiation
 */

class api_factory
{
	public static function new_campaign($account_id = null, $id = null, $text = null, $budget = null, $status = null)
	{
		if (!class_exists('base_Campaign'))
		{
			require_once(\epro\WPROPHP_PATH.'apis/objects/campaign.php');
		}
		return new base_Campaign($account_id, $id, $text, $budget, $status);
	}
	
	public static function new_ad_group($campaign_id = null, $id = null, $text = null, $max_cpc = null, $max_content_cpc = null, $status = null)
	{
		if (!class_exists('base_Ad_Group'))
		{
			require_once(\epro\WPROPHP_PATH.'apis/objects/ad_group.php');
		}
		return new base_Ad_Group($campaign_id, $id, $text, $max_cpc, $max_content_cpc, $status);
	}
	
	public static function new_ad($ad_group_id = null, $id = null, $text = null, $desc_1 = null, $desc_2 = null, $disp_url = null, $dest_url = null, $status = null)
	{
		if (!class_exists('base_Ad'))
		{
			require_once(\epro\WPROPHP_PATH.'apis/objects/ad.php');
		}
		return new base_Ad($ad_group_id, $id, $text, $desc_1, $desc_2, $disp_url, $dest_url, $status);
	}
	
	public static function db_to_ad($d, $market)
	{
		
	}
	
	public static function array_to_ad($d, $market)
	{
		
	}
	
	public static function new_keyword($ad_group_id = null, $id = null, $text = null, $match_type = null, $max_cpc = null, $dest_url = null, $status = null)
	{
		if (!class_exists('base_Keyword'))
		{
			require_once(\epro\WPROPHP_PATH.'apis/objects/keyword.php');
		}
		return new base_Keyword($ad_group_id, $id, $text, $match_type, $max_cpc, $dest_url, $status);
	}
}

?>