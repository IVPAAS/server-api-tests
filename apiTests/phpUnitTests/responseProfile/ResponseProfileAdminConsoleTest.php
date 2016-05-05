<?php

require_once __DIR__ . '/ResponseProfileServiceTest.php';

/**
 * ResponseProfile service from admin-console test case.
 */
class ResponseProfileAdminConsoleTest extends ResponseProfileServiceTest
{
	protected $createdProfiles = array();
	
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
		foreach($this->createdProfiles as $id)
		{
			$this->delete($id);
		}
		
		parent::tearDown();
	}
	
	protected function getAdminClient()
	{
		return $this->getClient(KalturaSessionType::ADMIN, $this->partnerId , $this->adminEmail , 86400, '', $this->adminSecret);
	}
}

