<?php
class mod_eppctwo_sb_fb_extras extends rs_object
{
	public static $db, $cols, $primary_key;
	
	public static function set_table_definition()
	{
		self::$db = 'eppctwo';
		self::$primary_key = array('ad_id');
		self::$cols = self::init_cols(
			new rs_col('ad_id'                   ,'bigint' ,null,0 ,rs::NOT_NULL | rs::READ_ONLY | rs::UNSIGNED),
			new rs_col('campaign_id'             ,'char'   ,32  ,'',rs::NOT_NULL),
			new rs_col('campaign_run_status'     ,'char'   ,16  ,'',rs::NOT_NULL),
			new rs_col('campaign_name'           ,'char'   ,128 ,'',rs::NOT_NULL),
			new rs_col('campaign_time_start'     ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('campaign_time_stop'      ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('campaign_daily_budget'   ,'char'   ,8   ,'',rs::NOT_NULL),
			new rs_col('campaign_lifetime_budget','char'   ,16  ,'',rs::NOT_NULL),
			new rs_col('campaign_type'           ,'char'   ,32  ,'',rs::NOT_NULL),
			new rs_col('ad_status'               ,'char'   ,16  ,'',rs::NOT_NULL),
			new rs_col('demo_link'               ,'varchar',512 ,'',rs::NOT_NULL),
			new rs_col('rate_card'               ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('max_bid_social'          ,'char'   ,8   ,'',rs::NOT_NULL),
			new rs_col('conversion_specs'        ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('related_page'            ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('image_hash'              ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('creative_type'           ,'char'   ,16  ,'',rs::NOT_NULL),
			new rs_col('app_platform_type'       ,'char'   ,16  ,'',rs::NOT_NULL),
			new rs_col('link_object_id'          ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('story_id'                ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('auto_update'             ,'char'   ,8   ,'',rs::NOT_NULL),
			new rs_col('url_tags'                ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('page_types'              ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('connections'             ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('excluded_connections'    ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('friends_of_connections'  ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('locales'                 ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('likes_and_interests'     ,'varchar',1024,'',rs::NOT_NULL),
			new rs_col('excluded_user_adclusters','varchar',1024,'',rs::NOT_NULL),
			new rs_col('broad_category_clusters' ,'varchar',1024,'',rs::NOT_NULL),
			new rs_col('custom_audiences'        ,'varchar',128 ,'',rs::NOT_NULL),
			new rs_col('targeted_entities'       ,'varchar',128 ,'',rs::NOT_NULL),
			new rs_col('broad_age'               ,'char'   ,8   ,'',rs::NOT_NULL),
			new rs_col('actions'                 ,'char'   ,64  ,'',rs::NOT_NULL),
			new rs_col('image'                   ,'char'   ,64  ,'',rs::NOT_NULL)
		);
	}
}
?>
