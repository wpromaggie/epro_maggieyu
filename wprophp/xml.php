<?php

/* -----
// Description:
	parse an xml document and retrieve nodes
 * Programmers:
 *	cp C-P
 *	km K-Money
 *	mc Merlin Corey
 *	vy1 Vyrus001
// History:
	0.0.1 2008June25 born
---- */

if (class_exists('xml_doc'))
{
	trigger_error('Class "xml_doc" already exists!', E_USER_WARNING);
}
else
{
	class xml_doc
	{
		private $doc;
		private $xml;
		private $nodes, $node_index, $num_nodes;
		
		function __construct(&$xml)
		{
			$this->set_xml($xml);
			$this->set_all_nodes();
			$this->create_xml_doc();
		}
		
		// need to allow more than 1 target to better describe node in case of conflicts? (this might (should?) never come up)
		//
		//	a
		//		thing
		//	b
		//		stuff
		//		foo
		//	c
		//		blah
		//		thing
		//
		// how do we get "thing" from c?
		public function get($target, $expect_array = false)
		{
			$tmp = $this->get_it($this->doc, $target);
			
			// if we expect an array of results but it appears an associative array if returned,
			// create a numerically indexed array with the associative array as the first (and only) element
			if ($expect_array && !@array_key_exists(0, $tmp)) $tmp = array($tmp);
			return ($tmp);
		}
		
		public function get_root()
		{
			return $this->doc;
		}
		
		private function get_it(&$node, $target)
		{
			foreach ($node as $k => $v)
			{
				if ($k === $target) return $v;
				if (is_array($v))
				{
					$tmp = $this->get_it($v, $target);
					if ($tmp !== null) return $tmp;
				}
			}
			return null;
		}
		
		public function to_string()
		{
			print_r($this->doc);
		}
		
		private function set_xml(&$xml)
		{
			$this->xml = trim($xml);
			if (strpos($this->xml, "\n") !== false) $this->xml = strings::ml_one_line_ref($this->xml);
		}
		
		// create a flat array of all nodes
		private function set_all_nodes()
		{
			$this->nodes = array();
			for ($i = 0, $len = strlen($this->xml); $i < $len; $i += $node_len)
			{
				if (!preg_match("/<.*?>/", $this->xml, $matches, PREG_OFFSET_CAPTURE, $i)) break;
				list($node, $offset) = $matches[0];
				$node_len = strlen($node);
				
				// didn't start with a tag, text node
				if ($offset != $i)
				{
					preg_match("/(.*?)</", $this->xml, $matches, 0, $i);
					$inner_ml = $matches[1];
					$i += strlen($inner_ml);
					$this->nodes[] = $inner_ml;
				}
				$this->nodes[] = $node;
			}
		}
		
		private function create_xml_doc()
		{
			$this->doc = array();
			$this->num_nodes = count($this->nodes);
			$this->node_index = 0;
			
			$this->create_xml_doc_go($this->doc);
			
			// special case for root (for other nodes, this is taken care of in child loop)
			unset($this->doc['>name']);
			
			// get rid of the string
			unset($this->xml);
		}
		
		private function create_xml_doc_go(&$cur_node)
		{
			// reached the end
			if (($s_node = $this->get_next_node()) == null) return;
			
			preg_match("/^<(.*?)(((|\/)>$)|( .*?(\/|)>$))/", $s_node, $matches);
			@list($ph1, $node_name, $attrs, $ph2, $no_children1, $ph4, $no_children2) = $matches;

			// we'll default to not caring about any namespaces in the response
			$cur_node['>name'] = $this->remove_namespace($node_name);
			
			/*
			// set attrs (WE ARE IGNORING ATTRIBUTES FOR NOW)
			if ($attrs != '>')
			{
				preg_match_all("/ (\S+)=(\".*?(?<!\\\)\"|\S+|)/", $attrs, $matches);
				$keys = $matches[1];
				$vals = $matches[2];
				$tmp = array();
				for ($i = 0, $loopend = count($keys); $i < $loopend; ++$i)
				{
					$key = $this->remove_namespace($keys[$i]);
					$val = $this->get_attr_val($vals[$i]);
					
					$tmp[$key] = $val;
				}
				if (!empty($tmp)) $node['>attrs'] = $tmp;
			}
			*/
			
			// see if this node has any children
			if ($no_children1 == '/' || $no_children2 == '/') return;
			
			// get children
			while (($node = $this->get_next_node()) != null)
			{
				// end of node, nothing more to do
				if (strpos($node, '</') === 0)
				{
					return;
				}
				// a child node
				else if ($node[0] == '<')
				{
					// move index back one so we can process again
					$child = array();
					$this->node_index--;
					$this->create_xml_doc_go($child);
					
					$child_name = $child['>name'];
					unset($child['>name']);
					
					// child is empty (use empty string and not null so we can use null as "nothing" when we check for nodes in get() method)
					if (empty($child)) $child = '';
					// child has just 1 child which is a "value" (text node)
					else if (count($child) == 1 && array_key_exists('>value', $child)) $child = $child['>value'];
					
					// this node already exists, make array
					if (array_key_exists($child_name, $cur_node))
					{
						// if it's not a numbered array, make it one with current node as first child
						if (!@array_key_exists(0, $cur_node[$child_name])) $cur_node[$child_name] = array($cur_node[$child_name]);
						$cur_node[$child_name][] = $child;
					}
					// first time we've seen this child, just add it on
					else
					{
						$cur_node[$child_name] = $child;
					}
				}
				// text node
				else
				{
					$cur_node['>value'] = strings::xml_decode($node);
				}
			}
		}
		
		private function get_next_node()
		{
			if ($this->node_index == $this->num_nodes) return null;
			return ($this->nodes[$this->node_index++]);
		}
		
		private function remove_namespace($str, $delimiter = ':')
		{
			return ((($i = strpos($str, $delimiter)) !== false) ? substr($str, $i + 1) : $str);
		}
		
		private function get_attr_val($val)
		{
			$len = strlen($val);
			return (($len > 0 && $val[0] == '"' && $val[$len - 1] == '"') ? str_replace('\\"', '"', substr($val, 1, $len - 2)) : $val);
		}
	}
}


?>