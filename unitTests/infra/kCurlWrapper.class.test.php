<?php
/**
 * @package infra
 * @subpackage Storage
 */
#Mock-ups - 
print __DIR__.'/n';
require __DIR__.'/../mock/KalturaLog.mock.php';
#Tested file - 
require 'infra/general/KCurlWrapper.class.php';
require 'vendor/webex/xml/WebexXmlClient.class.php';

class utkCurlWrapperClassPhp extends PHPUnit_Framework_TestCase
{
	const URL1='https://purdue.webex.com/purdue/lsr.php?RCID=4690a025b2a4213d7affd3d5e1138596';
	const URL2='https://purdue.webex.com/purdue/lsr.php?RCID=95d6d14dfb65d77baa76d8eaed204961';
	/**
	* @beforeClass
	*/
	public function includeTestedClass()
	{
		//todo pre-test.
	}

	/**
     * @group 
	 * @group 
     */
	  
	 
	public function testCurlHeader()
	{
		$curlObj = new KCurlWrapper();
		$curlInfo=$curlObj->getHeader(self::URL1);
		$this->assertTrue($curlInfo!=null);
		print_r($curlInfo);

		
		$recordId=null;
		if(isset($curlInfo->headers['set-cookie']))
		{
			$recordId = $curlInfo->getCookieValue($curlInfo->headers['set-cookie'], 'recordId');
			if ($recordId==null)
			{
				throw new Exception('recordId value not found');
			}
		}
		
		print_r($curlInfo->headers['set-cookie']);
		
        $recordId=$curlInfo->getCookieValue($curlInfo->headers['set-cookie'],'recordId');
		print_r ($recordId);
        
		
		
		if(isset($curlInfo->headers['set-cookie']))
		{
			$recordId = $curlInfo->getCookieValue($curlInfo->headers['set-cookie'], 'recordId');
			if ($recordId==null)
			{
				throw new Exception('recordId value not found');
			}
		}
		else
		{
			throw new Exception('set-cookie was not found in header');
		}
		
		if(isset($curlInfo->headers['set-cookie']))
        {
            $recordId = $curlInfo->getCookieValue($curlInfo->headers['set-cookie'], 'recordId');
            if ($recordId==null)
            {
                throw new Exception('recordId value not found');
            }
        }
        else
        {
            throw new Exception('set-cookie was not found in header');
        }
		print_r ($recordId);
	}
	public function testWebexClient()
	{
		$objWebexXmlSecurityContext = new WebexXmlSecurityContext();
		$objWebexXmlClient = new WebexXmlClient('http://purdue.webex.com',$objWebexXmlSecurityContext);
		$this->assertTrue($objWebexXmlClient!=null);
		try
		{
			$objWebexXmlClient = new WebexXmlClient('http://nebulaav.webex.com',$objWebexXmlSecurityContext);
		}
		catch(Exception $e)
		{
			$this->assertTrue("Cannot run on backup."==$e->getMessage());
		}
	}
    /**
     * @afterClass
     */
	public function cleanUp()
	{
	}

}
?>

