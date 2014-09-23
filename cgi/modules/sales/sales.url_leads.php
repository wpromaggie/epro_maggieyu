<?php

class mod_sales_url_leads extends mod_sales
{
	private $LEADS_UPLOAD_PATH;

	public function pre_output()
	{
		self::$LEADS_UPLOAD_PATH = sys_get_temp_dir();
	}
	
	public function get_page_menu()
	{
		return array(
			array(''                ,'Dashboard'),
			array('past_uploads'    ,'Past Uploads'),
			array('quick_upload'    ,'Quick Upload'),
			array('import_from_file','Import From File')
		);
	}
	
	public function head()
	{
		echo '
			<h1>
				'.implode(' :: ', array_map(array('util', 'display_text'), array_slice(g::$pages, 1))).'
			</h1>
			'.$this->page_menu($this->get_page_menu(), 'sales/url_leads/').'
		';
	}
	
	public function output()
	{
		$this->call_member('display_'.g::$p3, 'display_index');
	}
	
	public function display_index()
	{
		?>
		hello?
		<?php
	}
	
	public function display_past_uploads()
	{
		$options = db::select("
			select id, concat(created, ' - ', name)
			from sales_leads.url_lead_upload
			order by created desc
		");
		?>
		<table>
			<tbody>
				<tr>
					<td>Select Upload</td>
					<td><?php echo cgi::html_select('url_lead_upload', $options, $this->url_lead_upload_id); ?></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_past_uploads_set_urls" value="Go" /></td>
				</tr>
			</tbody>
		</table>
		<?php
		echo $this->ml;
	}
	
	public function action_past_uploads_set_urls()
	{
		$this->url_lead_upload_id = $_POST['url_lead_upload'];
		$this->show_dups = ($_POST['show_dups'] == 1);
		
		$urls = db::select("
			select url
			from sales_leads.url_lead_url
			where url_lead_upload_id = '{$this->url_lead_upload_id}' && is_new
		");
		
		$fname = str_replace(' ', '_', db::select_one("select concat(name, '-', created) from sales_leads.url_lead_upload where id={$this->url_lead_upload_id}"));
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment;filename="'.$fname.'-new.txt');
		
		echo implode("\n", $urls);
		exit;
	}
	
	public function display_quick_upload()
	{
		?>
		<table>
			<tbody>
				<tr>
					<td>Name</td>
					<td><input type="text" name="name" id="name" /></td>
				</tr>
				<tr>
					<td>URLs</td>
					<td><textarea name="new_urls" id="new_urls"></textarea></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_quick_upload" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function display_import_from_file()
	{
		$dh = opendir(self::$LEADS_UPLOAD_PATH);
		if ($dh === false)
		{
			feedback::add_error_msg('Could not open import from file directory');
			return;
		}
		$files = array();
		while (($file = readdir($dh)) !== false)
		{
			if ($file[0] != '.')
			{
				$files[] = $file;
			}
		}
		closedir($dh);
		
		?>
		<table>
			<tbody>
				<tr>
					<td>Name</td>
					<td><input type="text" name="name" id="name" /></td>
				</tr>
				<tr>
					<td>File</td>
					<td><?php echo cgi::html_select('file', $files); ?></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_import_from_file" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function action_import_from_file()
	{
		$urls = explode("\n", str_replace("\r", '', file_get_contents(self::$LEADS_UPLOAD_PATH.$_POST['file'])));
		$this->act_process_urls($urls);
	}
	
	public function action_quick_upload()
	{
		$urls = explode("\n", $_POST['new_urls']);
		$this->act_process_urls($urls);
	}
	
	public function act_process_urls(&$urls)
	{
		$url_leads = db::select("
			select url, 1
			from sales_leads.url_lead
		", 'NUM', 0);
		
		$url_lead_upload = new url_lead_upload(array(
			'created' => date(util::DATE_TIME),
			'name' => $_POST['name']
		));
		$url_lead_upload->put();
		
		$num_new = 0;
		$new_urls = array();
		for ($i = 0, $ci = count($urls); $i < $ci; ++$i)
		{
			$url = strtolower(trim($urls[$i]));
			if ($url && !array_key_exists($url, $new_urls))
			{
				$new_urls[$url] = 1;
				$url_lead_url = new url_lead_url(array(
					'url_lead_upload_id' => $url_lead_upload->id,
					'url' => $url,
					'is_new' => (array_key_exists($url, $url_leads)) ? 0 : 1
				));
				$url_lead_url->put();
				if ($url_lead_url->is_new)
				{
					$num_new++;
					$url_lead = new url_lead(array(
						'url' => $url,
						'url_lead_upload_id' => $url_lead_upload->id
					));
					$url_lead->put();
				}
			}
		}
		feedback::add_success_msg('Upload Processed: '.$ci.' URLs, '.$num_new.' New');
	}
}

?>
