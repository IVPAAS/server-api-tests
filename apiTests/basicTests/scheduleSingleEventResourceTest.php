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

function createEventsResourcesResponseProfile()
{
	// filter for getting resources
	$resourceFilter = new  KalturaScheduleResourceFilter();

	$resourceMapping = new KalturaResponseProfileMapping();
	$resourceMapping->filterProperty = 'idEqual';
	$resourceMapping->parentProperty = 'resourceId';

	// nested-nested profile - get resources
	$resourceResponseProfile = new KalturaDetachedResponseProfile();
	$resourceResponseProfile->name = 'get the resource id name and systemName';
	$resourceResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
	$resourceResponseProfile->fields = 'id,name,systemName';
	$resourceResponseProfile->filter = $resourceFilter;
	$resourceResponseProfile->mappings = array($resourceMapping);

	// filter for getting event resources
	$eventResourceFilter = new KalturaScheduleEventResourceFilter();

	$eventResourceMapping = new KalturaResponseProfileMapping();
	$eventResourceMapping->filterProperty = 'eventIdOrItsParentIdEqual';
	$eventResourceMapping->parentProperty = 'id';

	// nested profile - get event-resources
	$eventResourceResponseProfile = new KalturaDetachedResponseProfile();
	$eventResourceResponseProfile->name = 'get event ids and resource ids';
	$eventResourceResponseProfile->type = KalturaResponseProfileType::INCLUDE_FIELDS;
	$eventResourceResponseProfile->fields = 'resourceId,eventId';
	$eventResourceResponseProfile->filter = $eventResourceFilter;
	$eventResourceResponseProfile->mappings = array($eventResourceMapping);
	$eventResourceResponseProfile->relatedProfiles = array(
		$resourceResponseProfile
	);

	// main profile - define the fields we want on events list and the nested profiles
	$responseProfile = new KalturaResponseProfile();
	$responseProfile->name = 'main profile';
	$responseProfile->type = KalturaResponseProfileType::EXCLUDE_FIELDS;
	// $responseProfile->fields = '';  // get all the fields
	$responseProfile->relatedProfiles = array(
		$eventResourceResponseProfile
	);

	return $responseProfile;
}

/***
 * @param $client
 * @return int|string
 * expected result:
 * list with EventIdOrItsParentIdEqual=scheduleEvent1->id : 0
 * list with EventIdOrItsParentIdEqual=scheduleEvent2->id : $scheduleEventResource1 and $scheduleEventResource2
 * list with idEqual=scheduleEvent2->id + responseProfilr: scheduleEvent2 + relatedObjects $scheduleResource1 $scheduleResource2
 *
 */
function TestSingleScheduleEventFilterByEventIdOrItsParentIdEqual($client)
{
	$failCount = 0;

	$scheduleEvent1 = createScheduleEvent($client, null );
	while (isScheduleEventUploaded($client, $scheduleEvent1->id) != true)
	{
		sleep(1);
		print (".");
	}

	$scheduleEvent2 = createScheduleEvent($client, null );
	while (isScheduleEventUploaded($client, $scheduleEvent2->id) != true)
	{
		sleep(1);
		print (".");
	}

	$scheduleResource1 = createScheduleResource($client, "resource1" , $systemName = null);
	$scheduleResource2 = createScheduleResource($client, "resource2" , $systemName = null);
	$scheduleEventResource1 = createScheduleEventResource($client, $scheduleEvent2->id , $scheduleResource1->id );
	$scheduleEventResource2 = createScheduleEventResource($client, $scheduleEvent2->id , $scheduleResource2->id );


	info("Testing list with filter: EventIdOrItsParentIdEqual with id: $scheduleEvent1->id");
	$filter = new KalturaScheduleEventResourceFilter();
	$filter->eventIdOrItsParentIdEqual = $scheduleEvent1->id;
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEventResource->listAction($filter, $pager);
	info("Total list count: $result->totalCount");
	if ($result->totalCount != 0)
	{
		$failCount = fail(__FUNCTION__ . " scheduleEventResource list result failed. expected <0> but the count is <$result->totalCount> ");
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
		$failCount += fail(__FUNCTION__ . " scheduleEventResource list result failed. expected <2> but the count is <$result->totalCount> ");
	} else
	{
		success("Successful list count");
	}

	info("Testing list with filter: EventId with id: $scheduleEvent2->id and response profile");
	$filter = new KalturaScheduleEventFilter();
	$filter->idEqual = $scheduleEvent2->id;
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$responseProfile = createEventsResourcesResponseProfile();
	$responseProfile = $client->responseProfile->add($responseProfile);

	$nestedResponseProfile = new KalturaResponseProfileHolder();
	$nestedResponseProfile->id = $responseProfile->id;
	$client->setResponseProfile($nestedResponseProfile);

	$result = $schedulePlugin->scheduleEventResource->listAction($filter, $pager);
	info("Total list count: $result->totalCount");
	if ($result->totalCount != 1)
	{
		$failCount += fail(__FUNCTION__ . " scheduleEventResource list result failed. expected <1> but resulted in <$result->totalCount> ");
	}
	else
	{
		if($result->relatedObjects[0]->totalCount != 2)
		{
			$failCount += fail(__FUNCTION__ . " scheduleEventResource relatedObjects list result failed. expected <2> but the count is <$result->totalCount> ");
		}
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

function main($dc,$partnerId,$adminSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret = TestSingleScheduleEventFilterByEventIdOrItsParentIdEqual($client);
	return ($ret);
}

goMain();
