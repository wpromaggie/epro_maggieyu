<?php
/* ----
 * Description:
 * 	read a file one "block" at a time
 *  allows processing of large files which would otherwise exceed a reasonable php memory limit
 * Usage:

for ($fi = new file_iterator($file_name, $block_size, $delimiter); $fi->next($block); )
{
	// file data is in $block
}
 */

define('FILE_ITERATOR_DEFAULT_BLOCK_SIZE', 1048576);

class file_iterator
{
	private $file_handle, $do_show_progress, $block_size, $delimiter, $delimiter_len, $previous_leftovers;
	public $count, $num_blocks;
	
	function __construct($file, $block_size = FILE_ITERATOR_DEFAULT_BLOCK_SIZE, $delimiter = "\n", $do_show_progress = false)
	{
		if (is_string($file)) $this->file_handle = fopen($file, 'rb');
		else $this->file_handle = $file;
		
		$this->block_size = $block_size;
		$this->delimiter = $delimiter;
		
		$this->previous_leftovers = '';
		$this->delimiter_len = strlen($delimiter);
		
		$this->do_show_progress = $do_show_progress;
		$this->count = 0;
		$this->num_blocks = (is_string($file)) ? (ceil(filesize($file) / $block_size)) : '?';
	}
	
	public function next(&$block)
	{
		++$this->count;
		if ($this->do_show_progress) echo $this->count.' / '.$this->num_blocks.ENDL;
		
		// see if we're done
		if (feof($this->file_handle))
		{
			$this->end();
			return false;
		}
		
		// get leftovers from previous read and read next block
		$tmp_block = $this->previous_leftovers . fread($this->file_handle, $this->block_size);
		
		// sometimes we seem to reach the end before feof knows it?
		// if tmp_block is empty, we're done
		if (empty($tmp_block))
		{
			$this->end();
			return false;
		}
		
		// if the last read reached the end, just set block to tmp block
		// in case file doesn't end with delimiter
		if (feof($this->file_handle))
		{
			$block = $tmp_block;
			return true;
		}
		
		// look for the end of the last block
		$block_end = strrpos($tmp_block, $this->delimiter);
		if ($block_end === false)
		{
			$block = $tmp_block;
			$this->previous_leftovers = '';
		}
		else
		{
			$block = substr($tmp_block, 0, $block_end);
			$this->previous_leftovers = substr($tmp_block, $block_end + $this->delimiter_len);
		}
		return true;
	}
	
	private function end()
	{
		$this->block = $this->block_size = $this->delimiter = $this->previous_leftovers = null;
		fclose($this->file_handle);
	}
}

?>