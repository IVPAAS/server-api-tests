<?php

require_once __DIR__ . '/../KalturaApiTestCase.php';

/**
 * AppToken service test case.
 */
class AppTokenServiceTest extends KalturaApiTestCase
{
	protected $createdAppTokens = array();
	
	/* (non-PHPdoc)
	 * @see KalturaApiTestCase::setUp()
	 */
	protected function setUp()
	{
		parent::setUp();
	}
	
	/* (non-PHPdoc)
	 * @see KalturaApiTestCase::tearDown()
	 */
	protected function tearDown()
	{
		foreach($this->createdAppTokens as $id)
		{
			$this->delete($id);
		}
		
		parent::tearDown();
	}

	protected function getUserClient($userId = null)
	{
		return $this->getClient(KalturaSessionType::USER);
	}
	
	protected function getAdminClient()
	{
		return $this->getClient(KalturaSessionType::ADMIN);
	}
	
	protected function delete($id)
	{
		$client = $this->getAdminClient();
		$client->appToken->delete($id);
		
		if(isset($this->createdAppTokens[$id]))
			unset($this->createdAppTokens[$id]);
	}
	
	/**
	 * @return KalturaAppToken
	 */
	protected function create()
	{
		$appToken = new KalturaAppToken();
		
		return $this->add($appToken);
	}
	
	/**
	 * @param KalturaAppToken $appToken
	 * @return KalturaAppToken
	 */
	protected function add(KalturaAppToken $appToken)
	{
		$client = $this->getAdminClient();
		$appToken = $client->appToken->add($appToken);
		
		$this->createdAppTokens[$appToken->id] = $appToken->id;
		
		return $appToken;
	}
	
	public function testAdd()
	{
		$appToken = $this->create();
		$this->assertNotNull($appToken->id);
		$this->assertNotNull($appToken->partnerId);
		$this->assertNotNull($appToken->createdAt);
		$this->assertNotNull($appToken->updatedAt);
		$this->assertNotNull($appToken->token);
		$this->assertNotNull($appToken->sessionDuration);
	}
	
	public function testUpdate()
	{
		$appToken = $this->create();
		$update = new KalturaAppToken();
		$update->sessionDuration = rand(10000, 100000);
		$update->sessionType = rand(KalturaSessionType::ADMIN, KalturaSessionType::USER);
		$update->sessionUserId = uniqid('test_');
		$update->sessionPrivileges = uniqid('test_');
		
		$client = $this->getAdminClient();
		$appToken = $client->appToken->update($appToken->id, $update);
		
		$this->assertEquals($update->sessionDuration, $appToken->sessionDuration);
		$this->assertEquals($update->sessionType, $appToken->sessionType);
		$this->assertEquals($update->sessionUserId, $appToken->sessionUserId);
		$this->assertEquals($update->sessionPrivileges, $appToken->sessionPrivileges);
	}
	
	public function testGet()
	{
		$appToken = $this->create();
		$appTokenId = $appToken->id;
		
		$client = $this->getAdminClient();
		$appToken = $client->appToken->get($appTokenId);
		
		$this->assertEquals($appTokenId, $appToken->id);
	}
	
	public function testDelete()
	{
		$appToken = $this->create();
		$this->delete($appToken->id);
	}
	
	public function testStartSessionFull()
	{
		for($i = 0; $i < 50; $i++)
		{
			$this->startSession(
				rand(85400, 87400),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				uniqid('test_'),
				uniqid('test_'),
				uniqid('test_'),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				rand(85400, 87400),
				uniqid('test_'),
				rand(85400, 87400)
			);
		}
	}
	
	public function testStartSessionNoSessionDuration()
	{
		for($i = 0; $i < 50; $i++)
		{
			$this->startSession(
				null,
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				uniqid('test_'),
				uniqid('test_'),
				uniqid('test_'),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				rand(85400, 87400),
				uniqid('test_'),
				rand(85400, 87400)
			);
		}
	}
	
	public function testStartSessionNoSessionType()
	{
		for($i = 0; $i < 50; $i++)
		{
			$this->startSession(
				rand(85400, 87400),
				null,
				uniqid('test_'),
				uniqid('test_'),
				uniqid('test_'),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				rand(85400, 87400),
				uniqid('test_'),
				rand(85400, 87400)
			);
		}
	}
	
	public function testStartSessionNoSessionUserId()
	{
		for($i = 0; $i < 50; $i++)
		{
			$this->startSession(
				rand(85400, 87400),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				null,
				uniqid('test_'),
				uniqid('test_'),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				rand(85400, 87400),
				uniqid('test_'),
				rand(85400, 87400)
			);
		}
	}
	
	public function testStartSessionNoSessionPrivileges()
	{
		for($i = 0; $i < 50; $i++)
		{
			$this->startSession(
				rand(85400, 87400),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				uniqid('test_'),
				null,
				uniqid('test_'),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				rand(85400, 87400),
				uniqid('test_'),
				rand(85400, 87400)
			);
		}
	}
	
	public function testStartSessionNoUserId()
	{
		for($i = 0; $i < 50; $i++)
		{
			$this->startSession(
				rand(85400, 87400),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				uniqid('test_'),
				uniqid('test_'),
				null,
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				rand(85400, 87400),
				uniqid('test_'),
				rand(85400, 87400)
			);
		}
	}
	
	public function testStartSessionNoType()
	{
		for($i = 0; $i < 50; $i++)
		{
			$this->startSession(
				rand(85400, 87400),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				uniqid('test_'),
				uniqid('test_'),
				uniqid('test_'),
				null,
				rand(85400, 87400),
				uniqid('test_'),
				rand(85400, 87400)
			);
		}
	}
	
