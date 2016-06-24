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
}


