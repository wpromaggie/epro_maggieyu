<?php

class wid_client_select extends widget_base
{
	public $form_key, $prompt, $ignore_cl_ids, $selected;
	
	function __construct($opts = array())
	{
		$this->form_key = (isset($opts['form_key'])) ? $opts['form_key'] : 'client';
		$this->prompt = (isset($opts['prompt'])) ? $opts['prompt'] : 'Client Name:';
		$this->ignore_cl_ids = (isset($opts['ignore_cl_ids'])) ? $opts['ignore_cl_ids'] : false;
		$this->selected = (isset($opts['selected'])) ? $opts['selected'] : '';
	}
	
	public function output()
	{
		$where = array("account.status = 'Active'");
		$data = array();
		$join = "client.id = account.client_id";
		if ($this->ignore_cl_ids) {
			$where[] = "account.client_id not in (:cl_ids)";
			$data["cl_ids"] = $this->ignore_cl_ids;
			$join .= " && client.id not in (:cl_ids)";
		}
		
		// get all active clients
		$client_select_options = service::get_all(array(
			'select' => array(
				"client" => array("id"),
				"account" => array("concat(ltrim(client.name), ' (', group_concat(dept separator ', '), ')') as name")
			),
			'join' => array("client" => $join),
			'where' => $where,
			'data' => $data,
			'group_by' => "client.id",
			'order_by' => "name asc",
			'flatten' => true,
			'key_col' => "name"
		));
		// todo? add option to rs_array not to include key_col in object itself ie only use as key into array
		foreach ($client_select_options as $acnt) {
			unset($acnt->name);
		}
		cgi::add_js_var('client_select_options', $client_select_options);
		cgi::add_js_var('client_select_selected', $this->selected);

		$input_key = "client_select_{$this->form_key}";
		?>
		<div id="wid_client_select" ejo>
			<label for="<?= $input_key ?>">Client Name:</label> <input type="text" class="client_select_input" id="<?= $input_key ?>" />
			<p id="w_dst_client">
				<span class="client_select_msg"></span>
				<input type="hidden" class="client_select_field" name="<?= $this->form_key ?>" id="<?= $this->form_key ?>" value="" />
			</p>
		</div>
		<?php
	}

	// must be called before output()
	public function set_selected($selected)
	{
		$this->selected = $selected;
	}
}

?>