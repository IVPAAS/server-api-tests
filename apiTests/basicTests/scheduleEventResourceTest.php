<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');

function createScheduleEvent($client, $templateEntryId = null)
{
	info("Creating scheduleEvent");
	$scheduleEvent = new KalturaLiveStreamScheduleEvent();
	$scheduleEvent->summary = 'testScheduleEvent';
	$scheduleEvent->startDate = 1584914400000;
	$scheduleEvent->endDate = 1584914700000;
	$scheduleEvent->recurrenceType = KalturaScheduleEventRecurrenceType::NONE;
	if ($templateEntryId != null)
	{
		$scheduleEvent->templateEntryId = $templateEntryId;
	}
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->add($scheduleEvent);
	info("Created scheduleEvent id =" . $result->id);

	return $result;
}


function createScheduleEventRecurring($client, $templateEntryId = null)
{
	info("Creating scheduleEvent");
	$scheduleEvent = new KalturaLiveStreamScheduleEvent();
	$scheduleEvent->summary = 'testScheduleEvent';
	$scheduleEvent->startDate = 1536240588;
	$scheduleEvent->endDate = 1536240588;
	$scheduleEvent->recurrenceType = KalturaScheduleEventRecurrenceType::RECURRING;
	$scheduleEvent->duration = 1800;
	$scheduleEvent->recurrence = new KalturaScheduleEventRecurrence();
	$scheduleEvent->recurrence->name = 'TEST';
	$scheduleEvent->recurrence->frequency = KalturaScheduleEventRecurrenceFrequency::WEEKLY;
	$scheduleEvent->recurrence->until = 1538836188;
	$scheduleEvent->recurrence->byDay = 'SU,MO,FR';
	if ($templateEntryId != null)
	{
		$scheduleEvent->templateEntryId = $templateEntryId;
	}
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->add($scheduleEvent);
	info("Created scheduleEvent id =" . $result->id);

	return $result;
}

function createScheduleResource($client, $name , $systemName = null)
{
	info("Creating scheduleResource");
	$scheduleResource = new KalturaCameraScheduleResource();
	$scheduleResource->name = $name;
	if ($systemName != null)
	{
		$scheduleResource->systemName = $systemName;
	}
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleResource->add($scheduleResource);
	info("Created scheduleResource id =" . $result->id);

	return $result;
}


function createScheduleEventResource($client, $eventId , $resourceId )
{
	info("Creating scheduleEventResource");
	$scheduleEventResource = new KalturaScheduleEventResource();
	$scheduleEventResource->eventId = $eventId;
	$scheduleEventResource->resourceId = $resourceId;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEventResource->add($scheduleEventResource);
	info("Created scheduleEventResource");

	return $result;
}

/***
 * @param $client
 * @return int|string
 * expected result:
 * list with EventIdOrItsParentIdEqual=scheduleEvent1->id : $scheduleEvent1->id
 * list with EventIdOrItsParentIdEqual=scheduleEvent2->id (recurring) : $scheduleEvent2->id and $scheduleEvent3->id
 * list with EventIdOrItsParentIdEqual=scheduleEvent3->id (recurrence from 2): $scheduleEvent4->id
 * list with EventIdOrItsParentIdEqual=scheduleEvent4->id (recurrence from 2): $scheduleEvent2->id and $scheduleEvent3->id
 */
