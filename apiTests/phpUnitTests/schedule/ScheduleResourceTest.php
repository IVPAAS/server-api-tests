<?php

require_once __DIR__ . '/../KalturaApiTestCase.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaScheduleClientPlugin.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaBulkUploadCsvClientPlugin.php';

/**
 * ScheduleResource service test case.
 */
class ScheduleResourceTest extends KalturaApiTestCase
{
	protected $createdScheduleResources = array();
	
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
		foreach($this->createdScheduleResources as $id)
		{
			$this->delete($id);
		}
		
		parent::tearDown();
	}
	
	/* (non-PHPdoc)
	 * @see KalturaApiTestCase::getAdminClient()
	 */
	protected function getAdminClient()
	{
		return $this->getClient(KalturaSessionType::ADMIN, null, uniqid('test'));
	}
	
	protected function delete($id)
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$plugin->scheduleResource->delete($id);
		
		if(isset($this->createdScheduleResources[$id]))
			unset($this->createdScheduleResources[$id]);
	}
	
	/**
	 * @return KalturaCameraScheduleResource
	 */
	protected function createCameraResource($systemName = null)
	{
		$scheduleResource = new KalturaCameraScheduleResource();
		$scheduleResource->name = uniqid('Test: ');
		$scheduleResource->streamUrl = uniqid('test');
		$scheduleResource->systemName = $systemName;
		
		$createdScheduleResource = $this->add($scheduleResource);
		
		$this->assertEquals(KalturaScheduleResourceStatus::ACTIVE, $createdScheduleResource->status);
		
		$this->assertEquals($scheduleResource->name, $createdScheduleResource->name);
		$this->assertEquals($scheduleResource->streamUrl, $createdScheduleResource->streamUrl);
		
		return $createdScheduleResource;
	}
	
	/**
	 * @return KalturaLiveEntryScheduleResource
	 */
	protected function createLiveEntryResource($systemName = null)
	{
		$entry = $this->createLiveStreamEntry();
		$scheduleResource = new KalturaLiveEntryScheduleResource();
		$scheduleResource->name = uniqid('Test: ');
		$scheduleResource->entryId = $entry->id;
		$scheduleResource->systemName = $systemName;

		$createdScheduleResource = $this->add($scheduleResource);

		$this->assertEquals(KalturaScheduleResourceStatus::ACTIVE, $createdScheduleResource->status);

		$this->assertEquals($scheduleResource->name, $createdScheduleResource->name);
		$this->assertEquals($scheduleResource->entryId, $createdScheduleResource->entryId);

		return $createdScheduleResource;
	}
	
	/**
	 * @return KalturaLocationScheduleResource
	 */
	protected function createLocationResource($systemName = null)
	{
		$scheduleResource = new KalturaLocationScheduleResource();
		$scheduleResource->name = uniqid('Test: ');
		$scheduleResource->systemName = $systemName;
		
		$createdScheduleResource = $this->add($scheduleResource);
		
		$this->assertEquals(KalturaScheduleResourceStatus::ACTIVE, $createdScheduleResource->status);
		
		$this->assertEquals($scheduleResource->name, $createdScheduleResource->name);
		
		return $createdScheduleResource;
	}
	
	/**
	 * @param KalturaScheduleResource $scheduleResource
	 * @return KalturaScheduleResource
	 */
	protected function add(KalturaScheduleResource $scheduleResource)
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$createdScheduleResource = $plugin->scheduleResource->add($scheduleResource);
		
		$this->createdScheduleResources[$createdScheduleResource->id] = $createdScheduleResource->id;
		
		$this->assertNotNull($createdScheduleResource->id);
		$this->assertNotNull($createdScheduleResource->partnerId);
		$this->assertNotNull($createdScheduleResource->createdAt);
		$this->assertNotNull($createdScheduleResource->updatedAt);
		$this->assertNotNull($createdScheduleResource->status);
		
		return $createdScheduleResource;
	}
	
	public function testAddCameraResource()
	{
		$scheduleResource = $this->createCameraResource();
	}
	
	public function testAddExistingSystemName()
	{
		$systemName = uniqid();
		$scheduleResource = $this->createCameraResource($systemName);
		$this->assertEquals($systemName, $scheduleResource->systemName);

		$expectedCode = 'SYSTEM_NAME_ALREADY_EXISTS';
		try{
			$scheduleResource = $this->createCameraResource($systemName);
			$this->fail("Expected exception [$expectedCode]");
		} catch (KalturaException $e) {
			$this->assertEquals($expectedCode, $e->getCode());
		}
	}
	
	public function testAddExistingSystemNameOnDifferentType()
	{
		$systemName = uniqid();
		$scheduleResource = $this->createCameraResource($systemName);
		$this->assertEquals($systemName, $scheduleResource->systemName);

		$scheduleResource = $this->createLocationResource($systemName);
		$this->assertEquals($systemName, $scheduleResource->systemName);
	}
	
	public function testUpdateExistingSystemName()
	{
		$systemName = uniqid();
		$scheduleResource = $this->createCameraResource();
		$class = get_class($scheduleResource);
		$update = new $class();
		$update->systemName = $systemName;
		
		$updatedScheduleResource = $this->update($scheduleResource, $update);
		$this->assertEquals($update->systemName, $updatedScheduleResource->systemName);

		$scheduleResource = $this->createCameraResource();
		$class = get_class($scheduleResource);
		$update = new $class();
		$update->systemName = $systemName;

		$expectedCode = 'SYSTEM_NAME_ALREADY_EXISTS';
		try{
			$this->update($scheduleResource, $update);
			$this->fail("Expected exception [$expectedCode]");
		} catch (KalturaException $e) {
			$this->assertEquals($expectedCode, $e->getCode());
		}
	}
	
	public function testUpdateExistingSystemNameOnDifferentType()
	{
		$systemName = uniqid();
		$scheduleResource = $this->createCameraResource();
		$class = get_class($scheduleResource);
		$update = new $class();
		$update->systemName = $systemName;
		
		$updatedScheduleResource = $this->update($scheduleResource, $update);
		$this->assertEquals($update->systemName, $updatedScheduleResource->systemName);

		$scheduleResource = $this->createLocationResource();
		$class = get_class($scheduleResource);
		$update = new $class();
		$update->systemName = $systemName;

		$updatedScheduleResource = $this->update($scheduleResource, $update);
		$this->assertEquals($update->systemName, $updatedScheduleResource->systemName);
	}
	
	public function testAddLiveEntryResource()
	{
		$scheduleResource = $this->createLiveEntryResource();
	}
	
	public function testAddLocationResource()
	{
		$scheduleResource = $this->createLocationResource();
	}
	
	public function testUpdateCameraResource()
	{
		$scheduleResource = $this->createCameraResource();
		$class = get_class($scheduleResource);
		$update = new $class();
		$update->streamUrl = uniqid('test');
		
		$updatedScheduleResource = $this->updateTest($scheduleResource, $update);
		$this->assertEquals($update->streamUrl, $updatedScheduleResource->streamUrl);
	}
	
	public function testUpdateLocationResource()
	{
		$scheduleResource = $this->createLocationResource();
		$class = get_class($scheduleResource);
		$update = new $class();
		
		$updatedScheduleResource = $this->updateTest($scheduleResource, $update);
	}
	
	public function testUpdateLiveEntryResource()
	{
		$entry = $this->createLiveStreamEntry();
		$scheduleResource = $this->createLiveEntryResource();
		$class = get_class($scheduleResource);
		$update = new $class();
		$update->entryId = $entry->id;

		$updatedScheduleResource = $this->updateTest($scheduleResource, $update);
		$this->assertEquals($update->entryId, $updatedScheduleResource->entryId);
	}
	
	private function update(KalturaScheduleResource $scheduleResource, KalturaScheduleResource $update)
	{	
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		return $plugin->scheduleResource->update($scheduleResource->id, $update);
	}
	
	private function updateTest(KalturaScheduleResource $scheduleResource, KalturaScheduleResource $update)
	{
		$update->name = uniqid('Test: ');
		
		$updatedScheduleResource = $this->update($scheduleResource, $update);
		
		$this->assertEquals($update->name, $updatedScheduleResource->name);
		return $updatedScheduleResource;
	}
	
	public function testGetLiveEntryResource()
	{
		$scheduleResource = $this->createLiveEntryResource();
		$getScheduleResource = $this->getTest($scheduleResource);
	}
	
	public function testGetCameraResource()
	{
		$scheduleResource = $this->createCameraResource();
		$getScheduleResource = $this->getTest($scheduleResource);
	}
	
	public function testGetLocationResource()
	{
		$scheduleResource = $this->createLocationResource();
		$getScheduleResource = $this->getTest($scheduleResource);
	}
	
	public function getTest(KalturaScheduleResource $scheduleResource)
	{
		$scheduleResourceId = $scheduleResource->id;
		
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$getScheduleResource = $plugin->scheduleResource->get($scheduleResourceId);
		
		$this->assertEquals(get_class($scheduleResource), get_class($getScheduleResource));
		$this->assertEquals($scheduleResourceId, $getScheduleResource->id);
		
		return $getScheduleResource;
	}
	
	public function testDelete()
	{
		$scheduleResource = $this->createCameraResource();
		$this->delete($scheduleResource->id);
	}
	
	public function testList()
	{
		$scheduleResources = array(
			$this->createCameraResource(),
			$this->createLocationResource(),
			$this->createLiveEntryResource(),
			$this->createCameraResource(),
			$this->createLocationResource(),
			$this->createLiveEntryResource(),
			$this->createCameraResource(),
			$this->createLocationResource(),
			$this->createLiveEntryResource(),
			$this->createCameraResource(),
		);
		
		$scheduleResourcesIds = array();
		foreach($scheduleResources as $scheduleResource)
		{
			$scheduleResourcesIds[$scheduleResource->id] = $scheduleResource->id;
		}
		
		$filter = new KalturaScheduleResourceFilter();
		$filter->idIn = implode(',', $scheduleResourcesIds);
		
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$scheduleResourcesList = $plugin->scheduleResource->listAction($filter);
		
		$this->assertEquals(count($scheduleResources), $scheduleResourcesList->totalCount);
	}
	
	public function testListLocations()
	{
		$locations = 4;
		$cameras = 4;
		
		$scheduleResources = array();
		
		for($i = 0; $i < $locations; $i++)
			$scheduleResources[] = $this->createLocationResource();
			
		for($i = 0; $i < $cameras; $i++)
			$scheduleResources[] = $this->createCameraResource();
		
		$scheduleResourcesIds = array();
		foreach($scheduleResources as $scheduleResource)
		{
			$scheduleResourcesIds[$scheduleResource->id] = $scheduleResource->id;
		}
		
		$filter = new KalturaLocationScheduleResourceFilter();
		$filter->idIn = implode(',', $scheduleResourcesIds);
		
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$scheduleResourcesList = $plugin->scheduleResource->listAction($filter);
		
		$this->assertEquals($locations, $scheduleResourcesList->totalCount);
		foreach($scheduleResourcesList->objects as $scheduleResources){
			$this->assertEquals('KalturaLocationScheduleResource', get_class($scheduleResources));
		}
	}

	protected function addBulkUpload($columns, $input, array $itemsThatExpectedToFail = array())
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$data = array();
		foreach($input as $row)
		{
			$data[] = array_combine($columns, $row);
		}
		$rows = array(
				'*' . implode(",", $columns)
		);
		foreach($data as $item)
		{
			$rows[] = implode(",", $item);
		}
		$content = implode("\n", $rows) . "\n";

		$filename = tempnam(sys_get_temp_dir(), 'bulk.') . '.csv';
		file_put_contents($filename, $content);

		$bulkUploadData = new KalturaBulkUploadCsvJobData();

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$bulkUpload = $plugin->scheduleResource->addFromBulkUpload($filename, $bulkUploadData);
		$logUrl = $client->bulkUpload->serveLog($bulkUpload->id);

		$output = array();
		while(!count($output))
		{
			echo "Fetching log.\n";
			$log = file_get_contents($logUrl);
			if($log === 'Log file is not ready')
			{
				sleep(2);
				continue;
			}

			$columns[] = 'resultStatus';
			$columns[] = 'objectId';
			$columns[] = 'objectStatus';
			$columns[] = 'errorDescription';

			$rows = str_getcsv(str_replace("\r", '', $log), "\n");
			foreach($rows as $row)
			{
				$output[] = array_combine($columns, str_getcsv($row));
			}
		}
		var_dump($output);

		foreach($output as $index => $rowOutput)
		{
			$rowInput = $data[$index];

			if(in_array($index, $itemsThatExpectedToFail))
			{
				$this->assertEquals(0, strlen($rowOutput['objectId']));
				$this->assertEquals(KalturaBulkUploadResultStatus::ERROR, $rowOutput['resultStatus']);
				$this->assertGreaterThan(0, strlen($rowOutput['errorDescription']));
				continue;
			}

			$this->assertGreaterThan(0, strlen($rowOutput['objectId']));
			if(isset($rowInput['action']) && $rowInput['action'] == KalturaBulkUploadAction::DELETE)
			{
				try{
					$plugin->scheduleResource->get($rowOutput['objectId']);
					$this->fail('Exception INVALID_OBJECT_ID expected');
				}
				catch(Exception $e) {
					$this->assertEquals('INVALID_OBJECT_ID', $e->getCode());
				}
				continue;
			}
			$scheduleResource = $plugin->scheduleResource->get($rowOutput['objectId']);

			$this->createdScheduleResources[$scheduleResource->id] = $scheduleResource->id;

			$this->assertEquals($this->partnerId, $scheduleResource->partnerId);
			$this->assertEquals(KalturaScheduleResourceStatus::ACTIVE, $scheduleResource->status);

			$this->assertTrue($scheduleResource instanceof KalturaScheduleResource);
			if(isset($rowInput['type']))
			{
				switch($rowInput['type'])
				{
					case 'camera':
						$this->assertTrue($scheduleResource instanceof KalturaCameraScheduleResource);
						break;

					case 'location':
						$this->assertTrue($scheduleResource instanceof KalturaLocationScheduleResource);
						break;
				}
			}
			else
			{
				$this->assertTrue($scheduleResource instanceof KalturaLocationScheduleResource);
			}

			if(isset($rowInput['name']))
				$this->assertEquals($rowInput['name'], $scheduleResource->name);

			if(isset($rowInput['systemName']) && $rowInput['systemName'])
				$this->assertEquals($rowInput['systemName'], $scheduleResource->systemName);

			if(isset($rowInput['description']) && $rowInput['description'])
				$this->assertEquals($rowInput['description'], $scheduleResource->description);

			if(isset($rowInput['tags']) && $rowInput['tags'])
				$this->assertEquals(trim($rowInput['tags'], '"'), $scheduleResource->tags);

			$this->assertEquals($scheduleResource->status, $rowOutput['objectStatus']);
			$this->assertNotEquals(KalturaBulkUploadResultStatus::ERROR, $rowOutput['resultStatus']);

			if(isset($rowInput['parentSystemName']) && $rowInput['parentSystemName'])
			{
				$this->assertNotNull($scheduleResource->parentId);
				$parentScheduleResource = $plugin->scheduleResource->get($scheduleResource->parentId);
				$this->assertEquals($rowInput['parentSystemName'], $parentScheduleResource->systemName);

				if(isset($rowInput['parentType']) && $rowInput['parentType'])
				{
					switch($rowInput['parentType'])
					{
						case 'camera':
							$this->assertTrue($parentScheduleResource instanceof KalturaCameraScheduleResource);
							break;

						case 'location':
							$this->assertTrue($parentScheduleResource instanceof KalturaLocationScheduleResource);
							break;
					}
				}
				else
				{
					$this->assertTrue($parentScheduleResource instanceof KalturaLocationScheduleResource);
				}
			}
		}

		return $output;
	}

	public function testBulkUploadWrongFields()
	{
		$columns = array(
			'name',
			'systemName',
			'invalidField',	
		);
		
		$parentSystemName = uniqid();
		
		$data = array(
			array(uniqid(), uniqid(), uniqid()),
			array(uniqid(), uniqid(), uniqid()),
		);
		
		$csv = $this->addBulkUpload($columns, $data);
	}

	public function testBulkUploadMissingName()
	{
		$columns = array(
			'systemName',
		);
		
		$parentSystemName = uniqid();
		
		$data = array(
			array(uniqid()),
			array(uniqid()),
		);
		
		$csv = $this->addBulkUpload($columns, $data, array(0, 1));
	}

	public function testBulkUploadDeleteWithId()
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		
		$client->startMultiRequest();
		
		$scheduleResource1 = new KalturaLocationScheduleResource();
		$scheduleResource1->name = uniqid();
		$scheduleResource1->systemName = uniqid();
		$plugin->scheduleResource->add($scheduleResource1);

		$scheduleResource2 = new KalturaLocationScheduleResource();
		$scheduleResource2->name = uniqid();
		$scheduleResource2->systemName = uniqid();
		$plugin->scheduleResource->add($scheduleResource2);

		$scheduleResource3 = new KalturaCameraScheduleResource();
		$scheduleResource3->name = uniqid();
		$scheduleResource3->systemName = uniqid();
		$plugin->scheduleResource->add($scheduleResource3);
		
		$scheduleResources = $client->doMultiRequest();
		
		$columns = array(
			'action',
			'resourceId',
		);

		$data = array(
			array(KalturaBulkUploadAction::DELETE, $scheduleResources[0]->id),
			array(KalturaBulkUploadAction::DELETE, $scheduleResources[1]->id),
			array(KalturaBulkUploadAction::DELETE, $scheduleResources[2]->id),
		);
		
		$csv = $this->addBulkUpload($columns, $data);
	}

	public function testBulkUploadDeleteWithSystemName()
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		
		$parentSystemName1 = uniqid();
		$parentSystemName2 = uniqid();
		$parentSystemName3 = uniqid();

		$client->startMultiRequest();
		
		$scheduleResource1 = new KalturaLocationScheduleResource();
		$scheduleResource1->name = uniqid();
		$scheduleResource1->systemName = $parentSystemName1;
		$plugin->scheduleResource->add($scheduleResource1);

		$scheduleResource2 = new KalturaLocationScheduleResource();
		$scheduleResource2->name = uniqid();
		$scheduleResource2->systemName = $parentSystemName2;
		$plugin->scheduleResource->add($scheduleResource2);

		$scheduleResource3 = new KalturaCameraScheduleResource();
		$scheduleResource3->name = uniqid();
		$scheduleResource3->systemName = $parentSystemName3;
		$plugin->scheduleResource->add($scheduleResource3);
		
		$scheduleResources = $client->doMultiRequest();
		
		$columns = array(
			'action',
			'type',
			'systemName',
		);

		$data = array(
			array(KalturaBulkUploadAction::DELETE, 'location', $parentSystemName1),
			array(KalturaBulkUploadAction::DELETE, 'location', $parentSystemName2),
			array(KalturaBulkUploadAction::DELETE, 'camera', $parentSystemName3),
		);
		
		$csv = $this->addBulkUpload($columns, $data);
	}

	public function testBulkUploadUpdateWithId()
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		
		$client->startMultiRequest();
		
		$scheduleResource1 = new KalturaLocationScheduleResource();
		$scheduleResource1->name = uniqid();
		$scheduleResource1->systemName = uniqid();
		$plugin->scheduleResource->add($scheduleResource1);

		$scheduleResource2 = new KalturaLocationScheduleResource();
		$scheduleResource2->name = uniqid();
		$scheduleResource2->systemName = uniqid();
		$plugin->scheduleResource->add($scheduleResource2);

		$scheduleResource3 = new KalturaCameraScheduleResource();
		$scheduleResource3->name = uniqid();
		$scheduleResource3->systemName = uniqid();
		$plugin->scheduleResource->add($scheduleResource3);
		
		$scheduleResources = $client->doMultiRequest();
		
		$columns = array(
			'action',
			'name',
			'type',
			'resourceId',
			'tags',
			'parentSystemName',	
		);

		$data = array(
			array(KalturaBulkUploadAction::UPDATE, uniqid(), 'location',	$scheduleResources[0]->id,	"\"{$this->uniqueTag},tag1\"", ''),
			array(KalturaBulkUploadAction::UPDATE, uniqid(), 'location',	$scheduleResources[1]->id, "\"{$this->uniqueTag},tag2\"", $scheduleResources[0]->systemName),
			array(KalturaBulkUploadAction::UPDATE, uniqid(), 'camera',	$scheduleResources[2]->id, "\"{$this->uniqueTag},tag3\"", $scheduleResources[1]->systemName),
		);
		
		$csv = $this->addBulkUpload($columns, $data);

		$client->startMultiRequest();
		foreach($scheduleResources as $index => $scheduleResource)
		{
			$this->assertEquals($scheduleResource->id, $csv[$index]['objectId']);

			$plugin->scheduleResource->get($scheduleResource->id);
		}
		$scheduleResources = $client->doMultiRequest();
		
		$this->assertEquals($scheduleResources[0]->id, $scheduleResources[1]->parentId);
		$this->assertEquals($scheduleResources[1]->id, $scheduleResources[2]->parentId);
	}

	public function testBulkUploadUpdateWithSystemName()
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		
		$parentSystemName1 = uniqid();
		$parentSystemName2 = uniqid();
		$parentSystemName3 = uniqid();

		$client->startMultiRequest();
		
		$scheduleResource1 = new KalturaLocationScheduleResource();
		$scheduleResource1->name = uniqid();
		$scheduleResource1->systemName = $parentSystemName1;
		$plugin->scheduleResource->add($scheduleResource1);

		$scheduleResource2 = new KalturaLocationScheduleResource();
		$scheduleResource2->name = uniqid();
		$scheduleResource2->systemName = $parentSystemName2;
		$plugin->scheduleResource->add($scheduleResource2);

		$scheduleResource3 = new KalturaCameraScheduleResource();
		$scheduleResource3->name = uniqid();
		$scheduleResource3->systemName = $parentSystemName3;
		$plugin->scheduleResource->add($scheduleResource3);
		
		$scheduleResources = $client->doMultiRequest();
		
		$columns = array(
			'action',
			'name',
			'type',
			'systemName',
			'tags',
			'parentSystemName',	
		);

		$data = array(
			array(KalturaBulkUploadAction::UPDATE, uniqid(), 'location',	$parentSystemName1,	"\"{$this->uniqueTag},tag1\"", ''),
			array(KalturaBulkUploadAction::UPDATE, uniqid(), 'location',	$parentSystemName2, "\"{$this->uniqueTag},tag2\"", $parentSystemName1),
			array(KalturaBulkUploadAction::UPDATE, uniqid(), 'camera',	$parentSystemName3, "\"{$this->uniqueTag},tag3\"", $parentSystemName2),
		);
		
		$csv = $this->addBulkUpload($columns, $data);

		$client->startMultiRequest();
		foreach($scheduleResources as $index => $scheduleResource)
		{
			$this->assertEquals($scheduleResource->id, $csv[$index]['objectId']);
			$this->assertEquals($scheduleResource->systemName, $csv[$index]['systemName']);

			$plugin->scheduleResource->get($scheduleResource->id);
		}
		$scheduleResources = $client->doMultiRequest();

		$this->assertEquals($scheduleResources[0]->id, $scheduleResources[1]->parentId);
		$this->assertEquals($scheduleResources[1]->id, $scheduleResources[2]->parentId);
	}

	public function testBulkUploadAddOrUpdateWithId()
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		
		$client->startMultiRequest();
		
		$scheduleResource1 = new KalturaLocationScheduleResource();
		$scheduleResource1->name = uniqid();
		$scheduleResource1->systemName = uniqid();
		$plugin->scheduleResource->add($scheduleResource1);

		$scheduleResource2 = new KalturaLocationScheduleResource();
		$scheduleResource2->name = uniqid();
		$scheduleResource2->systemName = uniqid();
		$plugin->scheduleResource->add($scheduleResource2);

		$scheduleResource3 = new KalturaCameraScheduleResource();
		$scheduleResource3->name = uniqid();
		$scheduleResource3->systemName = uniqid();
		$plugin->scheduleResource->add($scheduleResource3);
		
		$scheduleResources = $client->doMultiRequest();
		
		$columns = array(
			'action',
			'name',
			'type',
			'resourceId',
			'tags',
			'parentSystemName',	
		);

		$data = array(
			array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'location',	$scheduleResources[0]->id,	"\"{$this->uniqueTag},tag1\"", ''),
			array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'location',	$scheduleResources[1]->id, "\"{$this->uniqueTag},tag2\"", $scheduleResources[0]->systemName),
			array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'camera',	$scheduleResources[2]->id, "\"{$this->uniqueTag},tag3\"", $scheduleResources[1]->systemName),
		);
		
		$csv = $this->addBulkUpload($columns, $data);

		$client->startMultiRequest();
		foreach($scheduleResources as $index => $scheduleResource)
		{
			$this->assertEquals($scheduleResource->id, $csv[$index]['objectId']);

			$plugin->scheduleResource->get($scheduleResource->id);
		}
		$scheduleResources = $client->doMultiRequest();
		
		$this->assertEquals($scheduleResources[0]->id, $scheduleResources[1]->parentId);
		$this->assertEquals($scheduleResources[1]->id, $scheduleResources[2]->parentId);
	}

	public function _testBulkUploadAddOrUpdateWithSystemName()
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		
		$parentSystemName1 = uniqid();
		$parentSystemName2 = uniqid();
		$parentSystemName3 = uniqid();

		$client->startMultiRequest();
		
		$scheduleResource1 = new KalturaLocationScheduleResource();
		$scheduleResource1->name = uniqid();
		$scheduleResource1->systemName = $parentSystemName1;
		$plugin->scheduleResource->add($scheduleResource1);

		$scheduleResource2 = new KalturaLocationScheduleResource();
		$scheduleResource2->name = uniqid();
		$scheduleResource2->systemName = $parentSystemName2;
		$plugin->scheduleResource->add($scheduleResource2);

		$scheduleResource3 = new KalturaCameraScheduleResource();
		$scheduleResource3->name = uniqid();
		$scheduleResource3->systemName = $parentSystemName3;
		$plugin->scheduleResource->add($scheduleResource3);
		
		$scheduleResources = $client->doMultiRequest();
		
		$columns = array(
			'action',
			'name',
			'type',
			'systemName',
			'tags',
			'parentSystemName',	
		);

		$data = array(
			array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'location',	$parentSystemName1,	"\"{$this->uniqueTag},tag1\"", ''),
			array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'location',	$parentSystemName2, "\"{$this->uniqueTag},tag2\"", $parentSystemName1),
			array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'camera',	$parentSystemName3, "\"{$this->uniqueTag},tag3\"", $parentSystemName2),
		);
		
		$csv = $this->addBulkUpload($columns, $data);

		$client->startMultiRequest();
		foreach($scheduleResources as $index => $scheduleResource)
		{
			$this->assertEquals($scheduleResource->id, $csv[$index]['objectId']);
			$this->assertEquals($scheduleResource->systemName, $csv[$index]['systemName']);

			$plugin->scheduleResource->get($scheduleResource->id);
		}
		$scheduleResources = $client->doMultiRequest();

		$this->assertEquals($scheduleResources[0]->id, $scheduleResources[1]->parentId);
		$this->assertEquals($scheduleResources[1]->id, $scheduleResources[2]->parentId);
	}

	public function testBulkUploadWithParent()
	{
		$columns = array(
			'action',
			'name',
			'type',
			'systemName',
			'tags',
			'parentSystemName',	
		);

		$parentSystemName1 = uniqid();
		$parentSystemName2 = uniqid();
		
		$data = array(
			array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'location',	$parentSystemName1,	"\"{$this->uniqueTag},tag1\"", ''),
			array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'location',	$parentSystemName2, "\"{$this->uniqueTag},tag2\"", $parentSystemName1),
			array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'camera',	'', 				"\"{$this->uniqueTag},tag3\"", $parentSystemName2),
			array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'camera',	'', 				"\"{$this->uniqueTag},tag4\"", uniqid()),
		);
		
		$csv = $this->addBulkUpload($columns, $data, array(3));

		$data = array(
				array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'location',	$parentSystemName2, "\"{$this->uniqueTag},tag5,updating\"", $parentSystemName1),
				array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'camera',	'', 				"\"{$this->uniqueTag},tag6,creating\"", $parentSystemName2),
				array(KalturaBulkUploadAction::ADD_OR_UPDATE, uniqid(), 'camera',	'', 				"\"{$this->uniqueTag},tag7,creating\"", $parentSystemName2),
		);
		
		$csv = $this->addBulkUpload($columns, $data);
	}

	public function testBulkUploadDefaultTypes()
	{
		$columns = array(
			'name',
			'systemName',
			'tags',
			'parentSystemName',	
		);
		
		$parentSystemName = uniqid();
		
		$data = array(
			array(uniqid(), $parentSystemName,	"\"{$this->uniqueTag},tag1\"", ''),
			array(uniqid(), '', 				"\"{$this->uniqueTag},tag2\"", $parentSystemName),
		);
		
		$csv = $this->addBulkUpload($columns, $data);
	}

	public function testBulkUploadDuplicateSystemNames()
	{
		$columns = array(
			'name',
			'type',
			'systemName',
			'description',
				
			'tags',
		);
		
		$systemName = uniqid();
		
		$data = array(
			array(uniqid(), 'location', $systemName, 'test system-name 1', "\"{$this->uniqueTag},tag1\""),
			array(uniqid(), 'camera', $systemName, 'test system-name 2', "\"{$this->uniqueTag},tag2\""),
			array(uniqid(), 'camera', $systemName, 'test system-name 3', "\"{$this->uniqueTag},tag3\""),
			array(uniqid(), 'location', $systemName, 'test system-name 4', "\"{$this->uniqueTag},tag4\""),
		);
		
		$csv = $this->addBulkUpload($columns, $data, array(2, 3));
	}

	public function testBulkUploadAllTypes()
	{
		$columns = array(
			'name',
			'type',
			'systemName',
			'description',
			'tags',
		);
		
		$data = array(
			array(uniqid(), 'location', uniqid(), 'test type 1', "\"{$this->uniqueTag},tag1\""),
			array(uniqid(), 'camera', uniqid(), 'test type 2', "\"{$this->uniqueTag},tag2\""),
		);
	
		$csv = $this->addBulkUpload($columns, $data);
	}
}

