<?php

require 'ut.helper.php';

class KalturaLog//Mock
{
	static function err($msg)
	{
		print bcolors::$FAIL."\nERR:".$msg."\n".bcolors::$ENDC;
	}

		static function info($msg)
	{
		print bcolors::$HEADER."\nINF:".$msg."\n".bcolors::$ENDC;
	}

	static function debug($msg)
	{
		print bcolors::$HEADER."\nDBG:".$msg."\n".bcolors::$ENDC;
	}
}
