<?php
/* ----
 * Description:
 *	wProPHP HTML functions 
 * Programmers:
 *  cp C-P
 * 	kk Koding Kevin
 * 	mc Merlin Corey
 *	vy Vyrus001
 * History:
 * 	0.0.5 2008March27 Refactored with array based attributes
 * 	0.0.4 2008March10 Added form(), fieldset(), and several others
 * 	0.0.3 2008February04 Updated to house-style
 * 	0.0.2 2007September11 Added more functions, made more consistent in naming
 *	0.0.1 2007September08 Initial rewrite and consolidation of form-related HTML functions
 * Todo:
 *  Need to add full container functions for everything using start and end's with contents in between for recursive building
 * 	Audit all functions
 * 	Talk about conventions (order of arguments, naming, etc)
 * ---- */

if (class_exists("html"))
{
	trigger_error('Error: "html" already exists!', E_USER_WARNING);
}
else
{
	class html
	{
		public static function element_start($name, $attributes = array(), $end = true)
		{
			$ml = '<'.$name;
			foreach ($attributes as $key => $value)
			{
				if ($value === "\0")
				{
					$ml .= ' '.$key;
				}
				else
				{
					$ml .= ' '.$key.' = "'.$value.'"';
				}
			}		
			
			return ($ml.($end ? '>' : ''));	
		}
		
		public static function element_end($name, $end = true)
		{
			return ($end ? '</'.$name.'>' : ' />');
		}
		
		public static function element($name, $contents, $attributes = array(), $end = true)
		{
			return (html::element_start($name, $attributes, $end).$contents.html::element_end($name, $end));
		}
		
		private static function default_attribute(&$attributes, $key, $value)
		{
			if (!array_key_exists($key, $attributes))
			{
				$attributes[$key] = $value;
			}
		}

		private static function default_attributes(&$attributes, $pairs)
		{
			foreach ($pairs as $key => $value)
			{
				html::default_attribute($attributes, $key, $value);
			}
		}
		
		private static function match_attributes(&$attributes, $left, $right)
		{
				$has_left = array_key_exists($left, $attributes);
				$has_right = array_key_exists($right, $attributes);
				if ($has_left && !$has_right)
				{
					$attributes[$right] = $attributes[$left];
				}
				else if (!$has_left && $has_right)
				{
					$attributes[$left] = $attributes[$right];
				}
		}
		
		private static function match_id_name(&$attributes, $match)
		{
			if ($match)
			{
				html::match_attributes($attributes, 'id', 'name');
			}
		}
		
		public static function h($level, $contents, $attributes = array())
		{
			return (html::element('h'.$level, $contents, $attributes));
		}
		
		public static function p($contents, $attributes = array())
		{
			return (html::element('p', $contents, $attributes));
		}

		public static function img($attributes)
		{
			html::default_attribute($attributes, 'alt', 'An image');
			return (html::element('img', '', $attributes, false));
		}

		public static function img_src($src, $attributes = array())
		{
			$attributes['src'] = $src;
			return (html::img($attributes));
		}

		public static function a($contents, $attributes = array())
		{
			html::default_attribute($attributes, 'target', 'self');
			return (html::element('a', $contents, $attributes));
		}

		public static function html_start($attributes = array())
		{
			return (html::element_start('html', $attributes));
		}

		public static function html_end()
		{
			return (html::element_end('html'));
		}
		
		/*
		public static function html($contents, $attributes = array())
		{
			return (html::element('html', $contents, $attributes));
		}
		*/

		public static function head_start($attributes = array())
		{
			return (html::element_start('head', $attributes));	
		}
		
		public static function head_end()
		{
			return (html::element_end('head'));
		}
		
		public static function head($contents, $attributes = array())
		{
			return (html::element('head', $contents, $attributes));
		}

		public static function title($title = "Untitled")
		{
			return (html::element('title', $title));
		}

		public static function body_start($attributes = array())
		{
			return (html::element_start('body', $attributes));
		}
		
		public static function body_end()
		{
			return (html::element_end('body'));
		}
		
		public static function body($contents, $attributes)
		{
			return (html::element('body', $contents, $attributes));
		}
		
		public static function div_start($attributes = array())
		{
			return (html::element_start('div', $attributes));
		}
		
		public static function div_end()
		{
			return (html::element_end('div'));
		}

		public static function div($contents, $attributes = array())
		{
			return (html::element('div', $contents, $attributes));
		}

		public static function link($attributes)
		{
			html::default_attributes($attributes, array('rel' => 'stylesheet', 'type' => 'text/css'));
			return (html::element('link', '', $attributes));
		}
		
		public static function link_href($href, $attributes = array())
		{
			$attributes['href'] = $href;
			return (html::link($attributes));
		}
		
		// Has no defaults to allow blank script tags
		public static function script_start($attributes = array())
		{
			return (html::element_start('script', $attributes));
		}
		
		public static function script_end()
		{
			return (html::element_end('script'));
		}
		
		public static function script($contents, $attributes = array())
		{
			html::default_attribute($attributes, 'type', 'text/javascript');
			return (html::element('script', $contents, $attributes));
		}

		// Has no defaults to allow blank style tags
		public static function style_start($attributes = array())
		{
			return (html::element_start('style', $attributes));
		}
		
		public static function style_end()
		{
			return (html::element_end('style'));
		}
		
		public static function style($contents, $attributes = array())
		{
			html::default_attribute($attributes, 'type', 'text/css');
			return (html::element('style', $contents, $attributes));
		}

		public static function style_import()
		{
			$args = func_get_args();
			$arg_count = func_num_args();
			$imports = '';
			for ($current = 0; $current < $arg_count; ++$current)
			{
				$imports .= "\n\t@import \"".$args[$current]."\";\n";
			}

			return (html::style($imports, array('media' => 'all')));
		}
		
		public static function br($attributes = array())
		{
			return (html::element('br', '', $attributes, false));
		}
		
		// Has no defaults to allow blank form tags
		public static function form_start($attributes = array())
		{
			return (html::element_start('form', $attributes));
		}
		
		public static function form_end()
		{
			return (html::element_end('form'));
		}
		
		public static function form($contents, $attributes = array())
		{
			html::default_attributes($attributes, array('action' => 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'],
				'name' => 'some_form', 'method' => 'POST'));
			return (html::element('form', $contents, $attributes));
		}

		public static function fieldset_start($attributes = array())
		{
			return (html::element_start('fieldset', $attributes));
		}
		
		public static function fieldset_end()
		{
			return (html::element_end('fieldset'));
		}
		
		public function fieldset($contents, $attributes = array())
		{
			return (html::element('fieldset', $contents, $attributes));
		}

		public function fieldset_legend($legend, $contents, $attributes = array())
		{
			return (html::element('fieldset', html::legend($legend).$contents, $attributes));
		}

		public static function legend($contents, $attributes = array())
		{
			return (html::element('legend', $contents, $attributes));
		}
		
		public static function label($contents, $attributes = array())
		{
			return (html::element('label', $contents, $attributes));
		}
		
		public static function label_for($what, $attributes = array())
		{
			$attributes['for'] = $what;
			return (html::label(strings::Proper($what).':', $attributes));
		}
		
		public static function input($attributes, $match_id_name = true)
		{
			html::match_id_name($attributes, $match_id_name);
			return (html::element('input', '', $attributes, false));
		}
	
		public static function hidden($attributes, $match_id_name = true)
		{
			$attributes['type'] = 'hidden';
			return (html::input($attributes, $match_id_name));
		}
		
		public static function hidden_array($elements)
		{
			$contents = '';
			
			foreach ($elements as $name => $value)
			{
				$contents .= html::hidden(array('name' => $name, 'value' => $value));
			}
			
			return ($contents);
		}

		public static function text($attributes, $match_id_name = true)
		{
			$attributes['type'] = 'text';
			return (html::input($attributes, $match_id_name));
		}

		public static function password($attributes, $match_id_name = true)
		{
			$attributes['type'] = 'password';
			return (html::input($attributes, $match_id_name));
		}

		public static function checkbox($attributes, $match_id_name = true)
		{
			$attributes['type'] = 'checkbox';
			return (html::input($attributes, $match_id_name));
		}

		public static function radio($attributes, $match_id_name = true)
		{
			$attributes['type'] = 'radio';
			return (html::input($attributes, $match_id_name));
		}

		public static function submit($attributes, $match_id_name = true)
		{
			$attributes['type'] = 'submit';
			return (html::input($attributes, $match_id_name));
		}
		
		public static function button($attributes, $match_id_name = true)
		{
			$attributes['type'] = 'button';
			return (html::input($attributes, $match_id_name));
		}

		public static function file($attributes, $match_id_name = true)
		{
			$attributes['type'] = 'file';
			return (html::input($attributes, $match_id_name));
		}

		public static function textarea($contents, $attributes = array(), $match_id_name = true)
		{
			html::match_id_name($attributes, $match_id_name);
			return (html::element('textarea', $contents, $attributes));
		}

		public static function option($contents, $attributes = array())
		{
			return (html::element('option', $contents, $attributes));
		}
		
		public static function option_label_value($label, $value, $attributes = array())
		{
			$attributes['label'] = $label;
			$attributes['value'] = $value;
			return (html::option($label, $attributes));
		}
		
		public static function options($options, $selected = '')
		{
			$contents = '';

			foreach ($options as $option)
			{
				if (is_array($option))
				{
					$attributes = array('value' => $option['value']);
					if (isset($option['selected']) || $option['value'] == $selected)
					{
						$attributes['Selected'] = "\0";
					}
					$contents .= html::option($option['caption'], $attributes);
				}
				else
				{
					$attributes = array('value' => $option);
					if ($selected == $option)
					{
						$attributes['Selected'] = "\0";
					}
					$contents .= html::option($option, $attributes);
				}
			} // each option
			
			return ($contents);			
		}
		
		public static function options_n($options, $selected = '', $start = 0)
		{
			$options_n = array();
			$count = count($options);
			for ($current = 0; $current < $count; ++$current)
			{
				if (is_array($options[$current]))
				{
					$options[$current]['value'] = $start;
					$options_n[] = $options[$current];
				}
				else
				{
					$options_n[] = array('value' => $start, 'caption' => $options[$current]);
				}
				++$start;
			}
			return (html::options($options_n, $selected));
		}
		
		public static function optgroup_start($attributes)
		{
			return (html::element_start('optgroup', $attributes));
		}
		
		public static function optgroup_end()
		{
			return (html::element_end('optgroup'));
		}
		
		public static function optgroup($contents, $attributes)
		{
			return (html::element('optgroup', $contents, $attributes));
		}

		public static function optgroup_label($label, $contents, $attributes = array())
		{
			$attributes['label'] = $label;
			return (html::optgroup($contents, $attributes));
		}

		public static function select_start($attributes = array())
		{
			return (html::element_start('select', $attributes));
		}
		
		public static function select_end()
		{
			return (html::element_end('select'));
		}
		
		// TODO: Needs to support option groups in the array somehow
		public static function select($contents, $attributes, $selected = '', $match_id_name = true)
		{
			html::match_id_name($attributes, $match_id_name);
			return (html::element('select', $contents, $attributes));
		}
		
		/*
		public static function select_box($name, $options, $selected = "", $other = "", $id = NULL)
		{
			return (html::select(html::options($options, $selected), array('name' => $name)));
		}
		*/
						
		public static function check_boxes($options, $selected = null, $other = null, $post_ml = '<br />')
		{
			$s = "";
                        
			foreach ($options as $item)
			{

				if (is_array($item))
				{
					// val is optional
					$val = $item["value"];
					//if (empty($val)) $val = 1;
					
					$name = $item["name"];
					$s .= "\n\t<label><input type = \"checkbox\" name = \"$name\" value = \"$val\"".(($other != null) ? " ".$other : "");
					if ($val == $selected || ($selected && in_array($val, $selected)))
					{
                                                
						$s .= " checked='checked'";
					}
					$s .= " />{$item["caption"]}</label>{$post_ml}";
				}
				else
				{
					$s .= "\n\t<label><input type = \"checkbox\" name = \"$item\" value = \"$item\"".(($other != null) ? " ".$other : "");
					if ($selected && in_array($item, $selected))
					{
						$s .= " checked='checked'";
					}			
					$s .= " />$item</label>{$post_ml}";
				}
			}
			
			return ($s);
		}
		
		public static function radios($name, $options, $selected = null, $other = null, $post_ml = '<br />')
		{
			$s = "";
			foreach ($options as $item)
			{
				if (is_array($item))
				{
					$s .= "\n\t<label><input type = \"radio\" name = \"$name\" value = \"{$item["value"]}\"".(($other != null) ? " ".$other : "");
					if ($item["value"] == $selected || isset($item["selected"]) && $item["selected"])
					{
						$s .= " checked";
					}
					$s .= " />{$item["caption"]}</label>{$post_ml}";
				}
				else
				{
					$s .= "\n\t<label><input type = \"radio\" name = \"$name\" value = \"{$item}\"".(($other != null) ? " ".$other : "");
					if ($item == $selected)
					{
						$s .= " checked";
					}			
					$s .= " />{$item}</label>{$post_ml}";
				}
			}
			return ($s);
		}
		
		public static function param($attributes)
		{
			return (html::element('param', '', $attributes, false));
		}
		
		public static function param_name_value($name, $value, $attributes = array())
		{
			$attributes['name'] = $name;
			$attributes['value'] = $value;
			return (html::param($attributes));
		}
		
		public static function params($params)
		{
			$ml = '';
			foreach ($params as $name => $value)
			{
				$ml .= html::param(array('name' => $name, 'value' => $value));
			}
			return ($ml);
		}		
		
		public static function object_start($attributes)
		{
			return (html::element_start('object', $attributes));
		}
		
		public static function object_end()
		{
			return (html::element_end('object'));
		}
		
		public static function object($contents, $attributes)
		{
			return (html::element('object', $contents, $attributes));
		}
		
		public static function object_params($params, $attributes)
		{
			return (html::object(html::params($params), $attributes));
		}
				
		public static function start_object($type, $data, $attributes = array())
		{
			return ("<object type = \"{$type}\" data = \"{$data}\"".($other != "" ? " ".$other : "").">");
		}
		
		public static function flash($path, $attributes = array())
		{
			html::default_attributes($attributes, array('type' => 'application/x-shockwave-flash', 'data' => $path));
			return (html::object_params(array('movie' => $path), $attributes));
		}
		
		// TODO: Fix all below this line
		// Todo: Rework like in mod_head ?
		public static function row_label_input($type, $name, $caption = NULL, $value = NULL, $other_label = NULL, $other_input = NULL)
		{
			return ("<tr><td>".html::Label($name, $caption, $other_label)."</td><td>".html::Input($type, $name, $value, $other_input)."</td></tr>");		
		}		
		
		public static function meta($name, $content, $type = "name", $scheme = "")
		{
			return ("<meta {$type} = \"{$name}\" content = \"{$content}\"".($scheme != "" ? " scheme = \"$scheme\"" : "").">\n");
		}
		
		//public static function doc_type($attributes = array())
		public static function doc_type($type, $other)
		{
			//return (html::element('!DOCTYPE', 
			return ("<!DOCTYPE {$type}".(($other != "" ? " ".$other : "")).">\n");
		}		
	};
}

?>
