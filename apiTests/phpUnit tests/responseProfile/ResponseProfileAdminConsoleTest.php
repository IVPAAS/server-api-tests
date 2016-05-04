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
//		return $this->getClient(KalturaSessionType::ADMIN, -2, 'moshe.maor@kaltura.com', 86400, '', 'eb59eef581b03fb2be930a9c705629dd');
		return $this->getClient(KalturaSessionType::ADMIN, 2054, 'Kaltura.testapp1@kaltura.com', 86400, '', '26119aeb9b6258bb6c2ba952024d7b95');
	}
}

