<?php

class mod_social_facebook_post extends mod_social_network_post
{
	public static $db, $cols, $primary_key;

	private static $val_keys = false;

	const MAX_MESSAGE_LENGTH = 512;
	
	public static function set_table_definition()
	{
		self::$db = 'social';
		self::$primary_key = array('id');
		self::$cols = self::init_cols(
			new rs_col('id'         ,'char'   ,16  ,'',rs::READ_ONLY),
			new rs_col('album_id'   ,'char'   ,32  ,''),
			new rs_col('link'       ,'varchar',512 ,''),
			new rs_col('link_name'  ,'char'   ,200 ,''),
			new rs_col('picture_url','varchar',512 ,''),
			new rs_col('caption'    ,'varchar',512 ,''),
			new rs_col('description','varchar',1000,'')
		);
	}

	private static function get_val_keys()
	{
		if (empty(self::$val_keys)) {
			self::$val_keys = array();
			list($cols) = self::attrs('cols');
			foreach ($cols as $col_name => $col) {
				if (empty($col->ancestor_table) && !($col->attrs & rs::READ_ONLY)) {
					self::$val_keys[] = $col_name;
				}
			}
		}
		return self::$val_keys;
	}

	private static function get_saved_values($mod)
	{
		// we assume all non-read only cols should be gotten
		$keys = self::get_val_keys();
		// $keys = array_slice(func_get_args(), 1);
		$vals = array_combine($keys, array_fill(0, count($keys), ''));

		// set sources we can get values from
		// we want to check fb post before mod
		$sources = array();
		if ($mod->is_edit) {
			$facebook_post = $mod->post->network_post->find('network', 'facebook');
			if ($facebook_post) {
				$sources[] = $facebook_post;
			}
		}
		$sources[] = $mod;
		foreach ($keys as $key) {
			foreach ($sources as $src) {
				if (!empty($src->$key)) {
					$vals[$key] = $src->$key;
					break;
				}
			}
		}
		return $vals;
	}

	public function can_retry_on_error()
	{
		switch ($this->error) {
			case ('An unexpected error has occurred. Please retry your request later.'):
				return true;

			default:
				return false;
		}
	}

	// facebook image response contains id and post_id fields
	// post_id seems to be the actual post_id, id is..?
	public function process_response($api, &$r)
	{
		if (isset($r['post_id'])) {
			$r['id'] = $r['post_id'];
		}
		return parent::process_response($api, $r);
	}

	public static function print_network_inputs($mod)
	{
		// $vals = self::get_saved_values($mod, 'link', 'link_name', 'picture_url', 'caption', 'album_id');
		$vals = self::get_saved_values($mod);
		?>
		<tr id="album_tr" class="facebook" network="facebook" network_toggle_callback="album">
			<td>Album <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td id="album_select_td">
				<?= cgi::html_select('album_id', $mod->album_options, $vals['album_id']) ?>
				<a id="refresh_albums_link" href="" class="ajax">Refresh Albums</a>
				<?= cgi::loading(true) ?>
			</td>
		</tr>
		<tr class="facebook" network="facebook">
			<td>Link <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td><input class="wide" type="text" name="link" id="link" value="<?= $vals['link'] ?>" /></td>
		</tr>
		<tr class="facebook" network="facebook">
			<td>Link Text <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td><input class="wide" type="text" name="link_name" id="link_name" value="<?= $vals['link_name'] ?>" /></td>
		</tr>
		<tr class="facebook" network="facebook">
			<td>Picture URL <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td><input class="wide" type="text" name="picture_url" id="picture_url" value="<?= $vals['picture_url'] ?>" /></td>
		</tr>
		<tr class="facebook" network="facebook">
			<td>Caption <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td><input class="wide" type="text" name="caption" id="caption" value="<?= $vals['caption'] ?>" /></td>
		</tr>
		<tr class="facebook" network="facebook">
			<td>Description <?= $mod->ml_network_image('facebook', 'small') ?></td>
			<td><textarea name="description" id="description"><?= $vals['description'] ?></textarea></td>
		</tr>
		<?php
	}

	public static function get_post_data()
	{
		return array_intersect_key($_POST, array_flip(self::get_val_keys()));
	}
}
?>
