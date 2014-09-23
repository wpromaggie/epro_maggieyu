<?php

class wid_contacts extends widget_base
{
	private $cid, $fields;
	
	public function __construct($cid)
	{
		$this->cid = $cid;

		$this->fields = array(
			'id' => array('key' => 1, 'read_only' => 1),
			'name' => array(),
			'title' => array(),
			'email' => array(),
			'phone' => array(),
			'fax' => array(),
			'street' => array(),
			'city' => array(),
			'state' => array(),
			'zip' => array(),
			'country' => array(),
			'notes' => array('textarea' => 1)
		);
	}

	public function output()
	{
		if (array_key_exists('save', $_POST)) $this->save();
		else if (array_key_exists('delete', $_POST)) $this->delete();
		
		$contacts = db::select("
			select ".implode(',', array_keys($this->fields))."
			from contacts
			where client_id = :cid
			order by name asc
		", array(
			"cid" => $this->cid
		), 'ASSOC');
		
		?>
		<div id="cur_contacts" class="end_float"></div>
		<input type="submit" name="add" value="Add New Contact" />
		<?php
		cgi::to_js('g_contacts', $contacts);
		cgi::to_js('g_fields', $this->fields);
	}
	
	private function save()
	{
		$cid = $_POST['cid'];
		$data = array();
		foreach ($this->fields as $key => $params) {
			if ($params['read_only']) {
				continue;
			}
			$data[$key] = $_POST[$key];
		}
		
		if ($cid == 'new') {
			$data['client_id'] = $this->cid;
			db::insert("eppctwo.contacts", $data);
			feedback::add_success_msg('New Contact Added');
		}
		else {
			db::update("eppctwo.contacts", $data, "id = :cid", array("cid" => $cid));
			feedback::add_success_msg('Contact Changes Saved');
		}
	}
	
	private function delete()
	{
		db::delete(
			"eppctwo.contacts",
			"id = :contact_id",
			array("contact_id" => $_POST['cid'])
		);
		feedback::add_success_msg('Contact Deleted');
	}
}

?>