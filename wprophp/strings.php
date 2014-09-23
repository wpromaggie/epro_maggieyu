<?php
/* ----
 * Description:
 *	String Utility functions
 * Programmers:
 *  cp C-P
 * 	kk Koding Kevin
 * 	mc Merlin Corey
 *	vy Vyrus001
 * History:
 *	0.0.8 2008May01 Added *word*() functions, find_first_of(), and find_last_of()
 *	0.0.7 2008February18 Added array_to_xml()
 *	0.0.6 2008February04 Converted to house-style
 *	0.0.5 2007December05 Added Proper
 * 	0.0.4 2007September12 Refactored for wpro::php and included Kevin's string functions
 * 	0.0.3 2005July02 Added Ownership()
 * 	0.0.2 2005June14 Added Plural()
 * 	0.0.1 2004November25 First known version
 * Todo:
 *	Audit names and arguments for consistency
 * ---- */

if (class_exists('strings'))
{
	trigger_error('Error: "strings" already exists!', E_USER_WARNING);
}
else
{
	function sanitize_walker(&$value, $key)
	{
		$value = strings::Sanitize($value);
	}

	class strings
	{

	public static function array_maker($reqVars, $strict=false)
		{
		$ar=null;
		if(is_array($reqVars))
			{
			foreach($reqVars as $req)
				{
				$ar[$req]=$_REQUEST[$req];
				}
			if($strict && in_array(null,$ar))
				{
				return(false); // Something is missing
				}	
			}
		else
			{
			return(false); // 1st argument is not an array
			}
		return($ar);
		}
		
		public static function xml_decode($str)
		{
			return str_replace(strings::xml_get_entities_encoded(), strings::xml_get_entities_plain(), $str);
		}
		
		public static function xml_encode($str)
		{
			return str_replace(strings::xml_get_entities_plain(), strings::xml_get_entities_encoded(), $str);
		}
		
		private static function xml_get_entities_plain()
		{
			return (array('&', "'", '<', '>'));
		}
		
		private static function xml_get_entities_encoded()
		{
			return (array('&amp;', '&apos;', '&lt;', '&gt'));
		}
		
		public static function array_to_xml($array, $depth = 0)
		{
			$xml = $ml = '';

			if (is_array($array))
			{
				foreach ($array as $key => $data)
				{
					if (is_array($data))
					{
						$data = strings::array_to_xml($data, ++$depth);
					}
					//$ml .= "\n".str_repeat("\t", $depth)."<{$key}>\n".str_repeat("\t", $depth + 1).$data."\n".str_repeat("\t", $depth)."</{$key}>";
					$ml .= "<{$key}>".strings::xml_encode($data)."</{$key}>";
				} // each node
				$xml .= $ml;
			}
			else
			{
				$xml = '<data>'.$array.'</data>';
			}

			return ($xml);
		}

		public static function xml_to_array($xml)
		{
			$array = array();

			preg_match_all("/<(.*?)\s.*?>(.*?)<\/\\1>/", $xml, $matches);
			$keys = $matches[1];
			$values = $matches[2];
			$count = count($keys);
			for ($j = 0; $j < count($keys); $j++)
			{
				$array[$keys[$j]] = $values[$j];
			}

			return ($array);
		}

		// Pre: Receives prepend string and string to prepend to
		// Post: If string is not empty, prepends prepend string
		public static function prepend($prepend, $source)
		{
			return ((strlen($source)) ? ($prepend.$source) : ($source));
		}

		// Pre: Receives string to add space to
		// Post: Adds a space to a string if it is not empty
		public static function add_space($source)
		{
			return (strings::Prepend(' ', $source));
		}

		// Pre: Receives email string
		// Post: Returns true if string represents an email address
		public static function is_email($address)
		{
			if (eregi('^[a-z0-9]+([-_\.]?[a-z0-9])+@[a-z0-9]+([-_\.]?[a-z0-9])+\.[a-z]{2,4}', $address))
			{
				return (true);
			}
			else
			{
				return (false);
			}
		}

		public static function is_irc_nick($nick)
		{
			$b_return = false;
			$length = strlen($nick);

			if ($length >= 1 && $length <= 32)
			{
				// Thanks boki!
				$b_return = preg_match('/^[\w\-\[\]`{}|\^]+$/', $nick);
			}

			return ($b_return);
		}

		public static function make_irc_nick($nick)
		{
			$string = preg_replace('[~!@#$%^&*()+=\/;:\'\'<>,.?\ ]', '_', $string);
			return ($string);
		}

		public static function plural($object, $amount = 2)
		{
			// 0 things, 1 thing, many things
			if ($amount != 1)
			{
				switch ($object[strlen($object) - 1])
				{
					// county becomes counti
					case 'y':
						$object[strlen($object) - 1] = 'i';
					// counti becomes counties
					// kiss becomes kisses
					// box becomes boxes
					case 's':
					case 'x':
						$object .= 'es';
					break;

					// thing becomes things
					default:
						$object .= 's';
					break;
				};
			}

			return ($object);
		}

		public static function ownership($party)
		{
			switch ($party[strlen($party) - 1])
			{
				case 's':
					$party .= '\'';
				break;

				default:
					$party .= '\'s';
				break;
			}

			return ($party);
		}

		public static function starts_with($string, $search)
		{
			return (strpos($string, $search) === 0);
		}

		public static function ends_with($string, $search)
		{
			$pos = strpos($string, $search);
			return ($pos !== false && $pos === (strlen($string) - strlen($search)));
		}

		public static function prepare_url_for_var($url, $scheme = 'http')
		{
			// get url info
			$url_info = parse_url($url);

			// make sure we have a protocol
			if (empty($url_info['scheme']))
			{
				$url = $scheme . '://' . $url;

				// now parse again
				$url_info = parse_url($url);
			}

			// if there's no query string and no path and url doesn't end in slash, add slash and query string (eg, http://a.com -> http://a.com/?)
			if (empty($url_info['query']) && empty($url_info['path']) && !str_ends_with($url, '/')) $url .= '/?';

			// else if no query string, start one
			else if (empty($url_info['query'])) $url .= '?';

			// otherwise start another variable
			else $url .= '&';

			return $url;
		}

		public static function random_string($length, $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456790!@#$%^&*()')
		{
			$string = '';
			$max_index = strlen($characters) - 1;

			for ($nCur = 0; $nCur < $length; $nCur++)
			{
				$string .= $characters[mt_rand(0, $max_index)];
			}

			return ($string);
		}

		public static function random_digits($length)
		{
			return (strings::random_string($nlength, '0123456789'));
		}

		public static function sanitize(&$value)
		{
			if (is_array($value))
			{
				array_walk($value, 'sanitize_walker');
			}
			else
			{
				$value = addslashes($value);
			}
			return ($value);
		}

		public static function unsanitize(&$value)
		{
			return (get_magic_quotes_gpc() ? $value : stripslashes($value));
		}

		public static function sanitize_request()
		{
			if (!get_magic_quotes_gpc())
			{
				if (func_num_args())
				{
					$args = func_get_args();
					foreach ($args as $arg)
					{
						$_REQUEST[$arg] = strings::Sanitize($_REQUEST[$arg]);
					} // each argument
				}
				else
				{
					foreach($_REQUEST as $key => $dirty_value)
					{
						$_REQUEST[$key] = strings::Sanitize($dirty_value);
					}
				}
			} // not already quoting and has arguments
		}

		public static function proper($source)
		{
			$source = trim($source);
			return (strtoupper(substr($source, 0, 1)).substr($source, 1));
		}

		public static function remove_quotes($source)
		{
			return (str_replace(array('"','"'),'',$source));
		}

		public static function ml_one_line($ml)
		{
			return (strings::ml_one_line_ref($ml));
		}

		public static function ml_one_line_ref(&$ml)
		{
			$endl = (strpos($ml, "\r\n") !== false) ? "\r\n" : ((strpos($ml, "\r") !== false) ? "\r" : "\n");
			return trim(preg_replace("/>(\s*)</m","><",str_replace($endl,' ',preg_replace("/^\s*/m","",$ml))));
		}

		// Pre: Receives string to search and characters that should be found
		// Post: Returns first position of member of characters in stringor FALSE
		// Note: strpbrk() is a similar native function but returns substrs not positions
		public static function find_first_of($string, $characters)
		{
			$position = false;
			$length = strlen($string);
			for ($char = 0; $char < $length && $position === false; ++$char)
			{
				if (false !== strpos($characters, $string[$char]))
				{
					$position = $char;
				}
			} // each character

			return ($position);
		}

		// Pre: Receives string to search and characters that should be found
		// Post: Returns last position of member of characters in stringor FALSE
		public static function find_last_of($string, $characters)
		{
			$position = false;
			$length = strlen($string);
			for ($char = $length - 1; $char >= 0 && $position === false; --$char)
			{
				if (false !== strpos($characters, $string[$char]))
				{
					$position = $char;
				}
			} // each character

			return ($position);
		}
		// Pre: Receives string and optional word seperator
		// Post: Tokenizes string into array of words (alias of explode, basically, but defaults to ' ' for seperator)
		// TODO: Make custom explosion so we can break on seperators like other *word* functions instead of just one
		public static function words($string, $seperator = ' ')
		{
			return (explode($seperator, $string));
		}

		// Pre: Receives string and optional word seperators
		// Post: Returns the first word as substring based on seperator position
		public static function first_word($string, $seperators = " \n\t")
		{
			$word = '';
			$pos = strings::find_first_of($string, $seperators);
			if ($pos === false)
			{
				$word = $string;
			}
			else
			{
				$word = substr($string, 0, $pos);
			}

			return ($word);
		}

		// Pre: Receives string and optional word seperators
		// Post: Returns the first word as substring based on seperator position
		public static function last_word($string, $seperators = " \n\t")
		{
			$word = '';
			$pos = strings::find_last_of($string, $seperators);
			if ($pos === false)
			{
				$word = $string;
			}
			else
			{
				$word = substr($string, $pos + 1);
			}

			return ($word);
		}
	}; // strings
}

?>
