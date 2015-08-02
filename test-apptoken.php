<?php
require_once('KalturaPHPUnit_Framework_TestCase.php');
/**
 * @group AppToken
 */
class AppToken extends KalturaPHPUnit_Framework_TestCase
{
	/**
	 *
	 * @group appToken
	 */
	public function testAppTokenHash()
	{
		$appToken = new KalturaAppToken();
		$appToken->expiry = time()+999999;
		$appToken->sessionDuration = time()+999999;
		$appToken->hashType = 2;
		$newAppToken = $this->client->appToken->add($appToken);
		$tokenHash = md5($this->client->getKs().$newAppToken->token);
		$testAppTokenHash = $this->createTest('appToken', 'startSession', array("id" => $newAppToken->id, "tokenHash" => $tokenHash), 'validateAppTokenHash');
		$testAppTokenHash->runTest();
	}

	
	public function validateAppTokenHash($result)
	{
		$this->assertInstanceOf('KalturaSessionInfo' , $result);
		$this->assertNotEmpty($result->ks);
		return;
	}
}
