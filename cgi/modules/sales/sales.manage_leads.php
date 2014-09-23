<?php

class mod_sales_manage_leads extends mod_sales
{
	const LEAD_HEADERS = 'Company,Prefix,First Name,Last Name,Phone,Email,Title,Address,Website Address,Descriptions of Business';
	
	public function pre_output()
	{
		if (!user::has_role('Lead Manager'))
		{
			cgi::redirect('');
		}
		parent::pre_output();
	}
	
	public function output()
	{
		$this->call_member('display_'.g::$p3, 'display_index');
	}
	
	public function pre_output_download()
	{
		$leads = db::select("
			select company, prefix, first, last, phone, email, title, address, url, biz_desc
			from sales_leads.lead
			where
				upload_id = ".db::escape($_REQUEST['upload_id'])." &&
				!is_dup
		");
		$data_str = '';
		foreach ($leads as $i => $lead)
		{
			array_walk($lead, create_function('&$v', '$v = str_replace(\'"\', \'""\', $v);'));
			$data_str .= '"'.implode('","', $lead)."\"\n";
		}
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename="de-duped-lead-list_'.date(util::DATE).'.csv');
		echo self::LEAD_HEADERS."\n".$data_str;
		exit;
	}
	
	private function get_page_menu()
	{
		return array(
			array('index', 'Leads'),
			array('upload_leads', 'Upload Leads'),
			array('upload_contacts', 'Upload Contacts'),
			array('upload_disqualified', 'Upload Disqualified'),
		);
	}
	
	public function head()
	{
		$pages = array_slice(g::$pages, 1);
		if (empty(g::$p3))
		{
			$pages[] = 'index';
		}
		echo '
			<h1>
				'.implode(' :: ', array_map(array('util', 'display_text'), $pages)).'
			</h1>
			'.$this->page_menu($this->get_page_menu(), 'sales/manage_leads/').'
		';
	}
	
	public function display_index()
	{
		$lead_uploads = db::select("
			select u.id, u.created uploaded, u.name, count(l.upload_id) count, sum(l.is_dup) dups
			from sales_leads.upload_lead_file u, sales_leads.lead l
			where u.id = l.upload_id
			group by u.id
			order by u.created desc
		", 'ASSOC');
		
		?>
		<h1>Download Leads</h1>
		<div id="leads_wrapper"></div>
		<?php
		cgi::add_js_var('lead_uploads', $lead_uploads);
	}
	
	/*
	 * download should have be triggered by pre-output
	 */
	public function display_download()
	{
		feedback::add_error_msg('error. error. errereorrerorereor');
	}
	
	public function display_upload_leads()
	{
		$this->print_upload_form('lead', 'Leads');
	}
	
	public function display_upload_contacts()
	{
		$this->print_upload_form('contact', 'Contacts');
	}
	
	public function display_upload_disqualified()
	{
		$this->print_upload_form('disqualified', 'Disqualified');
	}
	
	private function print_upload_form($type, $label)
	{
		?>
		<table id="upload_table">
			<tbody>
				<tr>
					<td><label for="<?php echo $type; ?>_file"><?php echo $label; ?> CSV File:</label></td>
					<td><input type="file" name="<?php echo $type; ?>_file" id="<?php echo $type; ?>_file" /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" a0="action_upload_<?php echo $type; ?>" value="Upload" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	public function action_upload_lead()
	{
		// test file riddled with \r's, let's fix that
		$file_type = 'lead';
		$h = $this->init_upload_file('lead', self::LEAD_HEADERS);
		
		if ($h !== false)
		{
			$upload = new upload_lead_file(array(
				'created' => date(util::DATE_TIME),
				'name' => $_FILES[$file_type.'_file']['name']
			));
			$upload->put();
			$dup_types = array_filter(lead::$dup_type_options);
			$num_new = $num_dups = 0;
			for ($i = 1; list($company, $prefix, $first, $last, $phone, $email, $title, $address, $url, $biz_desc) = fgetcsv($h); ++$i)
			{
				$email = trim($email);
				if (util::is_email_address($email))
				{
					$lead_id = db::insert("sales_leads.lead", array(
						'upload_id' => $upload->id,
						'company' => $company,
						'prefix' => $prefix,
						'first' => $first,
						'last' => $last,
						'phone' => $phone,
						'email' => $email,
						'title' => $title,
						'address' => $address,
						'url' => $url,
						'biz_desc' => $biz_desc
					));
					if ($lead_id)
					{
						$num_new++;
						foreach ($dup_types as $dup_type)
						{
							$dup_upload_id = db::select_one("select upload_id from sales_leads.{$dup_type}_lead where email = '".db::escape($email)."'");
							if ($dup_upload_id)
							{
								db::update("sales_leads.lead", array(
									'is_dup' => true,
									'dup_type' => $dup_type,
									'dup_upload_id' => $dup_upload_id
								), "id = $lead_id");
								$num_dups++;
								break;
							}
						}
					}
				}
			}
			fclose($h);
			feedback::add_success_msg(($i - 1).' records processed, '.$num_new.' new leads imported, '.$num_dups.' duplicates identified');
		}
	}
	
	public function action_upload_contact()
	{
		$expected_headers = 'Salutation,First Name,Last Name,Title,Mailing Street,Mailing City,Mailing State/Province,Mailing Zip/Postal Code,Mailing Country,Phone,Mobile,Fax,Email,Account Owner,Account Name';
		$this->do_upload_email_dup_file('contact', $expected_headers, 12);
	}
	
	public function action_upload_disqualified()
	{
		$expected_headers = 'Lead Owner,First Name,Last Name,Title,Company / Account,Lead Source,Rating,Street,Email';
		$this->do_upload_email_dup_file('disqualified', $expected_headers, 8);
	}
	
	private function init_upload_file($file_type, $expected_headers)
	{
		$file_path = $_FILES[$file_type.'_file']['tmp_name'];
		$tmp = file_get_contents($file_path);
		if (strpos($tmp, "\r") !== false)
		{
			file_put_contents($file_path, str_replace(array("\r\n", "\r"), "\n", $tmp));
		}
		$h = fopen($file_path, 'rb');
		if ($h === false)
		{
			feedback::add_error_msg('Error reading file.');
			return false;
		}
		$headers = trim(fgets($h));
		
		// get rid of non-ascii at beginning of file
		for ($i = 0, $ci = strlen($headers); $i < $ci; ++$i)
		{
			$ord = ord($headers[$i]);
			if ($ord > 31 && $ord < 128)
			{
				break;
			}
		}
		// if we found any
		if ($i)
		{
			$headers = substr($headers, $i);
		}
		// get rid of quotes in headers
		$headers = str_replace('"', '', $headers);
		// now trim commas
		$headers = trim($headers, ',');
		if (strcasecmp($headers, $expected_headers) !== 0)
		{
			feedback::add_error_msg('Unrecognized file headers.');
			feedback::add_error_msg('Expecting: '.$expected_headers);
			feedback::add_error_msg('Received: '.$headers);
			return false;
		}
		return $h;
	}
	
	private function do_upload_email_dup_file($file_type, $expected_headers, $email_index)
	{
		$h = $this->init_upload_file($file_type, $expected_headers);
		if ($h !== false)
		{
			$upload_file_class = 'upload_'.$file_type.'_file';
			$upload = new $upload_file_class(array(
				'created' => date(util::DATE_TIME),
				'name' => $_FILES[$file_type.'_file']['name']
			));
			$upload->put();
			$num_new = 0;
			for ($i = 1; $d = fgetcsv($h); ++$i)
			{
				$email = trim($d[$email_index]);
				if (util::is_email_address($email))
				{
					$r = db::insert("sales_leads.{$file_type}_lead", array(
						'upload_id' => $upload->id,
						'email' => $email
					));
					if ($r)
					{
						$num_new++;
					}
				}
			}
			fclose($h);
			feedback::add_success_msg(($i - 1).' records processed, '.$num_new.' new '.$file_type.' emails imported');
		}
	}
}

?>