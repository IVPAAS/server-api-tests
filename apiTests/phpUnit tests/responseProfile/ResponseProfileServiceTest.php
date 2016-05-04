<?php

require_once __DIR__ . '/../KalturaApiTestCase.php';

/**
 * ResponseProfile service test case.
 */
class ResponseProfileServiceTest extends KalturaApiTestCase
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
	
	protected function getAlternateAdminClient()
	{
		return $this->getAlternateClient(KalturaSessionType::ADMIN);
	}
	
	protected function getAdminClient()
	{
		return $this->getClient(KalturaSessionType::ADMIN);
	}
	
	protected function delete($id)
	{
		$client = $this->getAdminClient();
		$client->responseProfile->delete($id);
		
		if(isset($this->createdProfiles[$id]))
			unset($this->createdProfiles[$id]);
	}
	
	protected function create()
	{
		$responseProfile = new KalturaResponseProfile();
		$responseProfile->name = uniqid('test_');
		$responseProfile->systemName = uniqid('test_');
		$responseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		
		return $this->add($responseProfile);
	}
	
	protected function add(KalturaResponseProfile $responseProfile)
	{
		$client = $this->getAdminClient();
		$responseProfile = $client->responseProfile->add($responseProfile);
		
		$this->createdProfiles[$responseProfile->id] = $responseProfile->id;
		
		return $responseProfile;
	}
	
	public function testAdd()
	{
		$this->create();
	}
	
	public function _testAddCrossPartnerSystemName()
	{
		$responseProfile1 = $this->create();
		
		$responseProfile = new KalturaResponseProfile();
		$responseProfile->name = uniqid('test_');
		$responseProfile->systemName = $responseProfile1->systemName;
		$responseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;

		$client = $this->getAdminClient();
		$responseProfile2 = $client->responseProfile->add($responseProfile);
		
		$this->assertNotEquals($responseProfile1->id, $responseProfile2->id);
		$this->assertNotEquals($responseProfile1->partnerId, $responseProfile2->partnerId);
		$this->assertEquals($responseProfile1->systemName, $responseProfile2->systemName);
	}
	
	public function testAddNested()
	{
		$assetFilter = new KalturaFlavorAssetFilter();
		$entryFilter = new KalturaBaseEntryFilter();
		$userFilter = new KalturaUserFilter();
		$userPager = new KalturaFilterPager();
		
		$namedResponseProfile = new KalturaResponseProfile();
		$namedResponseProfile->name = uniqid('test_');
		$namedResponseProfile->systemName = uniqid('test_');
		$namedResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$namedResponseProfile->fields = 'id,name';
		$namedResponseProfile->filter = $assetFilter;
		
		$namedResponseProfile = $this->add($namedResponseProfile);
		
		$nestedNestedResponseProfile = new KalturaDetachedResponseProfile();
		$nestedNestedResponseProfile->name = uniqid('test_');
		$nestedNestedResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$nestedNestedResponseProfile->fields = 'id,name';
		$nestedNestedResponseProfile->filter = $userFilter;
		$nestedNestedResponseProfile->pager = $userPager;
		
		$nestedResponseProfile = new KalturaDetachedResponseProfile();
		$nestedResponseProfile->name = uniqid('test_');
		$nestedResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$nestedResponseProfile->fields = 'id,name';
		$nestedResponseProfile->relatedProfiles = array($nestedNestedResponseProfile);
		$nestedResponseProfile->filter = $entryFilter;
		
		$responseProfile = new KalturaResponseProfile();
		$responseProfile->name = uniqid('test_');
		$responseProfile->systemName = uniqid('test_');
		$responseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
		$responseProfile->fields = 'id,name';
		$responseProfile->relatedProfiles = array($nestedResponseProfile);
		
		$responseProfile = $this->add($responseProfile);
		$this->assertNotNull($responseProfile->id);
		$this->assertEquals(1, count($responseProfile->relatedProfiles));
		foreach($responseProfile->relatedProfiles as $relatedProfile)
		{
			$this->assertNotNull($relatedProfile->name);
			$this->assertEquals(count($nestedResponseProfile->relatedProfiles), count($relatedProfile->relatedProfiles));
		}
	}
	
	public function testUpdate()
	{
		$responseProfile = $this->create();
		$update = new KalturaResponseProfile();
		$update->name = uniqid('test_');
		$update->systemName = uniqid('test_');
		
		$client = $this->getAdminClient();
		$responseProfile = $client->responseProfile->update($responseProfile->id, $update);
		
		$this->assertEquals($update->name, $responseProfile->name);
	}
	
	public function testGet()
	{
		$responseProfile = $this->create();
		$responseProfileId = $responseProfile->id;
		
		$client = $this->getClient();
		$responseProfile = $client->responseProfile->get($responseProfileId);
		
		$this->assertEquals($responseProfileId, $responseProfile->id);
	}
	
	public function testDelete()
	{
		$responseProfile = $this->create();
		$this->delete($responseProfile->id);
	}
	
	public function testList()
	{
		$responseProfiles = array(
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
		
		$responseProfilesIds = array();
		foreach($responseProfiles as $responseProfile)
		{
			$responseProfilesIds[$responseProfile->id] = $responseProfile->id;
		}
		
		$filter = new KalturaResponseProfileFilter();
		$filter->idIn = implode(',', $responseProfilesIds);
		
		$client = $this->getClient();
		$responseProfilesList = $client->responseProfile->listAction($filter);
		
		$this->assertEquals(count($responseProfiles), $responseProfilesList->totalCount);
	}
}