	public function testStartSessionNoExpiry()
	{
		for($i = 0; $i < 50; $i++)
		{
			$this->startSession(
				rand(85400, 87400),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				uniqid('test_'),
				uniqid('test_'),
				uniqid('test_'),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				null,
				uniqid('test_'),
				rand(85400, 87400)
			);
		}
	}
	
	public function testStartSessionNoPrivileges()
	{
		for($i = 0; $i < 50; $i++)
		{
			$this->startSession(
				rand(85400, 87400),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				uniqid('test_'),
				uniqid('test_'),
				uniqid('test_'),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				rand(85400, 87400),
				uniqid('test_'),
				rand(85400, 87400)
			);
		}
	}
	
	public function testStartSessionNoTokenExpiry()
	{
		for($i = 0; $i < 50; $i++)
		{
			$this->startSession(
				rand(85400, 87400),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				uniqid('test_'),
				uniqid('test_'),
				uniqid('test_'),
				rand(KalturaSessionType::ADMIN, KalturaSessionType::USER),
				rand(85400, 87400),
				uniqid('test_'),
				rand(85400, 87400)
			);
		}
	}
	
	protected function startSession(
		$sessionDuration = null, $sessionType = null, $sessionUserId = null, $sessionPrivileges = null,
		$userId = null, $type = null, $expiry = null, $privileges = null, $tokenExpiry = null
	)
	{
		$appToken = new KalturaAppToken(); 
		$appToken->sessionDuration = $sessionDuration;
		$appToken->sessionType = $sessionType;
		$appToken->sessionUserId = $sessionUserId;
		$appToken->sessionPrivileges = $sessionPrivileges;
		
		$client = new KalturaClient($this->config);
		$time = $client->system->getTime();
		
		if($tokenExpiry)
			$appToken->expiry = $time + $tokenExpiry;
			
		$appToken = $this->add($appToken);
		
		$widgetId = "_{$this->partnerId}";
		$startWidgetSessionResponse = $client->session->startWidgetSession($widgetId);
		$client->setKs($startWidgetSessionResponse->ks);
		
		$id = $appToken->id;
		$tokenHash = sha1($startWidgetSessionResponse->ks . $appToken->token);
		$sessionInfo = $client->appToken->startSession($id, $tokenHash, $userId, $type, $expiry, $privileges);
		$time = $client->system->getTime();
		if(!$expiry)
			$expiry = $appToken->sessionDuration;
		if(!$tokenExpiry)
			$tokenExpiry = $appToken->sessionDuration;
		$expiry = min($appToken->sessionDuration, $expiry, $tokenExpiry);
		$expectedExpiry = $time + $expiry;
		$gap = $expectedExpiry - $sessionInfo->expiry;
		$this->assertLessThan(4, $gap, "Time[$time] Expiry[$expiry] Gap[$gap] expected[$expectedExpiry] actual[{$sessionInfo->expiry}]");
		$this->assertGreaterThan(-2, $gap, "Time[$time] Expiry[$expiry] Gap[$gap] expected[$expectedExpiry] actual[{$sessionInfo->expiry}]");
		$this->assertEquals($this->partnerId, $sessionInfo->partnerId);
		if($sessionType)
			$this->assertEquals($appToken->sessionType, $sessionInfo->sessionType);
		else
			$this->assertEquals($type, $sessionInfo->sessionType);
		if($sessionUserId)
			$this->assertEquals($appToken->sessionUserId, $sessionInfo->userId);
		else
			$this->assertEquals($userId, $sessionInfo->userId);
		
		$finalPrivileges = explode(',', $sessionInfo->privileges);
		if($sessionPrivileges && !in_array($appToken->sessionPrivileges, $finalPrivileges))
			$this->fail("App-Token privilege [$appToken->sessionPrivileges] is missing in the session privileges [$sessionInfo->privileges]");
//		if($privileges && !in_array($privileges, $finalPrivileges))
//			$this->fail("Start-Session privilege [$privileges] is missing in the session privileges [$sessionInfo->privileges]");
	}
	
	public function testInvalidateSession()
	{
		$appToken = new KalturaAppToken();
		$appToken = $this->add($appToken);
		
		$client = new KalturaClient($this->config);
		$widgetId = "_{$this->partnerId}";
		$startWidgetSessionResponse = $client->session->startWidgetSession($widgetId);
		$client->setKs($startWidgetSessionResponse->ks);
		
		$id = $appToken->id;
		$tokenHash = sha1($startWidgetSessionResponse->ks . $appToken->token);
		$sessionInfo = $client->appToken->startSession($id, $tokenHash, '', KalturaSessionType::USER);
		$client->setKs($sessionInfo->ks);
		
		$entry = $this->createEntry();
		$entryId = $entry->id;
		$entry = $client->baseEntry->get($entryId);
		$this->assertEquals($entryId, $entry->id);
		
		$this->delete($id);
		
		try
		{
			$client->baseEntry->get($entryId);
			$this->fail("KS should be invalid");
		}
		catch(KalturaException $e)
		{
			if($e->getCode() != 'INVALID_KS')
				throw $e;
		}
	}
	
	public function testList()
	{
		$appTokens = array(
			$this->create(),
			$this->create(),
			$this->create(),
			$this->create(),
			$this->create(),
			$this->create(),
			$this->create(),
			$this->create(),
			$this->create(),
			$this->create(),
		);
		
		$appTokensIds = array();
		foreach($appTokens as $appToken)
		{
			$appTokensIds[$appToken->id] = $appToken->id;
		}
		
		$filter = new KalturaAppTokenFilter();
		$filter->idIn = implode(',', $appTokensIds);
		
		$client = $this->getAdminClient();
		$appTokensList = $client->appToken->listAction($filter);
		
		$this->assertEquals(count($appTokens), $appTokensList->totalCount);
	}
}

