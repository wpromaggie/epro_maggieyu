<?php
util::load_lib('network');

class smo_lib
{
	public static $networks = array(
		'facebook' => array(
			'class' => 'fb'
		),
		'twitter' => array(
			'class' => 'twitter'
		)
	);

	public static function facebook_refresh_pages($fb)
	{
		$pages = $fb->get_pages();
		if ($pages !== false) {
			$keys = array('id' => 1, 'name' => 1, 'category' => 1, 'access_token' => 1);
			foreach ($pages as $i => $page) {
				db::insert_update("social.facebook_page", array('id'), array_intersect_key($page, $keys));
			}
		}
		return $pages;
	}
}

?>