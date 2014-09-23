<?php

class MSort
{
	public $a, $b, $dir, $key, $cmp, $obj;
	
	function MSort(&$_a, $_dir = '', $_key = '')
	{
		$this->a = &$_a;
		$this->b = array();
		$this->dir = $_dir;
		$this->key = $_key;
	}
	
	function sort()
	{
		if (!empty($this->cmp))
		{
			$this->merge_sort_2d_cmp(0, count($this->a) - 1);
		}
		else if (!empty($this->obj))
		{
			$this->merge_sort_2d_obj(0, count($this->a) - 1);
		}
		else if ($this->dir == 'asc')
		{
			if ($this->key == null) $this->merge_sort_1d_asc(0, count($this->a) - 1);
			else                    $this->merge_sort_2d_asc(0, count($this->a) - 1);
		}
		else
		{
			if ($this->key == null) $this->merge_sort_1d_desc(0, count($this->a) - 1);
			else                    $this->merge_sort_2d_desc(0, count($this->a) - 1);
		}
	}
	
	function merge_sort($lo, $hi)
	{
		if ($lo < $hi)
		{
			$m = ($lo + $hi) >> 1;
			$this->merge_sort($lo   , $m);
			$this->merge_sort($m + 1, $hi);
			$this->merge($lo, $m, $hi);
		}
	}
	
	function merge_sort_2d_asc($lo, $hi)
	{
		if ($lo < $hi)
		{
			$m = ($lo + $hi) >> 1;
			$this->merge_sort_2d_asc($lo   , $m);
			$this->merge_sort_2d_asc($m + 1, $hi);
			$this->merge_2d_asc($lo, $m, $hi);
		}
	}
	
	function merge_sort_2d_desc($lo, $hi)
	{
		if ($lo < $hi)
		{
			$m = ($lo + $hi) >> 1;
			$this->merge_sort_2d_desc($lo   , $m);
			$this->merge_sort_2d_desc($m + 1, $hi);
			$this->merge_2d_desc($lo, $m, $hi);
		}
	}
	
	function merge_sort_2d_cmp($lo, $hi)
	{
		if ($lo < $hi)
		{
			$m = ($lo + $hi) >> 1;
			$this->merge_sort_2d_cmp($lo   , $m);
			$this->merge_sort_2d_cmp($m + 1, $hi);
			$this->merge_2d_cmp($lo, $m, $hi);
		}
	}
	
	function merge_sort_2d_obj($lo, $hi)
	{
		if ($lo < $hi)
		{
			$m = ($lo + $hi) >> 1;
			$this->merge_sort_2d_obj($lo   , $m);
			$this->merge_sort_2d_obj($m + 1, $hi);
			$this->merge_2d_obj($lo, $m, $hi);
		}
	}
	
	function merge_1d_num_asc($lo, $m, $hi)
	{
		$i = 0;
		$j = $lo;
		
		// copy first half of array a to helper array b
		while ($j <= $m)
			$this->b[$i++] = $this->a[$j++];
		
		$i = 0;
		$k = $lo;
		
		// copy back next-greatest element at each time
		while ($k < $j && $j <= $hi)
		{
			if ($this->a[$j] > $this->b[$i]) $this->a[$k++] = $this->b[$i++];
			else                             $this->a[$k++] = $this->a[$j++];
		}
		
		// copy back remaining elements of first half (if any)
		while ($k < $j)
			$this->a[$k++] = $this->b[$i++];
	}
	
	function merge_2d_asc($lo, $m, $hi)
	{
		$i = 0;
		$j = $lo;
		
		// copy first half of array a to helper array b
		while ($j <= $m)
			$this->b[$i++] = $this->a[$j++];
		
		$i = 0;
		$k = $lo;
		
		// copy back next-greatest element at each time
		while ($k < $j && $j <= $hi)
		{
			if ($this->a[$j][$this->key] > $this->b[$i][$this->key]) $this->a[$k++] = $this->b[$i++];
			else                                                     $this->a[$k++] = $this->a[$j++];
		}
		
		// copy back remaining elements of first half (if any)
		while ($k < $j)
			$this->a[$k++] = $this->b[$i++];
	}
	
	function merge_2d_desc($lo, $m, $hi)
	{
		$i = 0;
		$j = $lo;
		
		// copy first half of array a to helper array b
		while ($j <= $m)
			$this->b[$i++] = $this->a[$j++];
		
		$i = 0;
		$k = $lo;
		
		// copy back next-greatest element at each time
		while ($k < $j && $j <= $hi)
		{
			if ($this->a[$j][$this->key] < $this->b[$i][$this->key]) $this->a[$k++] = $this->b[$i++];
			else                                                     $this->a[$k++] = $this->a[$j++];
		}
		
		// copy back remaining elements of first half (if any)
		while ($k < $j)
			$this->a[$k++] = $this->b[$i++];
	}
	
	function merge_2d_cmp($lo, $m, $hi)
	{
		$i = 0;
		$j = $lo;
		
		// copy first half of array a to helper array b
		while ($j <= $m)
			$this->b[$i++] = $this->a[$j++];
		
		$i = 0;
		$k = $lo;
		
		// copy back next-greatest element at each time
		while ($k < $j && $j <= $hi)
		{
			if ($this->cmp($this->a[$j], $this->b[$i])) $this->a[$k++] = $this->b[$i++];
			else                                        $this->a[$k++] = $this->a[$j++];
		}
		
		// copy back remaining elements of first half (if any)
		while ($k < $j)
			$this->a[$k++] = $this->b[$i++];
	}
	
	function merge_2d_obj($lo, $m, $hi)
	{
		// copy first half of array a to helper array b
		for ($i = 0, $j = $lo; $j <= $m; ++$i, ++$j)
			$this->b[$i] = $this->a[$j];
		
		// copy back next-greatest element at each time
		for ($i = 0, $k = $lo; $k < $j && $j <= $hi; ++$k)
		{
			if ($this->obj->cmp($this->a[$j], $this->b[$i])) $this->a[$k] = $this->b[$i++];
			else                                             $this->a[$k] = $this->a[$j++];
		}
		
		// copy back remaining elements of first half (if any)
		while ($k < $j)
			$this->a[$k++] = $this->b[$i++];
	}
}

function msort(&$a, $dir = 'asc', $key = null)
{
	$s = new MSort($a, $dir, $key);
	$s->sort();
}

function msort_cmp(&$a, $cmp)
{
	$s = new MSort($a);
	$s->cmp = $cmp;
	$s->sort();
}

// this is weird, but I needed it so why not
function msort_obj(&$a, &$obj)
{
	$s = new MSort($a);
	$s->obj = $obj;
	$s->sort();
}

?>