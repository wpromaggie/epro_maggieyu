<?php
require_once 'proposal.php';

class proposal_display extends proposal
{
	
	/**
	 * Creates an appropriate branch node for the default SAP proposal.
	 */
	public function make_branch($parent_id, $node_id, $node_type) {
		return new display_branch($parent_id, $node_id, $node_type);
	}

	/**
	 * Creates an appropriate leaf node for the default SAP proposal.
	 */
	public function make_leaf($parent_id, $node_id, $node_type, $node_text) {
		
		//check and replace text variables {}
		preg_match_all("/\{(.*?)\}/", $node_text, $matches);
		$full_matches = $matches[0];
		for ($i = 0; $i < count($matches[0]); $i++)
		{
			$var_value = $this->prospect->get_var_by_key($full_matches[$i]);
			$node_text = str_replace($full_matches[$i], $var_value, $node_text);
		}
		
		return new display_leaf($parent_id, $node_id, $node_type, $node_text);
	}
	
}
?>
