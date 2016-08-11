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
		$uid = sprintf("%09d", $uid);
		$dir1 = substr($uid, 0, 3);
		$dir2 = substr($uid, 3, 2);
		$dir3 = substr($uid, 5, 2);
		return $preUrl.$dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2)."_avatar_small.jpg";
	}
	
	public function is404($url) 
	{
		$handle = curl_init($url);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($handle);

		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);

		/* If the document has loaded successfully without any redirection or error */
		if ($httpCode >= 200 && $httpCode < 300) {
			return false;
		} else {
			return true;
		}
	}
	
}


