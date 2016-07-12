<?php

namespace SchoolChat\Utility;

require 'Contract/ConfigurationUtilityInterface.php';

class WindowsConfigurationUtility implements ConfigurationUtility
{

    public function getConnectionString()
    {
		try{
			$shell = new \COM ( 'WScript.Shell' );
			$result = $shell -> regRead ( 'HKEY_LOCAL_MACHINE\SOFTWARE\WOW6432Node\SchoolChat\dbConnect' );  
			return (string)$result;
			
		}
		catch(Exception $e){
			return $e;
		}
    }
	
	public function getLogPath()
	{
		// try{
			// $shell = new \COM ( 'WScript.Shell' );
			// $result = $shell -> regRead ( 'HKEY_LOCAL_MACHINE\SOFTWARE\WOW6432Node\SchoolChat\logPath' );  
			// return (string)$result;
			
		// }
		// catch(Exception $e){
			// return "";
		// }
		return "";	
	}
	
	public function getAvatar($uid) 
	{
		$preUrl = "uc_server/data/avatar/";
		//$size = in_array($size, array('big', 'middle', 'small')) ? $size : 'middle';
		$uid = abs(intval($uid));
		//$uid = sprintf("%09d", $uid);
		$dir1 = substr($uid, 0, 3);
		$dir2 = substr($uid, 3, 2);
		$dir3 = substr($uid, 5, 2);
		return "hello";//$preUrl.$dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
	}
	
}


