<?php
/**
 * @package infra
 * @subpackage Storage
 */
#Mock-ups - 
require __DIR__.'/../mock/KalturaLog.mock.php';

interface IApiObject
{
    public function fromObject($srcObj, KalturaDetachedResponseProfile $responseProfile = null);
}

require "/opt/kaltura/app/infra/storage/kFile.class.php";
require "/opt/kaltura/app/api_v3/lib/KalturaPropertyInfo.php";
require "/opt/kaltura/app/api_v3/lib/KalturaDocCommentParser.php";
require "/opt/kaltura/app/api_v3/lib/KalturaTypeReflector.php";
require "/opt/kaltura/app/infra/KAutoloader.php";
require "/opt/kaltura/app/api_v3/lib/KalturaTypeReflectorCacher.php";
require "/opt/kaltura/app/vendor/propel/om/Persistent.php";
require "/opt/kaltura/app/vendor/propel/om/BaseObject.php";
require "/opt/kaltura/app/alpha/lib/model/om/BaseSchedulerConfig.php";
require "/opt/kaltura/app/alpha/lib/model/SchedulerConfig.php";
#Tested file - 
require '/opt/kaltura/app/api_v3/lib/types/KalturaObject.php';
class myTest extends KalturaObject
{
    
}
class kConf
{
 function get($value)
 {
  return "/opt/kaltura/app/cache";
 }
}
class KalturaObjectClassPhp extends PHPUnit_Framework_TestCase
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
	/**
     * @group kFile
	 * @group copySingleFile
     */
	public function testCopySingleFile()
	{
        //KalturaObject->fromObject(Object(SchedulerConfig), NULL)
        $obj = new myTest;
        $obj->fromObject(new SchedulerConfig);
        
//		$this->assertFalse($this->copySingleFile(self::IN_FILE_RO_NAME,self::IN_FILE_NAME."out",false));
	}
	public function testHelperDownloadUrlToString()
	{
	}
}
?>

