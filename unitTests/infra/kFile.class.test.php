<?php
/**
 * @package infra
 * @subpackage Storage
 */
#Mock-ups - 
require __DIR__.'/../mock/KalturaLog.mock.php';
#Tested file - 
require 'infra/storage/kFile.class.php';

class utkFileClassPhp extends PHPUnit_Framework_TestCase
{
	const IN_FILE_NAME='/tmp/infile1.txt';
	const IN_FILE_RO_NAME='/tmp/infile1ro.txt';
	/**
	* @beforeClass
	*/
	public static function includeTestedClass()
	{
		//todo pre-test.
	}
	private function createInputFile($file_name)
	{
		$infile = fopen($file_name, "w");
		fwrite($infile, $file_name);
		fclose($infile);
	}
	private function copySingleFile($src,$dst,$keepSrc=true)
	{
		if(true != kFile::moveFile($src, $dst, false, $keepSrc))
		{
			#$this->failure("kFile::moveFile($src, $dst, false, $keepSrc)");
			return false;
		}
		kFile::deleteFile($dst);
		if (file_exists($dst)) {
			return false;
		}
		return true;
	}

	/**
     * @group kFile
	 * @group copySingleFile
     */
	public function testCopySingleFile()
	{

		#normal case of move file	
		$this->createInputFile(self::IN_FILE_NAME);
		KalturaLog::debug("1. normal case of move file");
		$this->assertTrue($this->copySingleFile(self::IN_FILE_NAME,self::IN_FILE_NAME."out",true));

		#Dest dir is read only
		$this->createInputFile(self::IN_FILE_NAME);
		$OUT_DIR="/tmp/outdir";
		KalturaLog::debug("2. Dest dir is read only");
		$this->assertTrue($this->copySingleFile(self::IN_FILE_NAME,$OUT_DIR."/".self::IN_FILE_NAME."out",true));

		#Source file is read only - only copy
		KalturaLog::debug("3. Source file is read only");
		$this->assertFalse($this->copySingleFile(self::IN_FILE_RO_NAME,self::IN_FILE_NAME."out",true));

		#Source file is read only - try remove
		KalturaLog::debug("4. Source file is read only + request to delete");
		$this->assertFalse($this->copySingleFile(self::IN_FILE_RO_NAME,self::IN_FILE_NAME."out",false));
	}
	private function helperDownloadUrlToString($url)
	{
		$header = kFile::downloadUrlToString($url,2);
		$this->assertTrue($header!=="");
		$body = kFile::downloadUrlToString($url,1);
		$this->assertTrue($body!=="");
		$all = kFile::downloadUrlToString($url,3);
		$this->assertTrue($all!=="");
		$this->assertTrue(strlen($all)!=strlen($body.$header));
	}
	
	public function testHelperDownloadUrlToString()
	{
		$this->helperDownloadUrlToString("www.google.com");
		$this->helperDownloadUrlToString("www.yahoo.com");
	}


	/*
	*@expectedException
	*/
	private function readZeroByteFromFile($file_name)
	{
		try
		{
			$data = kFile::readLastBytesFromFile($file_name,0);
		}
		catch(Exception $e)
		{
			return "";
		}
	}  	
	
	public function testReadLastBytesFromFile()
	{
		$data = kFile::readLastBytesFromFile(self::IN_FILE_NAME,strlen(self::IN_FILE_NAME));
		$this->assertTrue(strcmp($data,self::IN_FILE_NAME)==0);
		$data = $this->readZeroByteFromFile(self::IN_FILE_NAME);
		$this->assertTrue($data==="");
		$data = kFile::readLastBytesFromFile(self::IN_FILE_NAME,3);
		$this->assertTrue($data==="txt");
		try
		{
			$data = kFile::readLastBytesFromFile("NonExistentFile",100);
		}
		catch(Exception $e)
		{
			KalturaLog::debug("Exception:".$e);
		}
	}
	
    /**
     * @afterClass
     */
	public function cleanUp()
	{
		if (file_exist($IN_FILE_NAME))
		{
			unlink($IN_FILE_NAME);
		}
	}
	
}
?>

