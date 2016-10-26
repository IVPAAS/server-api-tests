<?php

require_once __DIR__ . '/../KalturaApiTestCase.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaMetadataClientPlugin.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaScheduleClientPlugin.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaScheduleBulkUploadClientPlugin.php';
require_once '/opt/kaltura/web/content/clientlibs/testsClient/KalturaPlugins/KalturaDropFolderClientPlugin.php';
require_once '/opt/kaltura/app/plugins/schedule/base/lib/iCal/kSchedulingICal.php';
require_once '/opt/kaltura/app/plugins/schedule/base/lib/iCal/kSchedulingICalComponent.php';
require_once '/opt/kaltura/app/plugins/schedule/base/lib/iCal/kSchedulingICalCalendar.php';
require_once '/opt/kaltura/app/plugins/schedule/base/lib/iCal/kSchedulingICalEvent.php';
require_once '/opt/kaltura/app/plugins/schedule/base/lib/iCal/kSchedulingICalRule.php';
require_once '/opt/kaltura/app/infra/general/BaseEnum.php';
require_once '/opt/kaltura/app/infra/storage/StorageProfileProtocol.php';
require_once '/opt/kaltura/app/infra/storage/kFileTransferMgr.class.php';
require_once '/opt/kaltura/app/infra/storage/file_transfer_managers/sftpMgr.class.php';

/**
 * ScheduleEvent service test case.
 */
class ScheduleEventTest extends KalturaApiTestCase
{
	protected $createdScheduleEvents = array();
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
		foreach($this->createdScheduleEvents as $id)
		{
			try
			{
				$this->delete($id);
			}
			catch(Exception $e)
			{
				echo "error occured while deleting [$id]".PHP_EOL;
			}
		}
		
		foreach($this->createdScheduleResources as $id)
		{
			$this->deleteResource($id);
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
		$plugin->scheduleEvent->delete($id);

		if(isset($this->createdScheduleEvents[$id]))
			unset($this->createdScheduleEvents[$id]);
	}
	
	/**
	 * @return KalturaScheduleEvent
	 */
	protected function create($class = 'KalturaLiveStreamScheduleEvent', $additionalAttributes = array(), $duration = null)
	{
		$scheduleEvent = new $class();
		/* @var $scheduleEvent KalturaScheduleEvent */
		
		$scheduleEvent->recurrenceType = KalturaScheduleEventRecurrenceType::NONE;
		$scheduleEvent->summary = uniqid('Test: ');
		$scheduleEvent->startDate = time() + (60 * 60 * 24);
		$scheduleEvent->endDate = $scheduleEvent->startDate + 60;
		$scheduleEvent->referenceId = uniqid();

		foreach($additionalAttributes as $attribute => $value)
		{
			$scheduleEvent->$attribute = $value;
		}
		
		$createdScheduleEvent = $this->add($scheduleEvent);
		
		$this->assertEquals(KalturaScheduleEventStatus::ACTIVE, $createdScheduleEvent->status);
		$this->assertEquals(KalturaScheduleEventClassificationType::PUBLIC_EVENT, $createdScheduleEvent->classificationType);

		$this->assertEquals($scheduleEvent->recurrenceType, $createdScheduleEvent->recurrenceType);
		$this->assertEquals($scheduleEvent->summary, $createdScheduleEvent->summary);

		if($scheduleEvent->recurrenceType == KalturaScheduleEventRecurrenceType::NONE)
		{
			$this->assertEquals($scheduleEvent->endDate, $createdScheduleEvent->startDate + $createdScheduleEvent->duration);
			$this->assertNotNull($createdScheduleEvent->duration);
		}

		$this->assertEquals($class, get_class($createdScheduleEvent));
		
		return $createdScheduleEvent;
	}
	
	/**
	 * @param KalturaScheduleEvent $scheduleEvent
	 * @return KalturaScheduleEvent
	 */
	protected function add(KalturaScheduleEvent $scheduleEvent)
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$createdScheduleEvent = $plugin->scheduleEvent->add($scheduleEvent);
		
		$this->createdScheduleEvents[$createdScheduleEvent->id] = $createdScheduleEvent->id;
		
		$this->assertNotNull($createdScheduleEvent->id);
		$this->assertNotNull($createdScheduleEvent->partnerId);
		$this->assertNotNull($createdScheduleEvent->createdAt);
		$this->assertNotNull($createdScheduleEvent->updatedAt);
		$this->assertNotNull($createdScheduleEvent->classificationType);
		$this->assertNotNull($createdScheduleEvent->ownerId);
		$this->assertNotNull($createdScheduleEvent->recurrenceType);
		$this->assertNotNull($createdScheduleEvent->sequence);
		$this->assertNotNull($createdScheduleEvent->startDate);
		$this->assertNotNull($createdScheduleEvent->status);
		
