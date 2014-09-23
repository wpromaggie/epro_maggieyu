<?php

class mod_sb_account_ads extends mod_sb_account
{
	private $ad_id;

	/*
	 * when facebook changes columns:
	 * 1. add/remove columns below to FB_HEADERS
	 * 2. update fb_extras sb.rs.php rs file. column names much match exactly, except
	 *     - all lower case
	 *     - spaces replaced by underscores
	 *    this will allow update_ad_fb_extras to find what it needs
	 *    do not need to track all columns (don't need data (eg impressions)), but
	 *    need to add column in step 3 even if not tracked!
	 * 3. update the fb_uploader class to account for the new columns (usually just member var and setting in go())
	 * 4. update get_fb_formatted_ad function in this file
	 */
	
	const FB_HEADERS = "Campaign ID\tCampaign Run Status\tCampaign Daily Impressions\tCampaign Lifetime Impressions\tCampaign Name\tCampaign Time Start\tCampaign Time Stop\tCampaign Daily Budget\tCampaign Lifetime Budget\tCampaign Type\tRate Card\tAd ID\tAd Status\tDemo Link\tBid Type\tMax Bid\tClicks\tReach\tCampaign Monthly Reach\tSocial Imps\tActions\tMax Bid Clicks\tMax Bid Reach\tMax Bid Social\tMax Bid Conversions\tConversion Specs\tAd Name\tTitle\tBody\tLink\tRelated Page\tImage Hash\tCreative Type\tApp Platform Type\tLink Object ID\tStory ID\tAuto Update\tURL Tags\tQuery JSON\tQuery Template\tCountries\tCities\tRegions\tZip\tGender\tAge Min\tAge Max\tEducation Status\tEducation Networks\tEducation Majors\tWorkplaces\tCollege Start Year\tCollege End Year\tInterested In\tRelationship\tRadius\tConnections\tExcluded Connections\tFriends of Connections\tLocales\tLikes and Interests\tExcluded User AdClusters\tBroad Category Clusters\tCustom Audiences\tTargeted Entities\tBroad Age\tTarget on birthday\tPage Types\tSpent\tClicks Count\tImpressions\tActions Count\tImage";
	
