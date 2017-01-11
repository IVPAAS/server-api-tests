<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');

function TestScheduleResourceWithParentId($client)
{
	info("Testing Schedule resource creation with exising parentId");

	$failCount = 0;

	$testSystemName1 = 'systemName'.rand(0,1000000);
	$testSystemName2 = 'systemName'.rand(0,1000000);
	$testSystemName3 = 'systemName'.rand(0,1000000);
	$scheduleResource1 = createScheduleResource($client, "testResource1" , $testSystemName1);
	$scheduleResource2 = createScheduleResource($client, "testResource2" , $testSystemName2, $scheduleResource1->id);

	if (is_null($scheduleResource2) ||is_null($scheduleResource2->id))
		$failCount += fail(__FUNCTION__ . " ScheduleResource creation failed for parent id $scheduleResource1->id");

	info("Testing Schedule resource creation with Invalid parentId");
	try
	{
		$scheduleResource3 = createScheduleResource($client, "testResource3", $testSystemName3, -1232311);
	}
	catch (KalturaException $exception) {
		if($exception->getCode() == 'RESOURCE_PARENT_ID_NOT_FOUND')
			success("Successful Error - expected to failed creating scheduleResoucre with invalid parentId");
		else
			$failCount += (fail(__FUNCTION__.$exception->getCode()));
	}

	if($failCount )
		return  fail(__FUNCTION__." Schedule Resource creation with parent id Failed.");

	return success(__FUNCTION__);
}

function createScheduleResource($client, $name , $systemName = null, $parentId = null)
{
	info("Creating scheduleResource");
	$scheduleResource = new KalturaCameraScheduleResource();
	$scheduleResource->name = $name;
	if ($systemName != null)
	{
		$scheduleResource->systemName = $systemName;
	}
	if ($parentId != null)
	{
		$scheduleResource->parentId = $parentId;
	}
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleResource->add($scheduleResource);
	info("Created scheduleResource id =" . $result->id);

	return $result;
}

function buildScheduleEvent($start, $end)
{
	$scheduleEvent = new KalturaLiveStreamScheduleEvent();
	$scheduleEvent->summary = 'testScheduleEvent';
	$scheduleEvent->startDate = $start;
	$scheduleEvent->endDate = $end;
	$scheduleEvent->recurrenceType = KalturaScheduleEventRecurrenceType::NONE;
	return $scheduleEvent;
}

function createScheduleEvent($client, $scheduleEvent)
{
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->add($scheduleEvent);
	info("Created scheduleEvent id =" . $result->id);
	return $result;
}

function createScheduleEventResource($client, $eventId , $resourceId)
{
	$scheduleEventResource = new KalturaScheduleEventResource();
	$scheduleEventResource->eventId = $eventId;
	$scheduleEventResource->resourceId = $resourceId;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	try {
		$schedulePlugin->scheduleEventResource->add($scheduleEventResource);
		info("Created scheduleEventResource");
		return true;
	} catch (KalturaException $e){
		info("got error: " . $e->getMessage());
		return false;
	}
}

function deleteScheduleEventResource($client, $eventId , $resourceId)
{
	info("Deleting scheduleEventResource");
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$schedulePlugin->scheduleEventResource->delete($eventId, $resourceId);
}

function getConflicts($client, $resourceIds, $scheduleEvent)
{
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->getConflicts($resourceIds, $scheduleEvent);
	return count($result);
}

