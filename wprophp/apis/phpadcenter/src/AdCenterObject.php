<?php
namespace phpadcenter;

class AdCenterObject
{
	function __construct($data = array())
	{
		foreach ($data as $k => $v) {
			$this->$k = $v;
		}
	}
}

?>