	public function pre_output()
	{
		parent::pre_output();
		
		$this->ad_id = $_REQUEST['ad_id'];
		if ($this->ad_id)
		{
			$this->ad = db::select_row("
				select *
				from eppctwo.sb_ads
				where id = '{$this->ad_id}'
			", 'ASSOC');
		}
		$this->display_default = 'index';
	}
	
	public function display_index()
	{
		$ads = db::select("
			select * from sb_ads
			where group_id = {$this->account->id}
			order by id asc
		", 'ASSOC');
		
		if ($this->action == 'action_upload_ads')
		{
			$this->print_upload_form();
			$this->print_upload($ads);
		}
		// not in the middle of an upload, show ads
		else
		{
			$this->print_ads($ads);
			
			// an ad was selected, print ad form
			if ($this->ad_id)
			{
				$this->print_ad_form();
			}
			// no ad selected, show upload form
			else
			{
				$this->print_upload_form();
			}
		}
	}
	
	private function print_ad_form()
	{
		?>
		<table>
			<tbody>
				<tr>
					<td><input type="submit" a0="action_untie_ad" value="Untie Ad" /></td>
					<td></td>
				</tr>
				<tr>
					<td>Facebook import</td>
					<td><textarea id="ads_text" name="ads_text"><?php echo self::FB_HEADERS."\n".$this->get_fb_formatted_ad()."\n"; ?></textarea></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function action_untie_ad()
	{
		db::exec("delete from eppctwo.sb_fb_extras where ad_id = '{$this->ad_id}'");
		db::exec("delete from eppctwo.sb_keywords where ad_id = '{$this->ad_id}'");
		db::exec("delete from eppctwo.sb_ad_company where ad_id = '{$this->ad_id}'");
		db::exec("delete from eppctwo.sb_ad_college where ad_id = '{$this->ad_id}'");
		db::exec("delete from eppctwo.sb_ad_major where ad_id = '{$this->ad_id}'");
		db::exec("delete from eppctwo.sb_ad_location where ad_id = '{$this->ad_id}'");
		db::exec("delete from eppctwo.sb_ad_relationship where ad_id = '{$this->ad_id}'");
		db::exec("delete from eppctwo.sb_ads where id = '{$this->ad_id}'");
		
		util::wpro_post('account', 'sb_delete_ad', array('id' => $this->ad_id));
		
		unset($this->ad);
		$this->ad_id = null;
		
		feedback::add_success_msg('Ad untied from account');
	}
	
	private function get_fb_formatted_ad()
	{
		$fb_extras = new sb_fb_extras(array('ad_id' => $this->ad_id));
		
		$geo = sb_lib::get_ad_geotargeting($this->ad);
		$relationships = sb_lib::get_ad_relationships($this->ad_id);
		
		// many to one stuff
		$networks = sb_lib::ad_get_many($this->ad_id, 'sb_ad_college', 'college');
		$majors = sb_lib::ad_get_many($this->ad_id, 'sb_ad_major', 'major');
		$workplaces = sb_lib::ad_get_many($this->ad_id, 'sb_ad_company', 'company');
		
		// we'll always have these at 0
		$campaign_daily_impressions = $campaign_lifetime_impressions = $spent = $clicks = $impressions = $max_bid_clicks = $max_bid_reach = $max_bid_convs = 0;
		
		return 
			$fb_extras->campaign_id."\t".
			$fb_extras->campaign_run_status."\t".
			$campaign_daily_impressions."\t".
			$campaign_lifetime_impressions."\t".
			$fb_extras->campaign_name."\t".
			$fb_extras->campaign_time_start."\t".
			$fb_extras->campaign_time_stop."\t".
			$fb_extras->campaign_daily_budget."\t".
			$fb_extras->campaign_lifetime_budget."\t".
			$fb_extras->campaign_type."\t".
			$fb_extras->rate_card."\t".
			'a:'.$this->ad['fb_id']."\t".
			$fb_extras->ad_status."\t".
			$fb_extras->demo_link."\t".
			$this->ad['bid_type']."\t".
			$this->ad['max_bid']."\t".
			$max_bid_clicks."\t".
			$max_bid_reach."\t".
			$fb_extras->max_bid_social."\t".
			$max_bid_convs."\t".
			$fb_extras->conversion_specs."\t".
			$this->ad['name']."\t".
			$this->ad['title']."\t".
			$this->ad['body_text']."\t".
			$this->ad['link']."\t".
			$fb_extras->related_page."\t".
			$fb_extras->image_hash."\t".
			$fb_extras->creative_type."\t".
			$fb_extras->app_platform_type."\t".
			$fb_extras->link_object_id."\t".
			$fb_extras->story_id."\t".
			$fb_extras->auto_update."\t".
			$fb_extras->url_tags."\t".
			$this->ad['country']."\t".
			$geo['cities']."\t".
			$geo['regions']."\t".
			$geo['zips']."\t".
			$this->ad['sex']."\t".
			$this->ad['min_age']."\t".
			$this->ad['max_age']."\t".
			$this->ad['education_status']."\t".
			$networks."\t".
			$majors."\t".
			$workplaces."\t".
			$this->ad['college_year_min']."\t".
			$this->ad['college_year_max']."\t".
			$this->ad['interested_in']."\t".
			(($relationships) ? implode(', ', $relationships) : 'All')."\t".
			$this->ad['radius']."\t".
			$fb_extras->connections."\t".
			$fb_extras->excluded_connections."\t".
			$fb_extras->friends_of_connections."\t".
			$fb_extras->locales."\t".
			$fb_extras->likes_and_interests."\t".
			$fb_extras->excluded_user_adclusters."\t".
			$fb_extras->broad_category_clusters."\t".
			$fb_extras->custom_audiences."\t".
			$fb_extras->targeted_entities."\t".
			$fb_extras->broad_age."\t".
			$this->ad['birthday']."\t".
			$fb_extras->page_types."\t".
			$spent."\t".
			$clicks."\t".
			$impressions."\t".
			$fb_extras->actions."\t".
			$fb_extras->image
		;
	}
	
	private function print_upload_form()
	{
		?>
		<table>
			<tbody>
				<tr>
					<td>File</td>
					<td><input type="file" id="ads_file" name="ads_file" /></td>
				</tr>
				<tr>
					<td colspan="2"> - OR - </td>
				</tr>
				<tr>
					<td>Text</td>
					<td><textarea id="ads_text" name="ads_text"><?php echo $this->upload_str; ?></textarea></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" id="leads_submit" a0="action_upload_ads" value="Upload" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	private function print_ads($ads)
	{
		$ml = '';
		foreach ($ads as $ad)
		{
			$ml_class = ($ad['id'] == $this->ad_id) ? ' selected' : '';
			$ml .= '
				<a class="wrapper_link'.$ml_class.'" href="'.cgi::href('sbs/sb/account/ads?aid='.$this->account->id.'&ad_id='.$ad['id']).'">
					'.mod_sb_account_ads::ml_ad($ad).'
				</a>
				<div class="lft ad_extras">
					<div>
						<a target="_blank" href="'.cgi::href('sbs/sb/edit_ad?oid='.$this->account->id.'&selected_ad='.$ad['id']).'">
							<img src="'.cgi::href('img/external.png').'" />
						</a>
					</div>
					'.(($ad['fb_id']) ? '<div class="fb_tied">FB</div>' : '').'
				</div>
			';
		}
		?>
		<div id="ad_previews">
			<?php echo $ml; ?>
			<div class="clr"></div>
		</div>
		<?php
	}
	
	public static function ml_ad($ad)
	{
		return '
			<div class="creative_outter_wrapper lft">
				<p class="ad_name">'.$ad['name'].'</p>
				<div class="creative_sample_container droppable lft" ad_id="'.$ad['id'].'">
					<span class="creative_sample_title">'.$ad['title'].'</span>
					<div class="creative_sample_image">
						'.(($ad['image']) ? '<img src="http://'.\epro\WPRO_DOMAIN.'/uploads/sb/'.$ad['group_id'].'/'.$ad['image'].'" />' : '').'
					</div>
					<div class="creative_sample_body_text">'.$ad['body_text'].'</div>
					<div class="clr"></div>
					<div class="creative_sample_like">
						<p><span class="nogo">Wpromote</span> likes this.</p>
					</div>
				</div>
			</div>
		';
	}
	
	private function print_upload($ads)
	{
		$fbup = new fb_upload_preview($this->account, $this->upload_str);
		$fbup->go();
	}
	
	// the first step, just set the uploads string
	public function action_upload_ads()
	{
		if ($_FILES['ads_file']['tmp_name'])
		{
			/*
			 * \0: linux thinks fb's export file is utf-16
			 * php's mb_detect_encoding thinks it is either false or utf-8
			 * cursory attempt to use a php built in to parse did not work
			 * we'll just strip null bytes
			 */
			// substr: first two bytes of file appear to be magic number
			$this->upload_str = str_replace("\0", '', substr(file_get_contents($_FILES['ads_file']['tmp_name']), 2));
		}
		else
		{
			$this->upload_str = $_POST['ads_text'];
		}
	}
	
	// actually process the upload data
	public function action_process_upload()
	{
		$fbup = new fb_upload_processor($this->account, $_POST['ads_text']);
		$fbup->go();
	}
	
}

abstract class fb_uploader
{
	// ref to account
	protected $account;
	
	// upload data
	protected $lines;
	
	// we get rid of non-digits characters for the ids
	protected $campaign_id, $fb_id;
	
	public function __construct($account, &$upload_str)
	{
		$upload_str = str_replace("\r", '', $upload_str);
		$this->account = $account;
		$this->lines = explode("\n", $upload_str);
	}
	
	public function go()
	{
		$headers = trim($this->lines[0]);
		if ($headers != mod_sb_account_ads::FB_HEADERS)
		{
			feedback::add_error_msg("Import columns do not match! Tell Kevin to fix this shit!");
			return false;
		}
		for ($i = 1, $ci = count($this->lines); $i < $ci; ++$i)
		{
			$d = explode("\t", $this->lines[$i]);
			list($this->campaign_id_raw, $this->campaign_run_status, $this->campaign_daily_impressions, $this->campaign_lifetime_impressions, $this->campaign_name, $this->campaign_time_start, $this->campaign_time_stop, $this->campaign_daily_budget, $this->campaign_lifetime_budget, $this->campaign_type, $this->rate_card, $this->fb_id_raw, $this->ad_status, $this->demo_link, $this->bid_type, $this->max_bid, $this->clicks, $this->reach, $this->campaign_monthly_reach, $this->social_imps, $this->actions, $this->max_bid_clicks, $this->max_bid_reach, $this->max_bid_social, $this->max_bid_conversions, $this->conversion_specs, $this->ad_name, $this->title, $this->body, $this->link, $this->related_page, $this->image_hash, $this->creative_type, $this->app_platform_type, $this->link_object_id, $this->story_id, $this->auto_update, $this->url_tags, $this->query_json, $this->query_template, $this->countries, $this->cities, $this->regions, $this->zip, $this->gender, $this->age_min, $this->age_max, $this->education_status, $this->education_networks, $this->education_majors, $this->workplaces, $this->college_start_year, $this->college_end_year, $this->interested_in, $this->relationship, $this->radius, $this->connections, $this->excluded_connections, $this->friends_of_connections, $this->locales, $this->likes_and_interests, $this->excluded_user_adclusters, $this->broad_category_clusters, $this->custom_audiences, $this->targeted_entities, $this->broad_age, $this->target_on_birthday, $this->page_types, $this->spent, $this->clicks_count, $this->impressions, $this->actions_count, $this->image) = $d;
			
			// this export appears to have a marker prepended to the IDs to signify
			// which type they are. other exports however do not have this, so we will
			// get rid of all non-numeric characters
			$this->campaign_id = preg_replace("/\D/", '', $this->campaign_id_raw);
			$this->fb_id = preg_replace("/\D/", '', $this->fb_id_raw);

			// valid export line?
			if (preg_match("/^\d+$/", $this->campaign_id) && preg_match("/^\d+$/", $this->fb_id))
			{
				$this->process_line($i);
			}
		}
		$this->process_end();
	}
	
	abstract protected function process_line($i);
	
	protected function process_end(){}
}

class fb_upload_preview extends fb_uploader
{
	private $ac_ads, $default_edit_type, $ml;
	
	public function __construct($account, &$upload_str)
	{
		parent::__construct($account, $upload_str);
		$this->ac_ads = db::select("
			select id, name, group_id, title, image, body_text, if (fb_id = '', concat('tmp_', id), fb_id) fb_id
			from eppctwo.sb_ads sa
			where sa.group_id = {$account->id}
		", 'ASSOC', 'fb_id');
		$this->default_edit_type = ((count($this->lines) - 1) > count($this->ac_ads)) ? 'update' : 'new';
		$this->ml = '';
	}
	
	protected function process_line($i)
	{
		// fb id already in our database, must be update
		$edit_type_field = 'edit_type_'.$this->fb_id;
		$ml_ignore_interests = '
			<div class="ignore_interests">
				<hr />
				<input type="checkbox" id="ignore_interests_'.$this->fb_id.'" name="ignore_interests_'.$this->fb_id.'" value="1" checked />
				<label for="ignore_interests_'.$this->fb_id.'">Ignore Interests</span>
			</div>
		';
		if (array_key_exists($this->fb_id, $this->ac_ads))
		{
			$ml_update = '
				<td>
					<span>Update</span>
					'.$ml_ignore_interests.'
				</td>
				<td>
					'.mod_sb_account_ads::ml_ad($this->ac_ads[$this->fb_id]).'
					<input type="hidden" id="update_id_'.$this->fb_id.'" name="update_id_'.$this->fb_id.'" value="'.$this->ac_ads[$this->fb_id]['id'].'" />
					<input type="hidden" name="'.$edit_type_field.'" value="update" />
				</td>
			';
		}
		else
		{
			$ml_ad_select = '';
			foreach ($this->ac_ads as $ad)
			{
				$ml_ad_select .= '
					'.mod_sb_account_ads::ml_ad($ad).'
				';
			}
			$ml_update = '
				<td>
					<p>
						<input type="radio" class="edit_type" name="'.$edit_type_field.'" id="'.$edit_type_field.'_update" value="update"'.(($this->default_edit_type == 'update') ? ' checked' : '').' />
						<label for="'.$edit_type_field.'_update">Update</label>
					</p>
					<p>
						<input type="radio" class="edit_type" name="'.$edit_type_field.'" id="'.$edit_type_field.'_new" value="new"'.(($this->default_edit_type == 'new') ? ' checked' : '').' />
						<label for="'.$edit_type_field.'_new">New</label>
					</p>
					<p>
						<input type="radio" class="edit_type" name="'.$edit_type_field.'" id="'.$edit_type_field.'_ignore" value="ignore"'.(($this->default_edit_type == 'ignore') ? ' checked' : '').' />
						<label for="'.$edit_type_field.'_ignore">Ignore</label>
					</p>
					'.$ml_ignore_interests.'
					<input type="hidden" id="update_id_'.$this->fb_id.'" name="update_id_'.$this->fb_id.'" value="" />
				</td>
				<td>
					<span class="ad_select">'.$ml_ad_select.'</span>
				</td>
			';
		}
		$this->ml .= '
			<tr fb_id="'.$this->fb_id.'">
				<td>'.$i.'</td>
				<td class="export_cell">
					<p><b>Title: </b> <span>'.$this->title.'</span></p>
					<p><b>Body: </b> <span>'.$this->body.'</span></p>
				</td>
				'.$ml_update.'
			</tr>
		';
	}
	
	protected function process_end()
	{
		echo '
			<table id="upload_table" ejo>
				<thead>
					<tr>
						<th></th>
						<th>Export Data</th>
						<th>Edit Type</th>
						<th><!-- ad select if update --></th>
					</tr>
				</thead>
				<tbody>
					'.$this->ml.'
				</tbody>
			</table>
			<input type="submit" a0="action_process_upload" value="Process Changes" />
		';
	}
}

class fb_upload_processor extends fb_uploader
{
	// set by process line
	private $ad_id;
	
	// update, ignore, new
	private $edit_type;
	
	// maps go facebook to e2
	private $ad_map, $relationship_map;
	
	// e2 fields we need to send to wpro
	private $wpro_ad_keys;
	
	// values that were changed
	private $updates;
	
	// e2 ad db info, before updates
	private $e2_ad;
	
	// data we need to send to wpro
	private $wpro_ad_data;
	
	public function __construct($account, &$upload_str)
	{
		parent::__construct($account, $upload_str);
		
		$this->wpro_ad_data = array();
		$this->updates = array();
		
		$this->ad_map = array(
			'ad_name' => 'name',
			'bid_type' => 'bid_type',
			'max_bid' => 'max_bid',
			'title' => 'title',
			'body' => 'body_text',
			'link' => 'link',
			'countries' => 'country',
			'radius' => 'radius',
			'age_min' => 'min_age',
			'age_max' => 'max_age',
			'gender' => 'sex',
			'education_status' => 'education_status',
			'college_start_year' => 'college_year_min',
			'college_end_year' => 'college_year_max',
			'interested_in' => 'interested_in',
			'target_on_birthday' => 'birthday'
		);
		
		$this->relationship_map = sb_lib::get_relationship_map();
		
		$this->wpro_ad_keys = array(
			'title',
			'body_text',
			'link',
			'min_age',
			'max_age',
			'sex'
		);
	}
	
	protected function process_line($i)
	{
		$this->edit_type = $_POST['edit_type_'.$this->fb_id];
		switch ($this->edit_type)
		{
			case ('update'):
				$this->ad_id = $_POST['update_id_'.$this->fb_id];
				$this->update_ad();
				break;
			
			case ('new'):
				$this->new_ad();
				break;
			
			case ('ignore'):
			default:
				break;
		}
	}
	
	private function update_ad()
	{
		$this->e2_ad = db::select_row("
			select *
			from eppctwo.sb_ads
			where id = '{$this->ad_id}'
		", 'ASSOC');
		
		$this->update_ad_ad();
		$this->update_ad_relationships();
		$this->update_ad_location();
		
		// many to one relationship to ad
		$this->update_ad_many('majors', 'education_majors', 'sb_ad_major', 'major');
		$this->update_ad_many('education networks', 'education_networks', 'sb_ad_college', 'college');
		$this->update_ad_many('workplaces', 'workplaces', 'sb_ad_company', 'company');
		
		// we always update interests when edit type is new
		if ($this->edit_type == 'new' || ($this->edit_type == 'update' && !$_POST['ignore_interests_'.$this->fb_id]))
		{
			$this->update_ad_many('interests', 'likes_and_interests', 'sb_keywords', 'text', true);
		}
		
		$this->update_ad_fb_extras();
		
		
		if ($this->updates)
		{
			feedback::add_success_msg('Ad updated ('.$this->ad_id.', '.$this->title.'): '.implode(', ', $this->updates));
			if ($this->wpro_ad_data)
			{
				$this->wpro_ad_data['id'] = $this->ad_id;
				$this->wpro_ad_data['group_id'] = $this->account->id;
				util::wpro_post('account', 'sb_update_ad', $this->wpro_ad_data);
			}
		}
		else
		{
			feedback::add_error_msg('No changes detected ('.$this->ad_id.', '.$this->title.').');
		}
	}
	
	private function update_ad_fb_extras()
	{
		$extras = new sb_fb_extras();
		$cols = sb_fb_extras::get_cols();
		foreach ($cols as $col)
		{
			$col_raw = $col.'_raw';
			$extras->$col = ($this->$col_raw) ? $this->$col_raw : $this->$col;
		}
		$extras->put();
	}
	
	private function update_ad_ad()
	{
		$ad_updates = array();
		foreach ($this->ad_map as $fb_key => $e2_key)
		{
			$e2_val = $this->e2_ad[$e2_key];
			$fb_val = $this->$fb_key;
			if ($e2_val != $fb_val)
			{
				$ad_updates[$e2_key] = $fb_val;
				if (in_array($e2_key, $this->wpro_ad_keys));
				{
					$this->wpro_ad_data[$e2_key] = $fb_val;
				}
			}
		}
		
		if ($ad_updates)
		{
			db::update("eppctwo.sb_ads", array_merge($ad_updates, array('fb_id' => $this->fb_id)), "id = {$this->ad_id}");
			$this->updates = array_map(array('util', 'display_text'), array_keys($ad_updates));
		}
	}
	
	private function update_ad_relationships()
	{
		// order creates ads, but not relationships, so check that they exist
		if (!db::select_one("select count(*) from eppctwo.sb_ad_relationship where ad_id = {$this->ad_id}"))
		{
			$this->init_empty_relationship();
		}
		$e2_relationships = db::select_row("
			select ".implode(', ', array_values($this->relationship_map))."
			from eppctwo.sb_ad_relationship
			where ad_id = {$this->ad_id}
		", 'ASSOC');
		
		// relationship can either be all or some combination of the options (ie, cannot select nothing),
		// however in db we signify all by having nothing on
		$relationship_updates = array();
		if ($this->relationship == 'All')
		{
			// if we had something on, turn off everything
			if (array_sum($e2_relationships))
			{
				// set all e2 cols to zero
				$relationship_updates = array_combine(array_values($this->relationship_map), array_fill(0, count($this->relationship_map), 0));
			}
		}
		else
		{
			$fb_relationships = explode(',', $this->relationship);
			foreach ($this->relationship_map as $fb_key => $e2_key)
			{
				// on in fb, not on in e2
				if (in_array($fb_key, $fb_relationships) && !$e2_relationships[$e2_key])
				{
					$relationship_updates[$e2_key] = 1;
				}
				// off in fb, on in e2
				if (!in_array($fb_key, $fb_relationships) && $e2_relationships[$e2_key])
				{
					$relationship_updates[$e2_key] = 0;
				}
			}
		}
		if ($relationship_updates)
		{
			db::update("eppctwo.sb_ad_relationship", $relationship_updates, "ad_id = {$this->ad_id}");
			$this->updates[] = 'Relationship';
		}
	}
	
	private function update_ad_location()
	{
		$is_loc_update = false;
		
		// we keep cities and regions in same table
		if ($this->cities)
		{
			if ($this->e2_ad['location_type'] != 'city')
			{
				db::update("eppctwo.sb_ads", array('location_type' => 'city'), "id = {$this->ad_id}");
				$is_loc_update = true;
			}
			$this->update_ad_many_multi_col('cities', array_map('trim', explode(';', $this->cities)), 'sb_ad_location', array('city', 'state'));
		}
		else if ($this->regions)
		{
			if ($this->e2_ad['location_type'] != 'state')
			{
				db::update("eppctwo.sb_ads", array('location_type' => 'state'), "id = {$this->ad_id}");
				$is_loc_update = true;
			}
			$this->update_ad_many('regions', 'regions', 'sb_ad_location', 'state');
		}
		else if ($this->zip)
		{
			if ($this->e2_ad['location_type'] != 'zip')
			{
				db::update("eppctwo.sb_ads", array('location_type' => 'zip'), "id = {$this->ad_id}");
				$is_loc_update = true;
			}
			$this->update_ad_many('zips', 'zip', 'sb_ad_location', 'zip');
		}
		// everywhere/country
		else
		{
			if ($this->e2_ad['location_type'] != 'country')
			{
				db::update("eppctwo.sb_ads", array('location_type' => 'country'), "id = {$this->ad_id}");
				$is_loc_update = true;
			}
			// nothing else to do, country is updated in ad table
		}
		
		if ($is_loc_update)
		{
			$this->updates[] = 'Location';
		}
	}
	
	private function update_ad_many($display, $fb_key, $e2_table, $e2_db_col, $do_set_group = false)
	{
		$e2_vals = db::select("
			select {$e2_db_col}
			from eppctwo.{$e2_table}
			where ad_id = {$this->ad_id}
		");
		$vals_str = trim($this->$fb_key);
		$fb_vals = ($vals_str) ? explode(', ', $vals_str) : array();
		$fb_no_e2 = array_diff($fb_vals, $e2_vals);
		$e2_no_fb = array_diff($e2_vals, $fb_vals);
		if ($fb_no_e2 || $e2_no_fb)
		{
			$wpro_ad_data_func = 'set_wpro_ad_data_'.$display;
			if (method_exists($this, $wpro_ad_data_func))
			{
				$this->$wpro_ad_data_func($fb_vals);
			}
			if ($e2_no_fb)
			{
				$this->updates[] = 'Deleted '.$display.' ('.implode(', ', $e2_no_fb).')';
				db::exec("
					delete from eppctwo.{$e2_table}
					where ad_id = {$this->ad_id} && {$e2_db_col} in ('".implode("','", $e2_no_fb)."')
				");
			}
			if ($fb_no_e2)
			{
				$this->updates[] = 'New '.$display.' ('.implode(', ', $fb_no_e2).')';
				foreach ($fb_no_e2 as $fb_val)
				{
					$update_vals = array(
						'ad_id' => $this->ad_id,
						$e2_db_col => $fb_val
					);
					if ($do_set_group)
					{
						$update_vals['group_id'] = $this->account->id;
					}
					db::insert("eppctwo.{$e2_table}", $update_vals);
				}
			}
		}
	}
	
	private function update_ad_many_multi_col($display, $fb_vals, $e2_table, $e2_db_cols, $do_set_group = false)
	{
		$e2_db_cols_str = implode(', ', $e2_db_cols);
		$e2_tmp_vals = db::select("
			select {$e2_db_cols_str}
			from eppctwo.{$e2_table}
			where ad_id = {$this->ad_id}
		");
		$e2_vals = array();
		$delete_groups = '';
		$e2_no_fb = array();
		
		// convert e2 vals to comma separated string, compare to fb vals
		// build delete query as we go
		foreach ($e2_tmp_vals as $d)
		{
			$e2_val = implode(', ', $d);
			if (!in_array($e2_val, $fb_vals))
			{
				$e2_no_fb[] = $e2_val;
				$delete_group = '';
				foreach ($d as $val)
				{
					$delete_group .= (($delete_group) ? ',' : '')."'".db::escape($val)."'";
				}
				$delete_groups .= (($delete_groups) ? ',' : '')."({$delete_group})";
			}
			$e2_vals[] = $e2_val;
		}
		$fb_no_e2 = array_diff($fb_vals, $e2_vals);
		if ($fb_no_e2 || $e2_no_fb)
		{
			$wpro_ad_data_func = 'set_wpro_ad_data_'.$display;
			if (method_exists($this, $wpro_ad_data_func))
			{
				$this->$wpro_ad_data_func($fb_vals);
			}
			if ($e2_no_fb)
			{
				$this->updates[] = 'Deleted '.$display.' ('.implode(', ', $e2_no_fb).')';
				db::exec("
					delete from eppctwo.{$e2_table}
					where ad_id = {$this->ad_id} && ({$e2_db_cols_str}) in ({$delete_groups})
				");
			}
			if ($fb_no_e2)
			{
				$this->updates[] = 'New '.$display.' ('.implode(', ', $fb_no_e2).')';
				foreach ($fb_no_e2 as $fb_val)
				{
					// fb_data must be in order of e2 cols
					$fb_data = array_map('trim', explode(',', $fb_val));
					$update_vals = array_merge(array(
						'ad_id' => $this->ad_id
						), array_combine($e2_db_cols, $fb_data)
					);
					if ($do_set_group)
					{
						$update_vals['group_id'] = $this->account->id;
					}
					db::insert("eppctwo.{$e2_table}", $update_vals);
				}
			}
		}
	}
	
	private function set_wpro_ad_data_interests($fb_vals)
	{
		$this->wpro_ad_data = array_merge($this->wpro_ad_data, array(
			'keywords' => str_replace('#', '', implode("\t", $fb_vals))
		));
	}
	
	private function new_ad()
	{
		// create new ad with placeholder values
		$this->ad_id = db::insert("eppctwo.sb_ads", array(
			'group_id' => $this->account->id,
			'edit_status' => 'current',
			'status' => 'active',
			'bid_type' => 'cpc',
			'name' => 'New Ad',
			'link' => '',
			'location_type' => '',
			'country' => '',
			'create_date' => date(util::DATE_TIME),
			'max_bid' => 0
		));
		
		if ($this->ad_id)
		{
			feedback::add_success_msg('New ad created ('.$this->ad_id.', '.$this->title.')');
		}
		else
		{
			feedback::add_error_msg('Error creating new ad: '.db::last_error());
			return false;
		}
		
		// and relationship row
		$this->init_empty_relationship();
		
		// call our update function
		$this->update_ad();
	}
	
	private function init_empty_relationship()
	{
		db::insert("eppctwo.sb_ad_relationship", array_merge(
			array('ad_id' => $this->ad_id),
			array_combine(array_values($this->relationship_map), array_fill(0, count($this->relationship_map), 0))
		));
	}
}

?>