function TestResourceReservation($dc,$partnerId,$adminSecret)
{
	info("Testing Schedule resource reservation - this test count on reservation time of 5 sec or less");
	$failCount = 0;
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$client1 = startKalturaSession($partnerId,$adminSecret,$dc);
	$client2 = startKalturaSession($partnerId,$adminSecret,$dc);
	$resource1 = createScheduleResource($client, 'testResource1');
	$resource2 = createScheduleResource($client, 'testResource2');
	$scheduleEvent1 = buildScheduleEvent(1584914400,1584914700);
	$scheduleEvent2 = buildScheduleEvent(1584918400,1584918700);

	$cnt = getConflicts($client1,$resource1->id,$scheduleEvent1);
	info("Num of conflicts was $cnt"); // 0 is no conflicts
	$scheduleEventDB = createScheduleEvent($client, $scheduleEvent1);
	$scheduleEventDB1Id = $scheduleEventDB->id;

	$res = createScheduleEventResource($client2, $scheduleEventDB1Id , $resource1->id);
	if ($res)
		$failCount += fail(__FUNCTION__. " client 2 steal resource from first create with $resource1->id");
	$res = createScheduleEventResource($client1, $scheduleEventDB1Id , $resource1->id);
	if (!$res)
		$failCount += fail(__FUNCTION__. " client 1 can not use his reserved resource");
	$res = createScheduleEventResource($client2, $scheduleEventDB1Id , $resource2->id);
	if (!$res)
		$failCount += fail(__FUNCTION__. " client 2 can not create ScheduleEventResource with $resource2->id");

	deleteScheduleEventResource($client, $scheduleEventDB1Id, $resource1->id);
	deleteScheduleEventResource($client, $scheduleEventDB1Id, $resource2->id);
	sleep(6);

	$res = createScheduleEventResource($client2, $scheduleEventDB1Id , $resource1->id);
	if (!$res)
		$failCount += fail(__FUNCTION__. " client 2 can not use resource $resource1->id despite of 6 sec passed");
	deleteScheduleEventResource($client, $scheduleEventDB1Id, $resource1->id);

	//check no reservation if there are conflict
	$cnt = getConflicts($client1,$resource1->id,$scheduleEvent1);
	info("Num of conflicts was $cnt"); // 0 is no conflicts
	$res = createScheduleEventResource($client2, $scheduleEventDB1Id , $resource1->id);
	if (!$res)
		$failCount += fail(__FUNCTION__. "client 2 can not take resource [$resource1->id] despite of conflict");

	//check 2 resources reservation
	$cnt = getConflicts($client1,"$resource1->id,$resource2->id",$scheduleEvent2);
	info("Num of conflicts was $cnt"); // 0 is no conflicts
	$scheduleEventDB = createScheduleEvent($client, $scheduleEvent2);
	$scheduleEventDB2Id = $scheduleEventDB->id;

	$res = createScheduleEventResource($client2, $scheduleEventDB2Id , $resource1->id);
	if ($res)
		$failCount += fail(__FUNCTION__. "client 2 steal first resource [$resource1->id] from first");
	$res = createScheduleEventResource($client2, $scheduleEventDB2Id , $resource2->id);
	if ($res)
		$failCount += fail(__FUNCTION__. "client 2 steal second resource [$resource2->id] from first");
	$res = createScheduleEventResource($client1, $scheduleEventDB2Id , $resource1->id);
	if (!$res)
		$failCount += fail(__FUNCTION__. "client 1 can use his first reserved resource");
	$res = createScheduleEventResource($client1, $scheduleEventDB2Id , $resource2->id);
	if (!$res)
		$failCount += fail(__FUNCTION__. "client 1 can use his second reserved resource");

	deleteScheduleEventResource($client, $scheduleEventDB2Id, $resource1->id);
	deleteScheduleEventResource($client, $scheduleEventDB2Id, $resource2->id);
	sleep(6);

	$res = createScheduleEventResource($client2, $scheduleEventDB2Id, $resource1->id);
	if (!$res)
		$failCount += fail(__FUNCTION__. "client 2 can not use first resource despite of 6 sec passed");
	$res = createScheduleEventResource($client2, $scheduleEventDB2Id , $resource2->id);
	if (!$res)
		$failCount += fail(__FUNCTION__. "client 2 can not use second resource despite of 6 sec passed");

	if($failCount )
		return  fail(__FUNCTION__." Schedule resource reservation at $failCount tests");
	return success(__FUNCTION__);
}

function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret = TestScheduleResourceWithParentId($client);
	$ret += TestResourceReservation($dc,$partnerId,$adminSecret);
	return ($ret);
}

goMain();