function TestScheduleEventFilterByEventIdOrItsParentIdEqual($client)
{
	$failCount = 0;

	$scheduleEvent1 = createScheduleEvent($client, null );
	while (isScheduleEventUploaded($client, $scheduleEvent1->id) != true)
	{
		sleep(1);
		print (".");
	}

	$scheduleEvent2 = createScheduleEventRecurring($client, null );
	while (isScheduleEventUploaded($client, $scheduleEvent2->id) != true)
	{
		sleep(1);
		print (".");
	}

	$filter = new KalturaEntryScheduleEventFilter();
	$filter->parentIdEqual = $scheduleEvent2->id;
	$filter->recurrenceTypeEqual = KalturaScheduleEventRecurrenceType::RECURRENCE;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, null);

	info("Total list count: $result->totalCount");
	$scheduleEvent3 = null;
	$scheduleEvent4 = null;
	$retries = 5;
	while ($result->totalCount == 0 && $retries > 0){
		print ("Recurrences are not ready - waiting before retry...\n");
		sleep(5);
		$retries--;
		$result = $schedulePlugin->scheduleEvent->listAction($filter, null);
		info("Total list count: $result->totalCount");
	}

	if ($result->totalCount != 0)
	{
		$scheduleEvent3 = $result->objects[1];
		$scheduleEvent4 = $result->objects[2];
	} else
	{
		$failCount += fail(__FUNCTION__ . " Failed to create recurrences for recurring event: " .$scheduleEvent2->id );
	}

	$scheduleResource1 = createScheduleResource($client, "resource1" , $systemName = null);
	$scheduleResource2 = createScheduleResource($client, "resource2" , $systemName = null);
	$scheduleResource3 = createScheduleResource($client, "resource3" , $systemName = null);
	$scheduleResource4 = createScheduleResource($client, "resource4" , $systemName = null);

	$scheduleEventResource1 = createScheduleEventResource($client, $scheduleEvent1->id , $scheduleResource1->id );
	$scheduleEventResource2 = createScheduleEventResource($client, $scheduleEvent2->id , $scheduleResource2->id );
	$scheduleEventResource3 = createScheduleEventResource($client, $scheduleEvent2->id , $scheduleResource3->id );
	$scheduleEventResource4 = createScheduleEventResource($client, $scheduleEvent3->id , $scheduleResource4->id );

	info("Testing list with filter: EventIdOrItsParentIdEqual with id: $scheduleEvent1->id");
	$filter = new KalturaScheduleEventResourceFilter();
	$filter->eventIdOrItsParentIdEqual = $scheduleEvent1->id;
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEventResource->listAction($filter, $pager);
	info("Total list count: $result->totalCount");
	if ($result->totalCount != 1)
	{
		$failCount += fail(__FUNCTION__ . " scheduleEventResource list result failed. expected <1> but resulted in <$result->totalCount> ");
	} else
	{
		success("Successful list count");
	}

	info("Testing list with filter: EventIdOrItsParentIdEqual with id: $scheduleEvent2->id");
	$filter = new KalturaScheduleEventResourceFilter();
	$filter->eventIdOrItsParentIdEqual = $scheduleEvent2->id;
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEventResource->listAction($filter, $pager);
	info("Total list count: $result->totalCount");
	if ($result->totalCount != 2)
	{
		$failCount += fail(__FUNCTION__ . " scheduleEventResource list result failed. expected <2> but resulted in <$result->totalCount> ");
	} else
	{
		success("Successful list count");
	}

	info("Testing list with filter: EventIdOrItsParentIdEqual with id: $scheduleEvent3->id");
	$filter = new KalturaScheduleEventResourceFilter();
	$filter->eventIdOrItsParentIdEqual = $scheduleEvent3->id;
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEventResource->listAction($filter, $pager);
	info("Total list count: $result->totalCount");
	if ($result->totalCount != 1)
	{
		$failCount += fail(__FUNCTION__ . " scheduleEventResource list result failed. expected <1> but resulted in <$result->totalCount> ");
	} else
	{
		success("Successful list count");
	}

	info("Testing list with filter: EventIdOrItsParentIdEqual with id: $scheduleEvent4->id");
	$filter = new KalturaScheduleEventResourceFilter();
	$filter->eventIdOrItsParentIdEqual = $scheduleEvent4->id;
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEventResource->listAction($filter, $pager);
	info("Total list count: $result->totalCount");
	if ($result->totalCount != 2)
	{
		$failCount += fail(__FUNCTION__ . " scheduleEventResource list result failed. expected <2> but resulted in <$result->totalCount> ");
	} else
	{
		success("Successful list count");
	}

	if ($failCount)
		return fail(__FUNCTION__ . " Schedule Events list with filters failed - count doesn't match");

	return success(__FUNCTION__);
}

function isScheduleEventUploaded($client,$id)
{
	if ($id != null)
	{
		try
		{
			$result = $client->scheduleEvent->get($id, null);
			if ($result)
				return true;
		} catch (Exception $e)
		{
			return true;
		}
	}
	return false;
}

function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret = TestScheduleEventFilterByEventIdOrItsParentIdEqual($client);
	return ($ret);
}

goMain();
