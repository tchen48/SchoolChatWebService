<?php

namespace SchoolChat\Utility;


class DiscuzUtility
{
	public function getAvatar($uid, $size, $preUrl = "uc_server/data/avatar/") 
	{
		//$preUrl = "uc_server/data/avatar/";
		$size = in_array($size, array('big', 'middle', 'small')) ? $size : 'middle';
		$uid = abs(intval($uid));
		$dir1 = substr($uid, 0, 3);
		$dir2 = substr($uid, 3, 2);
		$dir3 = substr($uid, 5, 2);
		return $preUrl.$dir1."/".$dir2."/".$dir3."/".substr($uid, -2).$typeadd."_avatar_$size.jpg";
	}
	
}