		return $createdScheduleEvent;
	}
	
	/**
	 * @param KalturaScheduleResource $scheduleResource
	 * @return KalturaScheduleResource
	 */
	protected function addResource(KalturaScheduleResource $scheduleResource)
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$createdScheduleResource = $plugin->scheduleResource->add($scheduleResource);
		
		$this->createdScheduleResources[$createdScheduleResource->id] = $createdScheduleResource->id;
		
		return $createdScheduleResource;
	}
	
	/**
	 * @param int $eventId
	 * @param int $resourceId
	 * @return KalturaScheduleEventResource
	 */
	protected function addEventResource($eventId, $resourceId)
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$eventResource = new KalturaScheduleEventResource();
		$eventResource->eventId = $eventId;
		$eventResource->resourceId = $resourceId;
		
		return $plugin->scheduleEventResource->add($eventResource);
	}
	
	protected function deleteResource($id)
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
	protected function createCameraResource($systemName = null, $parentId = null)
	{
		$scheduleResource = new KalturaCameraScheduleResource();
		$scheduleResource->name = uniqid('Test: ');
		$scheduleResource->streamUrl = uniqid('test');
		$scheduleResource->systemName = $systemName;
		$scheduleResource->parentId = $parentId;
		
		$createdScheduleResource = $this->addResource($scheduleResource);
		
		return $createdScheduleResource;
	}
	
	/**
	 * @return KalturaLocationScheduleResource
	 */
	protected function createLocationResource($systemName = null, $parentId = null)
	{
		$scheduleResource = new KalturaLocationScheduleResource();
		$scheduleResource->name = uniqid('Test: ');
		$scheduleResource->systemName = $systemName;
		$scheduleResource->parentId = $parentId;
		
		$createdScheduleResource = $this->addResource($scheduleResource);
		
		return $createdScheduleResource;
	}
	
	public function testAddLiveStreamEvent()
	{
		$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent');
	}

	public function testAddRecordingEvent()
	{
		$scheduleEvent = $this->create('KalturaRecordScheduleEvent');
	}

	public function testAddEventWithTemplateEntry()
	{
		$entry = $this->createEntry();
		$scheduleEvent = $this->create('KalturaRecordScheduleEvent', array('templateEntryId' => $entry->id));
		/* @var $scheduleEvent KalturaRecordScheduleEvent */

		$this->assertEquals($entry->id, $scheduleEvent->templateEntryId);
	}

	public function testAddTooLong()
	{
		$scheduleEvent = new KalturaLiveStreamScheduleEvent();
		$scheduleEvent->recurrenceType = KalturaScheduleEventRecurrenceType::NONE;
		$scheduleEvent->summary = uniqid('Test: ');
		$scheduleEvent->startDate = time() + (60 * 60 * 24);
		$scheduleEvent->endDate = $scheduleEvent->startDate + (60 * 60 * 24 * 365 * 2) + 1;

		try
		{
			$createdScheduleEvent = $this->add($scheduleEvent);
			$this->fail("Exception [MAX_SCHEDULE_DURATION_REACHED] expected");
		}
		catch(KalturaException $e)
		{
			$this->assertEquals('MAX_SCHEDULE_DURATION_REACHED', $e->getCode());
		}
	}

	public function testAddSwitchedTimes()
	{
		$scheduleEvent = new KalturaLiveStreamScheduleEvent();
		$scheduleEvent->recurrenceType = KalturaScheduleEventRecurrenceType::NONE;
		$scheduleEvent->summary = uniqid('Test: ');
		$scheduleEvent->startDate = time() + (60 * 60 * 24);
		$scheduleEvent->endDate = $scheduleEvent->startDate - 60;

		try
		{
			$createdScheduleEvent = $this->add($scheduleEvent);
			$this->fail("Exception [INVALID_SCHEDULE_END_BEFORE_START] expected");
		}
		catch(KalturaException $e)
		{
			$this->assertEquals('INVALID_SCHEDULE_END_BEFORE_START', $e->getCode());
		}
	}

	public function testAddRecurrence()
	{
		$scheduleEvent = new KalturaLiveStreamScheduleEvent();
		$scheduleEvent->recurrenceType = KalturaScheduleEventRecurrenceType::RECURRENCE;
		$scheduleEvent->summary = uniqid('Test: ');
		$scheduleEvent->startDate = time() + (60 * 60 * 24);
		$scheduleEvent->endDate = $scheduleEvent->startDate + 60;

		try
		{
			$createdScheduleEvent = $this->add($scheduleEvent);
			$this->fail("Exception [INVALID_ENUM_VALUE] expected");
		}
		catch(KalturaException $e)
		{
			$this->assertEquals('INVALID_ENUM_VALUE', $e->getCode());
		}
	}

	public function testAddRecurringWeekly()
	{
		$recurrence = new KalturaScheduleEventRecurrence();
		$recurrence->frequency = KalturaScheduleEventRecurrenceFrequency::WEEKLY;
		$recurrence->interval = 1;
		$recurrence->byDay = KalturaScheduleEventRecurrenceDay::MONDAY . ',' . KalturaScheduleEventRecurrenceDay::WEDNESDAY;
		$recurrence->byHour = 16;
		$recurrence->byMinute = 0;
		$recurrence->bySecond = 0;

		$maxExpectedResults = 54 * 4;
		$minExpectedResults = 52 * 4;

		list($createdScheduleEvent, $recurrences) = $this->addRecurringTest($recurrence, $maxExpectedResults, $minExpectedResults);
		foreach($recurrences as $recurrence)
		{
			/* @var $recurrence KalturaLiveStreamScheduleEvent */
			$date = getdate($recurrence->startDate);
			$this->assertArrayHasKey($date['wday'], array(1 => true, 3 => true));
			$this->assertArrayHasKey($date['hours'], array(15 => true, 16 => true, 17 => true));
			$this->assertEquals(0, $date['minutes']);
			$this->assertEquals(0, $date['seconds']);

			$this->assertEquals($createdScheduleEvent->summary, $recurrence->summary);
			$this->assertEquals($createdScheduleEvent->duration, $recurrence->duration);
		}
	}

	public function testAddRecurringDaily()
	{
		$recurrence = new KalturaScheduleEventRecurrence();
		$recurrence->frequency = KalturaScheduleEventRecurrenceFrequency::DAILY;
		$recurrence->interval = 1;
		$recurrence->byHour = 10;
		$recurrence->byMinute = 0;
		$recurrence->bySecond = 0;

		$maxExpectedResults = 365 * 2;
		$minExpectedResults = 364 * 2;

		list($createdScheduleEvent, $recurrences) = $this->addRecurringTest($recurrence, $maxExpectedResults, $minExpectedResults);
		foreach($recurrences as $recurrence)
		{
			/* @var $recurrence KalturaLiveStreamScheduleEvent */
			$date = getdate($recurrence->startDate);
			$this->assertArrayHasKey($date['hours'], array(9 => true, 10 => true, 11 => true));
			$this->assertEquals(0, $date['minutes']);
			$this->assertEquals(0, $date['seconds']);

			$this->assertEquals($createdScheduleEvent->summary, $recurrence->summary);
			$this->assertEquals($createdScheduleEvent->duration, $recurrence->duration);
		}
	}

	public function testAddRecurringDailyLimited()
	{
		$recurrence = new KalturaScheduleEventRecurrence();
		$recurrence->frequency = KalturaScheduleEventRecurrenceFrequency::DAILY;
		$recurrence->interval = 1;
		$recurrence->byHour = 10;
		$recurrence->byMinute = 0;
		$recurrence->bySecond = 0;
		$recurrence->count = 88;

		$maxExpectedResults = $recurrence->count;
		$minExpectedResults = $recurrence->count;

		list($createdScheduleEvent, $recurrences) = $this->addRecurringTest($recurrence, $maxExpectedResults, $minExpectedResults);
		foreach($recurrences as $recurrence)
		{
			/* @var $recurrence KalturaLiveStreamScheduleEvent */
			$date = getdate($recurrence->startDate);
			$this->assertArrayHasKey($date['hours'], array(9 => true, 10 => true, 11 => true));
			$this->assertEquals(0, $date['minutes']);
			$this->assertEquals(0, $date['seconds']);

			$this->assertEquals($createdScheduleEvent->summary, $recurrence->summary);
			$this->assertEquals($createdScheduleEvent->duration, $recurrence->duration);
		}

		return array($createdScheduleEvent, $recurrences);
	}

	public function testCancelRecurring()
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		list($createdScheduleEvent, $recurrences) = $this->testAddRecurringDailyLimited();
		foreach($recurrences as $recurrence)
		{
			/* @var $recurrence KalturaLiveStreamScheduleEvent */
			$canceled = $plugin->scheduleEvent->cancel($recurrence->id);

			$this->assertEquals($recurrence->id, $canceled->id);
			$this->assertEquals(KalturaScheduleEventStatus::CANCELLED, $canceled->status);
		}


		$filter = new KalturaScheduleEventFilter();
		$filter->parentIdEqual = $createdScheduleEvent->id;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = 500;

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals(count($recurrences), $scheduleEventsList->totalCount);
		foreach($scheduleEventsList->objects as $recurrence)
		{
			/* @var $recurrence KalturaLiveStreamScheduleEvent */
			$this->assertEquals(KalturaScheduleEventStatus::CANCELLED, $canceled->status);
		}
	}

	public function addRecurringTest($recurrence, $maxExpectedResults, $minExpectedResults)
	{
		$scheduleEvent = new KalturaLiveStreamScheduleEvent();
		$scheduleEvent->recurrenceType = KalturaScheduleEventRecurrenceType::RECURRING;
		$scheduleEvent->summary = uniqid('Test: ');
		$scheduleEvent->startDate = time() + (60 * 60 * 24);
		$scheduleEvent->endDate = $scheduleEvent->startDate + (60 * 60 * 24 * 365 * 2);
		$scheduleEvent->duration = 60;
		$scheduleEvent->recurrence = $recurrence;

		$createdScheduleEvent = $this->add($scheduleEvent);

		$filter = new KalturaScheduleEventFilter();
		$filter->parentIdEqual = $createdScheduleEvent->id;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = 500;

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);

		$this->assertGreaterThanOrEqual($minExpectedResults, $scheduleEventsList->totalCount);
		$this->assertLessThanOrEqual($maxExpectedResults, $scheduleEventsList->totalCount);
		foreach($scheduleEventsList->objects as $scheduleEventRecurrence)
		{
			/* @var $scheduleEventRecurrence KalturaLiveStreamScheduleEvent */
			$this->assertEquals('KalturaLiveStreamScheduleEvent', get_class($scheduleEventRecurrence));
			$this->assertEquals(KalturaScheduleEventRecurrenceType::RECURRENCE, $scheduleEventRecurrence->recurrenceType);
			$this->assertGreaterThanOrEqual($scheduleEvent->startDate, $scheduleEventRecurrence->startDate);
			$this->assertLessThanOrEqual($scheduleEvent->endDate, $scheduleEventRecurrence->startDate);
		}

		return array($createdScheduleEvent, $scheduleEventsList->objects);
	}

	public function testUpdate()
	{
		$scheduleEvent = $this->create();
		$class = get_class($scheduleEvent);
		$update = new $class();
		$update->summary = uniqid('Test: ');
		$update->startDate = time() + (60 * 60 * 24);
		$update->duration = rand(60 * 60, 60 * 60 * 24);
		$update->recurrenceType = KalturaScheduleEventRecurrenceType::NONE;

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$updatedScheduleEvent = $plugin->scheduleEvent->update($scheduleEvent->id, $update);

		$this->assertEquals($update->summary, $updatedScheduleEvent->summary);
		$this->assertEquals($update->startDate, $updatedScheduleEvent->startDate);
		$this->assertEquals($update->duration, $updatedScheduleEvent->duration);
		$this->assertEquals($updatedScheduleEvent->endDate, $updatedScheduleEvent->startDate + $updatedScheduleEvent->duration);
		$this->assertGreaterThan($scheduleEvent->sequence + 1, $updatedScheduleEvent->sequence);
	}

	public function testGet()
	{
		$scheduleEvent = $this->create();
		$scheduleEventId = $scheduleEvent->id;

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$getScheduleEvent = $plugin->scheduleEvent->get($scheduleEventId);

		$this->assertEquals(get_class($scheduleEvent), get_class($getScheduleEvent));
		$this->assertEquals($scheduleEventId, $getScheduleEvent->id);
	}

	public function testDelete()
	{
		$scheduleEvent = $this->create();
		$this->delete($scheduleEvent->id);
	}

	public function testList()
	{
		$scheduleEvents = array(
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

		$scheduleEventsIds = array();
		foreach($scheduleEvents as $scheduleEvent)
		{
			$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
		}

		$filter = new KalturaScheduleEventFilter();
		$filter->idIn = implode(',', $scheduleEventsIds);

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);

		$this->assertEquals(count($scheduleEvents), $scheduleEventsList->totalCount);
	}

	public function testListByParentResources()
	{
		sleep(2);

		$parentResource1 = $this->createLocationResource();
		$parentResource2 = $this->createLocationResource();
		$resource1 = $this->createCameraResource(null, $parentResource1->id);
		$resource2 = $this->createLocationResource(null, $parentResource2->id);

		$commonParentResource = $this->createLocationResource();
		$commonResource = $this->createLocationResource(null, $commonParentResource->id);

		$resources = array(
				$resource1->id => 2,
				$resource2->id => 3,
		);

		$parentResources = array(
				$resource1->id => $parentResource1->id,
				$resource2->id => $parentResource2->id,
		);

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$allEvents = array();
		$resourceEvents = array();

		foreach($resources as $resourceId => $count)
		{
			$resourceEvents[$resourceId] = array();
			for($i = 0; $i < $count; $i++)
			{
				$uniqueParentResource = $this->createLocationResource();
				$uniqueResource = $this->createLocationResource(null, $uniqueParentResource->id);

				$scheduleEvent = $this->create('KalturaRecordScheduleEvent');

				$scheduleEvent->resourceIds = array($resourceId, $uniqueResource->id, $commonResource->id);

				$this->addEventResource($scheduleEvent->id, $resourceId);
				$this->addEventResource($scheduleEvent->id, $uniqueResource->id);
				$this->addEventResource($scheduleEvent->id, $commonResource->id);

				$allEvents[$scheduleEvent->id] = $scheduleEvent->id;
				$resourceEvents[$resourceId][$scheduleEvent->id] = $scheduleEvent->id;
			}
		}

		foreach($resourceEvents as $resourceId => $currentResourceEvents)
		{
			$parentResourceId = $parentResources[$resourceId];

			$filter = new KalturaRecordScheduleEventFilter();
			$filter->parentResourceIdsLike = $parentResourceId;

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($resources[$resourceId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $resourceId);
				$this->assertNotNull($eventResource);

				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $commonResource->id);
				$this->assertNotNull($eventResource);

				$this->assertTrue(isset($currentResourceEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaRecordScheduleEventFilter();
			$filter->parentResourceIdsMultiLikeAnd = "{$commonParentResource->id},$parentResourceId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
//			$this->assertEquals($resources[$resourceId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $resourceId);
				$this->assertNotNull($eventResource);

				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $commonResource->id);
				$this->assertNotNull($eventResource);

				$this->assertTrue(isset($currentResourceEvents[$listedScheduleEvent->id]));
			}

			$uniqueParentResource = $this->createLocationResource();
			$uniqueResource = $this->createLocationResource(null, $uniqueParentResource->id);

			$filter = new KalturaRecordScheduleEventFilter();
			$filter->parentResourceIdsMultiLikeOr = "{$uniqueParentResource->id},$parentResourceId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($resources[$resourceId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $resourceId);
				$this->assertNotNull($eventResource);

				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $commonResource->id);
				$this->assertNotNull($eventResource);

				$this->assertTrue(isset($currentResourceEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaRecordScheduleEventFilter();
			$filter->parentResourceIdsMultiLikeAnd = "{$uniqueParentResource->id},$parentResourceId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals(0, $scheduleEventsList->totalCount);
		}
	}

	public function testListByResources()
	{
		sleep(2);

		$parentResource = $this->createLocationResource();
		$resource1 = $this->createCameraResource(null, $parentResource->id);
		$resource2 = $this->createLocationResource();

		$commonResource = $this->createLocationResource();

		$resources = array(
			$resource1->id => 2,
			$resource2->id => 3,
		);

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$allEvents = array();
		$resourceEvents = array();

		foreach($resources as $resourceId => $count)
		{
			$resourceEvents[$resourceId] = array();
			for($i = 0; $i < $count; $i++)
			{
				$uniqueResource = $this->createLocationResource();
				$scheduleEvent = $this->create('KalturaRecordScheduleEvent');

				$scheduleEvent->resourceIds = array($resourceId, $uniqueResource->id, $commonResource->id);

				$this->addEventResource($scheduleEvent->id, $resourceId);
				$this->addEventResource($scheduleEvent->id, $uniqueResource->id);
				$this->addEventResource($scheduleEvent->id, $commonResource->id);

				$allEvents[$scheduleEvent->id] = $scheduleEvent->id;
				$resourceEvents[$resourceId][$scheduleEvent->id] = $scheduleEvent->id;
			}
		}

		foreach($resourceEvents as $resourceId => $currentResourceEvents)
		{
			$filter = new KalturaRecordScheduleEventFilter();
			$filter->resourceIdsLike = $resourceId;

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
//			$this->assertEquals($resources[$resourceId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $resourceId);
				$this->assertNotNull($eventResource);

				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $commonResource->id);
				$this->assertNotNull($eventResource);

				$this->assertTrue(isset($currentResourceEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaRecordScheduleEventFilter();
			$filter->resourceIdsMultiLikeAnd = "{$commonResource->id},$resourceId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
//			$this->assertEquals($resources[$resourceId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $resourceId);
				$this->assertNotNull($eventResource);

				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $commonResource->id);
				$this->assertNotNull($eventResource);

				$this->assertTrue(isset($currentResourceEvents[$listedScheduleEvent->id]));
			}

			$uniqueResource = $this->createLocationResource();
			$filter = new KalturaRecordScheduleEventFilter();
			$filter->resourceIdsMultiLikeOr = "{$uniqueResource->id},$resourceId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($resources[$resourceId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $resourceId);
				$this->assertNotNull($eventResource);

				$eventResource = $plugin->scheduleEventResource->get($listedScheduleEvent->id, $commonResource->id);
				$this->assertNotNull($eventResource);

				$this->assertTrue(isset($currentResourceEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaRecordScheduleEventFilter();
			$filter->resourceIdsMultiLikeAnd = "{$uniqueResource->id},$resourceId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals(0, $scheduleEventsList->totalCount);
		}
	}

	public function testListByEntries()
	{
		sleep(2);

		$entry1 = $this->createEntry();
		$entry2 = $this->createEntry();
		$commonEntry = $this->createEntry();

		$entries = array(
			$entry1->id => 2,
			$entry2->id => 3,
		);

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$allEvents = array();
		$entryEvents = array();

		foreach($entries as $entryId => $count)
		{
			$entryEvents[$entryId] = array();
			for($i = 0; $i < $count; $i++)
			{
				$uniqueEntry = $this->createEntry();
				$scheduleEvent = $this->create('KalturaRecordScheduleEvent', array('entryIds' => "$entryId,{$uniqueEntry->id},{$commonEntry->id}"));
				$allEvents[$scheduleEvent->id] = $scheduleEvent->id;
				$entryEvents[$entryId][$scheduleEvent->id] = $scheduleEvent->id;
			}
		}

		foreach($entryEvents as $entryId => $currentEntryEvents)
		{
			$filter = new KalturaRecordScheduleEventFilter();
			$filter->entryIdsLike = $entryId;

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($entries[$entryId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains(strval($entryId), $listedScheduleEvent->entryIds);
				$this->assertContains(strval($commonEntry->id), $listedScheduleEvent->entryIds);
				$this->assertTrue(isset($currentEntryEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaRecordScheduleEventFilter();
			$filter->entryIdsMultiLikeAnd = "{$commonEntry->id},$entryId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($entries[$entryId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains(strval($entryId), $listedScheduleEvent->entryIds);
				$this->assertContains(strval($commonEntry->id), $listedScheduleEvent->entryIds);
				$this->assertTrue(isset($currentEntryEvents[$listedScheduleEvent->id]));
			}

			$uniqueEntry = $this->createEntry();
			$filter = new KalturaRecordScheduleEventFilter();
			$filter->entryIdsMultiLikeOr = "{$uniqueEntry->id},$entryId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($entries[$entryId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains(strval($entryId), $listedScheduleEvent->entryIds);
				$this->assertContains(strval($commonEntry->id), $listedScheduleEvent->entryIds);
				$this->assertTrue(isset($currentEntryEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaRecordScheduleEventFilter();
			$filter->entryIdsMultiLikeAnd = "{$uniqueEntry->id},$entryId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals(0, $scheduleEventsList->totalCount);
		}
	}

	public function testListByParentCategories()
	{
		sleep(2);

		$parentCategory1 = $this->createCategory();
		$parentCategory2 = $this->createCategory();
		$category1 = $this->createCategory($parentCategory1->id);
		$category2 = $this->createCategory($parentCategory2->id);

		$commonParentCategory = $this->createCategory();
		$commonCategory = $this->createCategory($commonParentCategory->id);

		$categories = array(
				$category1->id => 2,
				$category2->id => 3,
		);

		$parentCategories = array(
				$category1->id => $parentCategory1->id,
				$category2->id => $parentCategory2->id,
		);

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$allEvents = array();
		$categoryEvents = array();

		foreach($categories as $categoryId => $count)
		{
			$categoryEvents[$categoryId] = array();
			for($i = 0; $i < $count; $i++)
			{
				$uniqueParentCategory = $this->createCategory();
				$uniqueCategory = $this->createCategory($uniqueParentCategory->id);
				$scheduleEvent = $this->create('KalturaRecordScheduleEvent', array('categoryIds' => "$categoryId,{$uniqueCategory->id},{$commonCategory->id}"));
				$allEvents[$scheduleEvent->id] = $scheduleEvent->id;
				$categoryEvents[$categoryId][$scheduleEvent->id] = $scheduleEvent->id;
			}
		}

		foreach($categoryEvents as $categoryId => $currentCategoryEvents)
		{
			$parentCategoryId = $parentCategories[$categoryId];

			$filter = new KalturaRecordScheduleEventFilter();
			$filter->parentCategoryIdsLike = $parentCategoryId;

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($categories[$categoryId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains(strval($categoryId), $listedScheduleEvent->categoryIds);
				$this->assertContains(strval($commonCategory->id), $listedScheduleEvent->categoryIds);
				$this->assertTrue(isset($currentCategoryEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaRecordScheduleEventFilter();
			$filter->parentCategoryIdsMultiLikeAnd = "{$commonParentCategory->id},$parentCategoryId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($categories[$categoryId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains(strval($categoryId), $listedScheduleEvent->categoryIds);
				$this->assertContains(strval($commonCategory->id), $listedScheduleEvent->categoryIds);
				$this->assertTrue(isset($currentCategoryEvents[$listedScheduleEvent->id]));
			}

			$uniqueParentCategory = $this->createCategory();
			$uniqueCategory = $this->createCategory($uniqueParentCategory->id);
			$filter = new KalturaRecordScheduleEventFilter();
			$filter->parentCategoryIdsMultiLikeOr = "{$uniqueParentCategory->id},$parentCategoryId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($categories[$categoryId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains(strval($categoryId), $listedScheduleEvent->categoryIds);
				$this->assertContains(strval($commonCategory->id), $listedScheduleEvent->categoryIds);
				$this->assertTrue(isset($currentCategoryEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaRecordScheduleEventFilter();
			$filter->parentCategoryIdsMultiLikeAnd = "{$uniqueCategory->id},$parentCategoryId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals(0, $scheduleEventsList->totalCount);
		}
	}

	public function testListByCategories()
	{
		sleep(2);

		$parentCategory = $this->createCategory();
		$category1 = $this->createCategory($parentCategory->id);
		$category2 = $this->createCategory();
		$commonCategory = $this->createCategory();

		$categories = array(
			$category1->id => 2,
			$category2->id => 3,
		);

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$allEvents = array();
		$categoryEvents = array();

		foreach($categories as $categoryId => $count)
		{
			$categoryEvents[$categoryId] = array();
			for($i = 0; $i < $count; $i++)
			{
				$uniqueCategory = $this->createCategory();
				$scheduleEvent = $this->create('KalturaRecordScheduleEvent', array('categoryIds' => "$categoryId,{$uniqueCategory->id},{$commonCategory->id}"));
				$allEvents[$scheduleEvent->id] = $scheduleEvent->id;
				$categoryEvents[$categoryId][$scheduleEvent->id] = $scheduleEvent->id;
			}
		}

		foreach($categoryEvents as $categoryId => $currentCategoryEvents)
		{
			$filter = new KalturaRecordScheduleEventFilter();
			$filter->categoryIdsLike = $categoryId;

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($categories[$categoryId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains(strval($categoryId), $listedScheduleEvent->categoryIds);
				$this->assertContains(strval($commonCategory->id), $listedScheduleEvent->categoryIds);
				$this->assertTrue(isset($currentCategoryEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaRecordScheduleEventFilter();
			$filter->categoryIdsMultiLikeAnd = "{$commonCategory->id},$categoryId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($categories[$categoryId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains(strval($categoryId), $listedScheduleEvent->categoryIds);
				$this->assertContains(strval($commonCategory->id), $listedScheduleEvent->categoryIds);
				$this->assertTrue(isset($currentCategoryEvents[$listedScheduleEvent->id]));
			}

			$uniqueCategory = $this->createCategory();
			$filter = new KalturaRecordScheduleEventFilter();
			$filter->categoryIdsMultiLikeOr = "{$uniqueCategory->id},$categoryId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($categories[$categoryId], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains(strval($categoryId), $listedScheduleEvent->categoryIds);
				$this->assertContains(strval($commonCategory->id), $listedScheduleEvent->categoryIds);
				$this->assertTrue(isset($currentCategoryEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaRecordScheduleEventFilter();
			$filter->categoryIdsMultiLikeAnd = "{$uniqueCategory->id},$categoryId";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals(0, $scheduleEventsList->totalCount);
		}
	}

	public function testListByTags()
	{
		sleep(2);

		$tags = array(
			uniqid() => 3,
			uniqid() => 2,
		);

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$allEvents = array();
		$tagEvents = array();

		foreach($tags as $tag => $count)
		{
			$tagEvents[$tag] = array();
			for($i = 0; $i < $count; $i++)
			{
				$uniqueTag = uniqid();
				$scheduleEvent = $this->create('KalturaRecordScheduleEvent', array('tags' => "$tag,$uniqueTag,{$this->uniqueTag}"));
				$allEvents[$scheduleEvent->id] = $scheduleEvent->id;
				$tagEvents[$tag][$scheduleEvent->id] = $scheduleEvent->id;
			}
		}

		foreach($tagEvents as $tag => $currentTagEvents)
		{
			$filter = new KalturaScheduleEventFilter();
			$filter->tagsLike = $tag;

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($tags[$tag], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains($tag, $listedScheduleEvent->tags);
				$this->assertContains($this->uniqueTag, $listedScheduleEvent->tags);
				$this->assertTrue(isset($currentTagEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaScheduleEventFilter();
			$filter->tagsMultiLikeAnd = "{$this->uniqueTag},$tag";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($tags[$tag], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains($tag, $listedScheduleEvent->tags);
				$this->assertContains($this->uniqueTag, $listedScheduleEvent->tags);
				$this->assertTrue(isset($currentTagEvents[$listedScheduleEvent->id]));
			}

			$uniqueTag = uniqid();
			$filter = new KalturaScheduleEventFilter();
			$filter->tagsMultiLikeOr = "$uniqueTag,$tag";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals($tags[$tag], $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertContains($tag, $listedScheduleEvent->tags);
				$this->assertContains($this->uniqueTag, $listedScheduleEvent->tags);
				$this->assertTrue(isset($currentTagEvents[$listedScheduleEvent->id]));
			}


			$filter = new KalturaScheduleEventFilter();
			$filter->tagsMultiLikeAnd = "$uniqueTag,$tag";

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals(0, $scheduleEventsList->totalCount);
		}
	}

	public function testListByRecurrenceType()
	{
		sleep(2);

		$recurring = 3;
		$recurrences = 3;
		$nonRecurring = 2;

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$recurringEvents = array();
		$nonRecurringEvents = array();

		$recurrence = new KalturaScheduleEventRecurrence();
		$recurrence->frequency = KalturaScheduleEventRecurrenceFrequency::WEEKLY;
		$recurrence->interval = 1;
		$recurrence->byDay = KalturaScheduleEventRecurrenceDay::MONDAY;
		$recurrence->byHour = 16;
		$recurrence->byMinute = 0;
		$recurrence->bySecond = 0;
		$recurrence->count = $recurrences;

		$endDate = time() + (60 * 60 * 24 * 365 * 2);
		$duration = 2000;
		for($i = 0; $i < $recurring; $i++)
		{
			$scheduleEvent = $this->create('KalturaRecordScheduleEvent', array('recurrenceType' => KalturaScheduleEventRecurrenceType::RECURRING, 'recurrence' => $recurrence, 'endDate' => $endDate, 'duration' => $duration) );
			$recurringEvents[$scheduleEvent->id] = $scheduleEvent;
		}

		for($i = 0; $i < $nonRecurring; $i++)
		{
			$scheduleEvent = $this->create();
			$nonRecurringEvents[$scheduleEvent->id] = $scheduleEvent;
		}




		$filter = new KalturaScheduleEventFilter();
		$filter->recurrenceTypeEqual = KalturaScheduleEventRecurrenceType::RECURRING;
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = $recurring;

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals($recurring, count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertEquals(KalturaScheduleEventRecurrenceType::RECURRING, $listedScheduleEvent->recurrenceType);
			$this->assertTrue(isset($recurringEvents[$listedScheduleEvent->id]));
		}




		$filter = new KalturaScheduleEventFilter();
		$filter->recurrenceTypeEqual = KalturaScheduleEventRecurrenceType::NONE;
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = $nonRecurring;

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals($nonRecurring, count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertEquals(KalturaScheduleEventRecurrenceType::NONE, $listedScheduleEvent->recurrenceType);
			$this->assertTrue(isset($nonRecurringEvents[$listedScheduleEvent->id]));
		}




		$filter = new KalturaScheduleEventFilter();
		$filter->recurrenceTypeEqual = KalturaScheduleEventRecurrenceType::RECURRENCE;
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = $recurring * $recurrences;

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals($recurring * $recurrences, count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertEquals(KalturaScheduleEventRecurrenceType::RECURRENCE, $listedScheduleEvent->recurrenceType);
			$this->assertTrue(isset($recurringEvents[$listedScheduleEvent->parentId]));
		}




		$filter = new KalturaScheduleEventFilter();
		$filter->recurrenceTypeIn = KalturaScheduleEventRecurrenceType::RECURRING . ',' . KalturaScheduleEventRecurrenceType::NONE;
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = $recurring + $nonRecurring;

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals($recurring + $nonRecurring, count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertArrayHasKey($listedScheduleEvent->recurrenceType, array(KalturaScheduleEventRecurrenceType::RECURRING => true, KalturaScheduleEventRecurrenceType::NONE => true));
			$this->assertTrue(isset($recurringEvents[$listedScheduleEvent->id]) || isset($nonRecurringEvents[$listedScheduleEvent->id]));
		}
	}

	public function testListByPriority()
	{
		sleep(2);

		$lows = 2;
		$highs = 3;

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$lowPriority = 3;
		$highPriority = 7;

		$scheduleEvents = array();
		$scheduleEventsIds = array();
		$scheduleEventsPriorities = array(
			$lowPriority => array(),
			$highPriority => array(),
		);


		$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('priority' => $lowPriority - 1));
		$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEventsTimes[$lowPriority][$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEvents[] = $scheduleEvent;

		for($i = 0; $i < $lows; $i++)
		{
			$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('priority' => $lowPriority));
			$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEventsTimes[$lowPriority][$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEvents[] = $scheduleEvent;
		}

		for($i = 0; $i < $highs; $i++)
		{
			$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('priority' => $highPriority));
			$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEventsTimes[$highPriority][$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEvents[] = $scheduleEvent;
		}

		$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('priority' => $highPriority));
		$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEventsTimes[$highPriority][$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEvents[] = $scheduleEvent;



		$filter = new KalturaScheduleEventFilter();
		$filter->priorityEqual  = $lowPriority;
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = count($scheduleEventsTimes[$lowPriority]) - 1;

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals(count($scheduleEventsTimes[$lowPriority]) - 1, count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertEquals($lowPriority, $listedScheduleEvent->priority);
			$this->assertTrue(isset($scheduleEventsTimes[$lowPriority][$listedScheduleEvent->id]));
		}



		$filter = new KalturaScheduleEventFilter();
		$filter->priorityLessThanOrEqual  = $lowPriority;
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = count($scheduleEventsTimes[$lowPriority]);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals(count($scheduleEventsTimes[$lowPriority]), count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertLessThanOrEqual($lowPriority, $listedScheduleEvent->priority);
			$this->assertTrue(isset($scheduleEventsTimes[$lowPriority][$listedScheduleEvent->id]));
		}



		$filter = new KalturaScheduleEventFilter();
		$filter->priorityGreaterThanOrEqual = $highPriority;
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = count($scheduleEventsTimes[$highPriority]);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals(count($scheduleEventsTimes[$highPriority]), count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertGreaterThanOrEqual($highPriority, $listedScheduleEvent->priority);
			$this->assertTrue(isset($scheduleEventsTimes[$highPriority][$listedScheduleEvent->id]));
		}



		$filter = new KalturaScheduleEventFilter();
		$filter->priorityIn = "$lowPriority,$highPriority";
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = count($scheduleEventsIds) - 2;

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals(count($scheduleEventsIds) - 2, count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertTrue(isset($scheduleEventsIds[$listedScheduleEvent->id]));
		}
	}

	public function testListByOwnerId()
	{
		sleep(2);

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$scheduleEvents = array(
			$this->create(),
			$this->create(),
		);

		$scheduleEventsIds = array();
		foreach($scheduleEvents as $scheduleEvent)
		{
			$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->ownerId;

			$filter = new KalturaScheduleEventFilter();
			$filter->ownerIdEqual = $scheduleEvent->ownerId;

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals(1, $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertEquals($scheduleEvent->id, $listedScheduleEvent->id);
				$this->assertEquals($scheduleEvent->ownerId, $listedScheduleEvent->ownerId);
			}
		}

		$filter = new KalturaScheduleEventFilter();
		$filter->ownerIdIn = implode(',', $scheduleEventsIds);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);

		$this->assertEquals(count($scheduleEvents), $scheduleEventsList->totalCount);
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertTrue(isset($scheduleEventsIds[$listedScheduleEvent->id]));
			$this->assertTrue(in_array($listedScheduleEvent->ownerId, $scheduleEventsIds));
		}
	}

	public function testListByReferenceId()
	{
		sleep(2);

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$scheduleEvents = array(
			$this->create(),
			$this->create(),
		);

		$scheduleEventsIds = array();
		foreach($scheduleEvents as $scheduleEvent)
		{
			$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->referenceId;

			$filter = new KalturaScheduleEventFilter();
			$filter->referenceIdEqual = $scheduleEvent->referenceId;

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals(1, $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertEquals($scheduleEvent->id, $listedScheduleEvent->id);
				$this->assertEquals($scheduleEvent->referenceId, $listedScheduleEvent->referenceId);
			}
		}

		$filter = new KalturaScheduleEventFilter();
		$filter->referenceIdIn = implode(',', $scheduleEventsIds);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);

		$this->assertEquals(count($scheduleEvents), $scheduleEventsList->totalCount);
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertTrue(isset($scheduleEventsIds[$listedScheduleEvent->id]));
			$this->assertTrue(in_array($listedScheduleEvent->referenceId, $scheduleEventsIds));
		}
	}

	public function testListByEndDate()
	{
		sleep(2);

		$lows = 2;
		$highs = 3;

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$lowStartDate = time() + (60 * 60 * 24);
		$highStartDate = time() + (60 * 60 * 24 * 10);

		$lowEndDate = $lowStartDate + (60 * 60);
		$highEndDate = $highStartDate + (60 * 60);

		$scheduleEvents = array();
		$scheduleEventsIds = array();
		$scheduleEventsTimes = array(
			$lowEndDate => array(),
			$highEndDate => array(),
		);


		$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('startDate' => $lowStartDate, 'endDate' => $lowEndDate - (60 * 30)));
		$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEventsTimes[$lowEndDate][$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEvents[] = $scheduleEvent;

		for($i = 0; $i < $lows; $i++)
		{
			$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('startDate' => $lowStartDate, 'endDate' => $lowEndDate));
			$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEventsTimes[$lowEndDate][$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEvents[] = $scheduleEvent;
		}

		for($i = 0; $i < $highs; $i++)
		{
			$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('startDate' => $highStartDate, 'endDate' => $highEndDate));
			$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEventsTimes[$highEndDate][$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEvents[] = $scheduleEvent;
		}

		$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('startDate' => $highStartDate, 'endDate' => $highEndDate + (60 * 30)));
		$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEventsTimes[$highEndDate][$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEvents[] = $scheduleEvent;



		$filter = new KalturaScheduleEventFilter();
		$filter->endDateLessThanOrEqual = $lowEndDate;
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = count($scheduleEventsTimes[$lowEndDate]);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals(count($scheduleEventsTimes[$lowEndDate]), count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertLessThanOrEqual($lowEndDate, $listedScheduleEvent->endDate);
			$this->assertTrue(isset($scheduleEventsTimes[$lowEndDate][$listedScheduleEvent->id]));
		}



		$filter = new KalturaScheduleEventFilter();
		$filter->endDateGreaterThanOrEqual = $highEndDate;
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = count($scheduleEventsTimes[$highEndDate]);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals(count($scheduleEventsTimes[$highEndDate]), count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertGreaterThanOrEqual($highEndDate, $listedScheduleEvent->endDate);
			$this->assertTrue(isset($scheduleEventsTimes[$highEndDate][$listedScheduleEvent->id]));
		}



		$filter = new KalturaScheduleEventFilter();
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = count($scheduleEvents);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);

		$this->assertEquals(count($scheduleEvents), count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertTrue(isset($scheduleEventsIds[$listedScheduleEvent->id]));
		}
	}

	public function testListByStartDate()
	{
		sleep(2);

		$lows = 2;
		$highs = 3;

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$lowStartDate = time() + (60 * 60 * 24);
		$highStartDate = time() + (60 * 60 * 24 * 10);

		$scheduleEvents = array();
		$scheduleEventsIds = array();
		$scheduleEventsTimes = array(
			$lowStartDate => array(),
			$highStartDate => array(),
		);


		$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('startDate' => $lowStartDate - 10000, 'endDate' => $lowStartDate + 6000));
		$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEventsTimes[$lowStartDate][$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEvents[] = $scheduleEvent;

		for($i = 0; $i < $lows; $i++)
		{
			$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('startDate' => $lowStartDate, 'endDate' => $lowStartDate + 6000));
			$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEventsTimes[$lowStartDate][$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEvents[] = $scheduleEvent;
		}

		for($i = 0; $i < $highs; $i++)
		{
			$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('startDate' => $highStartDate, 'endDate' => $highStartDate + 6000));
			$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEventsTimes[$highStartDate][$scheduleEvent->id] = $scheduleEvent->id;
			$scheduleEvents[] = $scheduleEvent;
		}

		$scheduleEvent = $this->create('KalturaLiveStreamScheduleEvent', array('startDate' => $highStartDate + 10000, 'endDate' => $highStartDate + 12000));
		$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEventsTimes[$highStartDate][$scheduleEvent->id] = $scheduleEvent->id;
		$scheduleEvents[] = $scheduleEvent;



		$filter = new KalturaScheduleEventFilter();
		$filter->startDateLessThanOrEqual = $lowStartDate;
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = count($scheduleEventsTimes[$lowStartDate]);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals(count($scheduleEventsTimes[$lowStartDate]), count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertLessThanOrEqual($lowStartDate, $listedScheduleEvent->startDate);
			$this->assertTrue(isset($scheduleEventsTimes[$lowStartDate][$listedScheduleEvent->id]));
		}



		$filter = new KalturaScheduleEventFilter();
		$filter->startDateGreaterThanOrEqual = $highStartDate;
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = count($scheduleEventsTimes[$highStartDate]);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
		$this->assertEquals(count($scheduleEventsTimes[$highStartDate]), count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertGreaterThanOrEqual($highStartDate, $listedScheduleEvent->startDate);
			$this->assertTrue(isset($scheduleEventsTimes[$highStartDate][$listedScheduleEvent->id]));
		}



		$filter = new KalturaScheduleEventFilter();
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = count($scheduleEvents);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);

		$this->assertEquals(count($scheduleEvents), count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertTrue(isset($scheduleEventsIds[$listedScheduleEvent->id]));
		}
	}

	public function testListByStatus()
	{
		sleep(2);

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$scheduleEvents = array(
			$this->create(),
			$this->create(),
			$this->create(),
			$this->create(),
			$this->create(),
		);

		$scheduleEventsIds = array();
		$scheduleEventsStatuses = array(
			KalturaScheduleEventStatus::ACTIVE => array(),
			KalturaScheduleEventStatus::CANCELLED => array(),
		);

		for($i = 0; $i < 2; $i++)
		{
			$scheduleEventId = $scheduleEvents[$i]->id;
			$scheduleEventsIds[$scheduleEventId] = $scheduleEventId;
			$scheduleEventsStatuses[KalturaScheduleEventStatus::CANCELLED][$scheduleEventId] = $scheduleEventId;
			$plugin->scheduleEvent->cancel($scheduleEventId);
		}

		for($i = 2; $i < count($scheduleEvents); $i++)
		{
			$scheduleEventId = $scheduleEvents[$i]->id;
			$scheduleEventsIds[$scheduleEventId] = $scheduleEventId;
			$scheduleEventsStatuses[KalturaScheduleEventStatus::ACTIVE][$scheduleEventId] = $scheduleEventId;
		}

		foreach($scheduleEventsStatuses as $status => $statusScheduleEventsIds)
		{
			$filter = new KalturaScheduleEventFilter();
			$filter->statusEqual = $status;
			$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

			$pager = new KalturaFilterPager();
			$pager->pageIndex = 1;
			$pager->pageSize = count($statusScheduleEventsIds);

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);
			$this->assertEquals(count($statusScheduleEventsIds), count($scheduleEventsList->objects));
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertEquals($status, $listedScheduleEvent->status);
				$this->assertTrue(isset($statusScheduleEventsIds[$listedScheduleEvent->id]));
			}
		}

		$filter = new KalturaScheduleEventFilter();
		$filter->statusIn = implode(',', array_keys($scheduleEventsStatuses));
		$filter->orderBy = KalturaScheduleEventOrderBy::CREATED_AT_DESC;

		$pager = new KalturaFilterPager();
		$pager->pageIndex = 1;
		$pager->pageSize = count($scheduleEvents);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter, $pager);

		$this->assertEquals(count($scheduleEvents), count($scheduleEventsList->objects));
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertTrue(isset($scheduleEventsIds[$listedScheduleEvent->id]));
		}
	}

	public function testListById()
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$scheduleEvents = array(
			$this->create(),
			$this->create(),
		);

		$scheduleEventsIds = array();
		foreach($scheduleEvents as $scheduleEvent)
		{
			$scheduleEventsIds[$scheduleEvent->id] = $scheduleEvent->id;

			$filter = new KalturaScheduleEventFilter();
			$filter->idEqual = $scheduleEvent->id;

			$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);
			$this->assertEquals(1, $scheduleEventsList->totalCount);
			foreach($scheduleEventsList->objects as $listedScheduleEvent)
			{
				/* @var $listedScheduleEvent KalturaScheduleEvent */
				$this->assertEquals($scheduleEvent->id, $listedScheduleEvent->id);
			}
		}

		$filter = new KalturaScheduleEventFilter();
		$filter->idIn = implode(',', $scheduleEventsIds);

		$scheduleEventsList = $plugin->scheduleEvent->listAction($filter);

		$this->assertEquals(count($scheduleEvents), $scheduleEventsList->totalCount);
		foreach($scheduleEventsList->objects as $listedScheduleEvent)
		{
			/* @var $listedScheduleEvent KalturaScheduleEvent */
			$this->assertTrue(isset($scheduleEventsIds[$listedScheduleEvent->id]));
		}
	}

	/**
	 * @param string $data
	 * @return kSchedulingICalCalendar
	 */
	protected function addBulkUpload($data)
	{
		$filename = tempnam(sys_get_temp_dir(), 'bulk.') . '.ical';
		file_put_contents($filename, $data);

		$bulkUploadData = new KalturaBulkUploadICalJobData();
		$bulkUploadData->eventsType = KalturaScheduleEventType::RECORD;

		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);
		$bulkUpload = $plugin->scheduleEvent->addFromBulkUpload($filename, $bulkUploadData);
		$logUrl = $client->bulkUpload->serveLog($bulkUpload->id);

		while(true)
		{
			echo "Fetching log.\n";
			$log = file_get_contents($logUrl);
			if($log === 'Log file is not ready')
			{
				sleep(10);
				continue;
			}

			var_dump($log);
			$calendar = kSchedulingICal::parse($log, KalturaScheduleEventType::RECORD);

			foreach($calendar->getComponents() as $event)
			{
				/* @var $event kSchedulingICalEvent */
				if($event->getField('x-kaltura-ingest-status') == KalturaBulkUploadResultStatus::ERROR)
					$this->fail($event->getField('x-kaltura-error-description'));
			}

			return $calendar;
		}
	}

	public function testBulkUpload()
	{
		$events = 3;

		$content = "BEGIN:VCALENDAR\r\n";
		$content .= "VERSION:1.0\r\n";
		$content .= "PRODID:-//Kaltura/tests//Bulk-Upload//EN\r\n";

		$now = time();
		$items = array();
		for($i = 1; $i <= $events; $i++)
		{
			$id = uniqid();

			$items[$id] = array(
				"UID" => "$id",
				"DTSTAMP" => kSchedulingICal::formatDate($now),
				"DTSTART" => kSchedulingICal::formatDate($now + (60 * 60 * $i)),
				"DTEND" => kSchedulingICal::formatDate($now + (60 * 60 * ($i + 1))),
				"SUMMARY" => "Test $i - $id",
			);

			$content .= "BEGIN:VEVENT\r\n";
			foreach($items[$id] as $field => $value)
				$content .= "$field:$value\r\n";
			$content .= "END:VEVENT\r\n";
		}

		$content .= "END:VCALENDAR\r\n";

		$calendar = $this->addBulkUpload($content);
		foreach($calendar->getComponents() as $event)
		{
			/* @var $event kSchedulingICalEvent */
			$this->assertArrayHasKey($event->getUid(), $items);
			$item = $items[$event->getUid()];

			foreach($item as $field => $value)
			{
				if($field != 'DTSTAMP')
					$this->assertEquals($value, $event->getField($field));
			}
		}
	}

	public function _testRemotelDropFolder()
	{
		$host = 'allinone-be.dev.kaltura.com';
		$port = 22;
		$username = 'root';
		$password = 'Kaltura12#';

		$this->dropFolderTest($host, $port, $username, $password, KalturaDropFolderType::SFTP);
	}

	public function _testLocalDropFolder()
	{
		$host = 'allinone-be.dev.kaltura.com';
		$port = 22;
		$username = 'root';
		$password = 'Kaltura12#';

		$this->dropFolderTest($host, $port, $username, $password, KalturaDropFolderType::LOCAL);
	}

	public function dropFolderTest($host, $port, $username, $password, $type)
	{
		$events = 3;

		$content = "BEGIN:VCALENDAR\r\n";
		$content .= "VERSION:1.0\r\n";
		$content .= "PRODID:-//Kaltura/tests//Bulk-Upload//EN\r\n";

		$now = time();
		$items = array();
		for($i = 1; $i <= $events; $i++)
		{
			$id = uniqid();

			$items[$id] = array(
				"UID" => "$id",
				"DTSTAMP" => kSchedulingICal::formatDate($now),
				"DTSTART" => kSchedulingICal::formatDate($now + (60 * 60 * $i)),
				"DTEND" => kSchedulingICal::formatDate($now + (60 * 60 * ($i + 1))),
				"SUMMARY" => "Test $i - $id",
			);

			$content .= "BEGIN:VEVENT\r\n";
			foreach($items[$id] as $field => $value)
				$content .= "$field:$value\r\n";
			$content .= "END:VEVENT\r\n";
		}

		$content .= "END:VCALENDAR\r\n";

		$client = $this->getAdminClient();
		$dropFolderPlugin = KalturaDropFolderClientPlugin::get($client);

		$dropFolderFilter = new KalturaDropFolderFilter();
		$dropFolderFilter->typeEqual = $type;
		$dropFolderFilter->fileHandlerTypeEqual = KalturaDropFolderFileHandlerType::ICAL;

		$dropFolders = $dropFolderPlugin->dropFolder->listAction($dropFolderFilter);
		$this->assertGreaterThan(0, count($dropFolders->objects));
		$dropFolder = reset($dropFolders->objects);
		/* @var $dropFolder KalturaDropFolder */
		$fileName = uniqid() . '.ical';
		$remoteFile = $dropFolder->path . '/' . $fileName;

		$connection = ssh2_connect($host, $port);
		if(!$connection)
			$this->fail("Failed to open connection [$host:$port]");

		$login = ssh2_auth_password($connection, $username, $password);
		if(!$login)
			$this->fail("Failed to login [$username/$password]");

		$sftp = ssh2_sftp($connection);
		if(!$sftp)
			$this->fail("Failed to open sftp");

		$uri = "ssh2.sftp://$sftp/$remoteFile";
		$stream = @fopen($uri, 'w');
		if(!$stream)
			$this->fail("Failed to open stream [" . $uri . "]");

		if(fwrite($stream, $content) === false)
		{
			fclose($stream);
			$this->fail("Failed to upload file to [$uri]");
		}
		fclose($stream);


		$dropFolderFileFilter = new KalturaDropFolderFileFilter();
		$dropFolderFileFilter->dropFolderIdEqual = $dropFolder->id;
		$dropFolderFileFilter->fileNameEqual = $fileName;

		$dropFolderFile = null;
		$dropFolderFileStatus = null;
		/* @var $dropFolderFile KalturaDropFolderFile */
		while (!$dropFolderFile || $dropFolderFile->status != KalturaDropFolderFileStatus::HANDLED){
			sleep(2);
			$dropFolderFileList = $dropFolderPlugin->dropFolderFile->listAction($dropFolderFileFilter);
			if($dropFolderFileList->totalCount)
			{
				$dropFolderFile = reset($dropFolderFileList->objects);
				if($dropFolderFileStatus != $dropFolderFile->status)
				{
					var_dump($dropFolderFile);
					$dropFolderFileStatus = $dropFolderFile->status;
				}
			}
		}

		$logUrl = $client->bulkUpload->serveLog($dropFolderFile->batchJobId);
		$calendar = null;
		while(!$calendar)
		{
			echo "Fetching log.\n";
			$log = file_get_contents($logUrl);
			if($log === 'Log file is not ready')
			{
				sleep(10);
				continue;
			}

			var_dump($log);
			$calendar = kSchedulingICal::parse($log, KalturaScheduleEventType::RECORD);

			foreach($calendar->getComponents() as $event)
			{
				/* @var $event kSchedulingICalEvent */
				if($event->getField('x-kaltura-ingest-status') == KalturaBulkUploadResultStatus::ERROR)
					$this->fail($event->getField('x-kaltura-error-description'));
			}
		}

		$this->assertEquals($events, count($calendar->getComponents()));

		foreach($calendar->getComponents() as $event)
		{
			/* @var $event kSchedulingICalEvent */
			$this->assertArrayHasKey($event->getUid(), $items);
			$item = $items[$event->getUid()];

			foreach($item as $field => $value)
			{
				if($field != 'DTSTAMP')
					$this->assertEquals($value, $event->getField($field));
			}
		}
	}

	public function testBulkUploadKalturaAttributes()
	{
		$client = $this->getAdminClient();
		$plugin = KalturaScheduleClientPlugin::get($client);

		$this->createEntry();
		$this->createEntry();
		$this->createEntry();
		$entryIds = implode(',', array_keys($this->createdEntries));

		$templateEntry = $this->createEntry();

		$parentCategory = $this->createCategory();
		$cat1 = $this->createCategory($parentCategory->id);
		$cat2 = $this->createCategory();
		$categoryIds = "{$cat1->id},{$cat2->id}";

		$now = time();
		$startTime = $now + (60 * 60 * 3);
		$endTime = $now + (60 * 60 * 4);
		$referenceId = uniqid();

		$content = "BEGIN:VCALENDAR\r\n";
		$content .= "VERSION:1.0\r\n";
		$content .= "PRODID:-//Kaltura/tests//Bulk-Upload//EN\r\n";

		$content .= "BEGIN:VEVENT\r\n";
		$content .= "UID:$referenceId\r\n";
		$content .= "DTSTAMP:" . kSchedulingICal::formatDate($now) . "\r\n";
		$content .= "DTSTART:" . kSchedulingICal::formatDate($startTime) . "\r\n";
		$content .= "DTEND:" . kSchedulingICal::formatDate($endTime) . "\r\n";
		$content .= "SUMMARY:Test\r\n";

		$content .= "X-KALTURA-TAGS:test,{$this->uniqueTag}\r\n";
		$content .= "X-KALTURA-ENTRY-IDS:$entryIds\r\n";
		$content .= "X-KALTURA-CATEGORY-IDS:$categoryIds\r\n";
		$content .= "X-KALTURA-TEMPLATE-ENTRY-ID:{$templateEntry->id}\r\n";

		$content .= "END:VEVENT\r\n";

		$content .= "END:VCALENDAR\r\n";

		$calendar = $this->addBulkUpload($content);
		foreach($calendar->getComponents() as $event)
		{
			/* @var $event kSchedulingICalEvent */
			$id = $event->getField('x-kaltura-id');
			$scheduleEvent = $plugin->scheduleEvent->get($id);
			/* @var $scheduleEvent KalturaRecordScheduleEvent */

			$this->assertEquals($startTime, $scheduleEvent->startDate);
			$this->assertEquals($endTime, $scheduleEvent->endDate);

			$this->assertEquals($referenceId, $scheduleEvent->referenceId);
			$this->assertEquals("test,{$this->uniqueTag}", $scheduleEvent->tags);
			$this->assertEquals($entryIds, $scheduleEvent->entryIds);
			$this->assertEquals($categoryIds, $scheduleEvent->categoryIds);
			$this->assertEquals($templateEntry->id, $scheduleEvent->templateEntryId);
		}
	}


	public function getICal($fields = array())
	{
		$content = "BEGIN:VCALENDAR\r\n";
		$content .= "VERSION:1.0\r\n";
		$content .= "PRODID:-//Kaltura/tests//Bulk-Upload//EN\r\n";

		$now = time();
		$id = uniqid();

		$content .= "BEGIN:VEVENT\r\n";
		$content .= "UID:$id\r\n";
		$content .= "DTSTAMP:" .  kSchedulingICal::formatDate($now). "\r\n";
		$content .= "DTSTART:" .  kSchedulingICal::formatDate($now + (60 * 60 * 2)). "\r\n";
		$content .= "DTEND:" .  kSchedulingICal::formatDate($now + (60 * 60 * 3)). "\r\n";
		$content .= "DURATION:2000" . "\r\n";
		$content .= "SUMMARY:Test $id\r\n";

		foreach($fields as $field => $value)
			$content .= "$field:$value\r\n";

			$content .= "END:VEVENT\r\n";

			$content .= "END:VCALENDAR\r\n";

			return $content;
	}

	public function validateICal($content)
	{
		var_dump($content);

		$calendar = kSchedulingICal::parse($content, KalturaScheduleEventType::RECORD);
		$components = $calendar->getComponents();

		$events = array();
		foreach($components as $component)
		{
			/* @var $component kSchedulingICalEvent */
			$this->assertTrue(is_object($component));
			$this->assertEquals('kSchedulingICalEvent', get_class($component));

			$event = $component->toObject();
			$this->assertEquals('KalturaRecordScheduleEvent', get_class($event));

			$createdEvent = $this->add($event);
			$events[$component->getUid()] = $createdEvent;
		}
		var_dump($events);

		return $events;
	}

	/**
	 * @param string $rule
	 * @return KalturaScheduleEventRecurrence
	 */
	public function doTestICalWithRules($rule)
	{
		$content = $this->getICal(array('RRULE' => $rule));
		$events = $this->validateICal($content);
		$event = reset($events);
		/* @var $event KalturaRecordScheduleEvent */

		return $event->recurrence;
	}

	public function testICalWithRules()
	{
		$rule = $this->doTestICalWithRules('FREQ=YEARLY;INTERVAL=2;BYMONTH=1;BYDAY=SU;BYHOUR=8,9;BYMINUTE=30');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(2, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals(1, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('SU', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals('8,9', $rule->byHour, "byHour [$rule->byHour]");
		$this->assertEquals(30, $rule->byMinute, "byMinute [$rule->byMinute]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");

		$until = time() + (60 * 60 * 24 * 365 * 6);
		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYMONTH=4;BYDAY=-1SU;UNTIL=' . kSchedulingICal::formatDate($until));
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(4, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('-1SU', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$until = time() + (60 * 60 * 24 * 365 * 6);
		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU;UNTIL=' . kSchedulingICal::formatDate($until));
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(10, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('-1SU', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$until = time() + (60 * 60 * 24 * 365 * 3);
		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYMONTH=4;BYDAY=-1SU;UNTIL=' . kSchedulingICal::formatDate($until));
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(4, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('-1SU', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$until = time() + (60 * 60 * 24 * 365 * 5);
		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYMONTH=4;BYDAY=1SU;UNTIL=' . kSchedulingICal::formatDate($until));
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(4, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('1SU', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYMONTH=3;BYDAY=2SU');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(3, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('2SU', $rule->byDay, "byDay [$rule->byDay]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYMONTH=11;BYDAY=1SU');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(11, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('1SU', $rule->byDay, "byDay [$rule->byDay]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYMONTH=11;BYDAY=1SU');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(11, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('1SU', $rule->byDay, "byDay [$rule->byDay]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYMONTH=3;BYDAY=2SU');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(3, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('2SU', $rule->byDay, "byDay [$rule->byDay]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(10, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('-1SU', $rule->byDay, "byDay [$rule->byDay]");


		$until = time() + (60 * 60 * 24 * 365);
		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYDAY=1SU;BYMONTH=4;UNTIL=' . kSchedulingICal::formatDate($until));
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(4, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('1SU', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(10, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('-1SU', $rule->byDay, "byDay [$rule->byDay]");


		$until = time() + (60 * 60 * 24 * 365 * 2);
		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYDAY=1SU;BYMONTH=4;UNTIL=' . kSchedulingICal::formatDate($until));
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(4, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('1SU', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYDAY=-1SU;BYMONTH=4');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(4, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('-1SU', $rule->byDay, "byDay [$rule->byDay]");


		$rule = $this->doTestICalWithRules('FREQ=DAILY;COUNT=10');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::DAILY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(10, $rule->count, "count [$rule->count]");


		$until = time() + (60 * 60 * 24 * 365 * 6);
		$rule = $this->doTestICalWithRules('FREQ=DAILY;UNTIL=' . kSchedulingICal::formatDate($until));
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::DAILY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$rule = $this->doTestICalWithRules('FREQ=DAILY;INTERVAL=2');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::DAILY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(2, $rule->interval, "interval [$rule->interval]");


		$rule = $this->doTestICalWithRules('FREQ=DAILY;INTERVAL=10;COUNT=5');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::DAILY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(10, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals(5, $rule->count, "count [$rule->count]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;UNTIL=' . kSchedulingICal::formatDate($until) . ';BYMONTH=1;BYDAY=SU,MO,TU,WE,TH,FR,SA');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$rule = $this->doTestICalWithRules('FREQ=DAILY;UNTIL=' . kSchedulingICal::formatDate($until) . ';BYMONTH=1');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::DAILY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(1, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$rule = $this->doTestICalWithRules('FREQ=WEEKLY;COUNT=10');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::WEEKLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(10, $rule->count, "count [$rule->count]");


		$until = time() + (60 * 60 * 24 * 365 * 6);
		$rule = $this->doTestICalWithRules('FREQ=WEEKLY;UNTIL=' . kSchedulingICal::formatDate($until));
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::WEEKLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$rule = $this->doTestICalWithRules('FREQ=WEEKLY;INTERVAL=2;WKST=SU');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::WEEKLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(2, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals('SU', $rule->weekStartDay, "weekStartDay [$rule->weekStartDay]");


		$until = time() + (60 * 60 * 24 * 365 * 6);
		$rule = $this->doTestICalWithRules('FREQ=WEEKLY;UNTIL=' . kSchedulingICal::formatDate($until) . ';WKST=SU;BYDAY=TU,TH');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::WEEKLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$rule = $this->doTestICalWithRules('FREQ=WEEKLY;COUNT=10;WKST=SU;BYDAY=TU,TH');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::WEEKLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('TU,TH', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(10, $rule->count, "count [$rule->count]");
		$this->assertEquals('SU', $rule->weekStartDay, "weekStartDay [$rule->weekStartDay]");


		$until = time() + (60 * 60 * 24 * 365 * 6);
		$rule = $this->doTestICalWithRules('FREQ=WEEKLY;INTERVAL=2;UNTIL=' . kSchedulingICal::formatDate($until) . ';WKST=SU;BYDAY=MO,WE,FR');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::WEEKLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(2, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals('MO,WE,FR', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");
		$this->assertEquals('SU', $rule->weekStartDay, "weekStartDay [$rule->weekStartDay]");


		$rule = $this->doTestICalWithRules('FREQ=WEEKLY;INTERVAL=2;COUNT=8;WKST=SU;BYDAY=TU,TH');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::WEEKLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(2, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals('TU,TH', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(8, $rule->count, "count [$rule->count]");
		$this->assertEquals('SU', $rule->weekStartDay, "weekStartDay [$rule->weekStartDay]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;COUNT=10;BYDAY=1FR');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('1FR', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(10, $rule->count, "count [$rule->count]");


		$until = time() + (60 * 60 * 24 * 365 * 6);
		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;UNTIL=' . kSchedulingICal::formatDate($until) . ';BYDAY=1FR');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('1FR', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;INTERVAL=2;COUNT=10;BYDAY=1SU,-1SU');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(2, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals('1SU,-1SU', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(10, $rule->count, "count [$rule->count]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;COUNT=6;BYDAY=-2MO');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('-2MO', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(6, $rule->count, "count [$rule->count]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;BYMONTHDAY=-3');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(-3, $rule->byMonthDay, "byMonthDay [$rule->byMonthDay]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;COUNT=10;BYMONTHDAY=2,15');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('2,15', $rule->byMonthDay, "byMonthDay [$rule->byMonthDay]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;COUNT=10;BYMONTHDAY=1,-1');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('1,-1', $rule->byMonthDay, "byMonthDay [$rule->byMonthDay]");
		$this->assertEquals(10, $rule->count, "count [$rule->count]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;INTERVAL=18;COUNT=10;BYMONTHDAY=10,11,12,13,14,15');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('10,11,12,13,14,15', $rule->byMonthDay, "byMonthDay [$rule->byMonthDay]");
		$this->assertEquals(18, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals(10, $rule->count, "count [$rule->count]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;INTERVAL=2;BYDAY=TU');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(2, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals('TU', $rule->byDay, "byDay [$rule->byDay]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;COUNT=10;BYMONTH=6,7');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('6,7', $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals(10, $rule->count, "count [$rule->count]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;INTERVAL=2;COUNT=10;BYMONTH=1,2,3');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(2, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals('1,2,3', $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals(10, $rule->count, "count [$rule->count]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;INTERVAL=3;COUNT=10;BYYEARDAY=1,100,200');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(3, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals(10, $rule->count, "count [$rule->count]");
		$this->assertEquals('1,100,200', $rule->byYearDay, "byYearDay [$rule->byYearDay]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYDAY=20MO');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('20MO', $rule->byDay, "byDay [$rule->byDay]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYWEEKNO=20;BYDAY=MO');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('MO', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(20, $rule->byWeekNumber, "byWeekNumber [$rule->byWeekNumber]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYMONTH=3;BYDAY=TH');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('TH', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(3, $rule->byMonth, "byMonth [$rule->byMonth]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;BYDAY=TH;BYMONTH=6,7,8');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('TH', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals('6,7,8', $rule->byMonth, "byMonth [$rule->byMonth]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;BYDAY=FR;BYMONTHDAY=13');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('FR', $rule->byDay, "byDay [$rule->byDay]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;BYDAY=SA;BYMONTHDAY=7,8,9,10,11,12,13');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('SA', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals('7,8,9,10,11,12,13', $rule->byMonthDay, "byMonthDay [$rule->byMonthDay]");


		$rule = $this->doTestICalWithRules('FREQ=YEARLY;INTERVAL=4;BYMONTH=11;BYDAY=TU;BYMONTHDAY=2,3,4,5,6,7,8');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::YEARLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(4, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals('TU', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(11, $rule->byMonth, "byMonth [$rule->byMonth]");
		$this->assertEquals('2,3,4,5,6,7,8', $rule->byMonthDay, "byMonthDay [$rule->byMonthDay]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;COUNT=3;BYDAY=TU,WE,TH;BYSETPOS=3');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('TU,WE,TH', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(3, $rule->count, "count [$rule->count]");
		$this->assertEquals(3, $rule->byOffset, "byOffset [$rule->byOffset]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-2');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('MO,TU,WE,TH,FR', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(-2, $rule->byOffset, "byOffset [$rule->byOffset]");


		$until = time() + (60 * 60 * 24 * 365 * 6);
		$rule = $this->doTestICalWithRules('FREQ=HOURLY;INTERVAL=3;UNTIL=' . kSchedulingICal::formatDate($until));
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::HOURLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(3, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals($until, $rule->until, "until [$rule->until]");


		$rule = $this->doTestICalWithRules('FREQ=MINUTELY;INTERVAL=15;COUNT=6');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MINUTELY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(15, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals(6, $rule->count, "count [$rule->count]");


		$rule = $this->doTestICalWithRules('FREQ=MINUTELY;INTERVAL=90;COUNT=4');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MINUTELY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(90, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals(4, $rule->count, "count [$rule->count]");


		$rule = $this->doTestICalWithRules('FREQ=DAILY;BYHOUR=9,10,11,12,13,14,15,16;BYMINUTE=0,20,40');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::DAILY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals('0,20,40', $rule->byMinute, "byMinute [$rule->byMinute]");


		$rule = $this->doTestICalWithRules('FREQ=MINUTELY;INTERVAL=20;BYHOUR=9,10,11,12,13,14,15,16');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MINUTELY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(20, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals('9,10,11,12,13,14,15,16', $rule->byHour, "byHour [$rule->byHour]");


		$rule = $this->doTestICalWithRules('FREQ=WEEKLY;INTERVAL=2;COUNT=4;BYDAY=TU,SU;WKST=MO');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::WEEKLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(2, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals('TU,SU', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(4, $rule->count, "count [$rule->count]");
		$this->assertEquals('MO', $rule->weekStartDay, "weekStartDay [$rule->weekStartDay]");


		$rule = $this->doTestICalWithRules('FREQ=WEEKLY;INTERVAL=2;COUNT=4;BYDAY=TU,SU;WKST=SU');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::WEEKLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(2, $rule->interval, "interval [$rule->interval]");
		$this->assertEquals('TU,SU', $rule->byDay, "byDay [$rule->byDay]");
		$this->assertEquals(4, $rule->count, "count [$rule->count]");
		$this->assertEquals('SU', $rule->weekStartDay, "weekStartDay [$rule->weekStartDay]");


		$rule = $this->doTestICalWithRules('FREQ=MONTHLY;BYMONTHDAY=15,30;COUNT=5');
		$this->assertEquals(KalturaScheduleEventRecurrenceFrequency::MONTHLY, $rule->frequency, "frequency [$rule->frequency]");
		$this->assertEquals(5, $rule->count, "count [$rule->count]");
		$this->assertEquals('15,30', $rule->byMonthDay, "byMonthDay [$rule->byMonthDay]");
	}
}
