<?php

/*
 * read a csv into array
 * 
 * usage: csv::read($data, 'file_path.csv');
 */

class csv
{
	private static $contents, $len, $i, $delimiter;
	
	public static function read(&$csv, $file_name, $delimiter = ',')
	{
		self::$contents = file_get_contents($file_name);
		self::go($csv, $delimiter);
	}
	
	public static function read_str(&$csv, $str, $delimiter = ',')
	{
		self::$contents = $str;
		self::go($csv, $delimiter);
	}
	
	private static function go(&$csv, $delimiter)
	{
		self::$delimiter = $delimiter;
		
		$csv = array();
		for (self::$i = 0, self::$len = strlen(self::$contents); self::$i < self::$len; ++self::$i)
		{
			self::read_row($csv);
		}
	}
	
	private static function read_row(&$csv)
	{
		$row = array();
		
		// each iteration of the loop reads one cell
		for (; self::$i < self::$len; ++self::$i)
		{
			$cell_start_char = self::$contents[self::$i];
			
			// we've reached the end of the row
			if ($cell_start_char == "\n") break;
			if ($cell_start_char == "\r")
			{
				// consume \n if end lines are \r\n
				if (@self::$contents[self::$i+1] == "\n")
				{
					++self::$i;
				}
				break;
			}
			
			// see if our cell is enclosed in quotes
			if ($cell_start_char == '"')
			{
				$is_enclosed = true;
				$cell_start = self::$i + 1;
			}
			else
			{
				$is_enclosed = false;
				$cell_start = self::$i;
			}
			
			// inner loop, get cell contents
			$cell_contents = '';
			for ($j = $cell_start; $j < self::$len; ++$j)
			{
				$ch = self::$contents[$j];
				if ($is_enclosed && $ch == '"')
				{
					// check for double quote escape, consume 2nd "
					if (@self::$contents[$j+1] == '"')
					{
						++$j;
					}
					// we've reached the end of cell
					else
					{
						// consume delimiter (check for delimiter because could also be end of row)
						if (@self::$contents[$j+1] == self::$delimiter) ++$j;
						$row[] = $cell_contents;
						break;
					}
				}
				else if (!$is_enclosed && $ch == self::$delimiter)
				{
					$row[] = $cell_contents;
					break;
				}
				// also need to check for end of line if not enclosed!
				else if (!$is_enclosed && ($ch == "\r" || $ch == "\n"))
				{
					// back up 1 so we deal with row end at beginning of loop like normal
					--$j;
					
					$row[] = $cell_contents;
					break;
				}
				
				$cell_contents .= $ch;
			}
			self::$i = $j;
		}
		if (!empty($row) && !empty($row[0])) $csv[] = $row;
	}
}

?>