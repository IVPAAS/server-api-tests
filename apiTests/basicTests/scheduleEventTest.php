<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__).'/../testsHelpers/apiTestHelper.php');


function createCategory($client,$categoryName, $parentId = null, $inheritanceType = KalturaInheritanceType::INHERIT)
{
	$category = new KalturaCategory();
	$category->name = $categoryName;
	$category->appearInList = KalturaAppearInListType::CATEGORY_MEMBERS_ONLY;
	$category->privacy = KalturaPrivacyType::MEMBERS_ONLY;
	$category->inheritanceType = KalturaInheritanceType::MANUAL;
	//   $category->defaultPermissionLevel = KalturaCategoryUserPermissionLevel::MEMBER;
	$category->contributionPolicy = KalturaContributionPolicyType::MEMBERS_WITH_CONTRIBUTION_PERMISSION;
	$category->privacyContext = 'PrivacyContext';
	if ( $parentId )
	{
		$category->inheritanceType = $inheritanceType;
		$category->parentId = $parentId;
	}

	$result = $client->category->add($category);
	info("category $result->id was added, parent: " . $parentId);

	return $result;
}

function TestScheduleEventFilterByTemplateEntryCategoriesId($client)
{
	//create category1
	$categoryName1 = 'cat'.rand(0,1000000);
	$category1 = createCategory($client, $categoryName1);

	//create category2
	$categoryName2 = 'cat'.rand(0,1000000);
	$category2 = createCategory($client, $categoryName2);

	//create template entry1
	$MediaEntry1 = helper_createEntryAndUploaDmp4Content($client, 'scheduleEventTest');
	info("Wait for entry to be ready id =".$MediaEntry1->id);
	while(isEntryReady($client,$MediaEntry1->id)!=true)
	{
		sleep(1);
		print (".");
	}

	//create template entry2
	$MediaEntry2 = helper_createEntryAndUploaDmp4Content($client, 'scheduleEventTest');
	info("Wait for entry to be ready id =".$MediaEntry2->id);
	while(isEntryReady($client,$MediaEntry2->id)!=true)
	{
		sleep(1);
		print (".");
	}

	addCategoryEntry($client, $category1->id, $MediaEntry1->id);
	addCategoryEntry($client, $category2->id, $MediaEntry1->id);
	addCategoryEntry($client, $category2->id, $MediaEntry2->id);

	$scheduleEvent1 = createScheduleEvent($client, $MediaEntry1->id);
	while (isScheduleEventUploaded($client, $scheduleEvent1->id) != true)
	{
		sleep(1);
		print (".");
	}

	$scheduleEvent2 = createScheduleEvent($client, $MediaEntry2->id);
	while (isScheduleEventUploaded($client, $scheduleEvent2->id) != true)
	{
		sleep(1);
		print (".");
	}
	$failCount = 0;

	info("Testing list with filter: templateEntryCategoriesIdsLike with id: $category1->id");
	$filter = new KalturaScheduleEventFilter();
	$filter->templateEntryCategoriesIdsLike = $category1->id;
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);
	info("Total list count: $result->totalCount");
	if ($result->totalCount != 1)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <1> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: templateEntryCategoriesIdsLike with InvalidID");
	$filter = new KalturaScheduleEventFilter();
	$filter->templateEntryCategoriesIdsLike = "InvalidID";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 0)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <0> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}


	info("Testing list with filter: templateEntryCategoriesIdsMultiLikeAnd with ids $category1->id,$category2->id");
	$filter = new KalturaScheduleEventFilter();
	$filter->templateEntryCategoriesIdsMultiLikeAnd = "$category1->id,$category2->id";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 1)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <1> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: templateEntryCategoriesIdsMultiLikeAnd with ids $category1->id,InValidId");
	$filter = new KalturaScheduleEventFilter();
	$filter->templateEntryCategoriesIdsMultiLikeAnd = "$category1->id,InValidId";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 0)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <0> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: templateEntryCategoriesIdsMultiLikeAnd with ids $category2->id");
	$filter = new KalturaScheduleEventFilter();
	$filter->templateEntryCategoriesIdsMultiLikeAnd = "$category2->id";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 2)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <2> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: templateEntryCategoriesIdsMultiLikeOr with ids $category1->id,$category2->id");
	$filter = new KalturaScheduleEventFilter();
	$filter->templateEntryCategoriesIdsMultiLikeOr = "$category1->id,$category2->id";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 2)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <2> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: templateEntryCategoriesIdsMultiLikeOr with ids $category1->id,InValidId");
	$filter = new KalturaScheduleEventFilter();
	$filter->templateEntryCategoriesIdsMultiLikeOr = "$category1->id,InValidId";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 1)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <2> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: templateEntryCategoriesIdsMultiLikeOr with InValidId");
	$filter = new KalturaScheduleEventFilter();
	$filter->templateEntryCategoriesIdsMultiLikeOr = "InValidId";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 0)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <0> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	if($failCount )
		return  fail(__FUNCTION__." Schedule Events list with filters failed - count doesn't match");

	return success(__FUNCTION__);
}

function TestScheduleEventFilterByResourceSystemName($client)
{
	$scheduleEvent1 = createScheduleEvent($client);
	while (isScheduleEventUploaded($client, $scheduleEvent1->id) != true)
	{
		sleep(1);
		print (".");
	}

	$scheduleEvent2 = createScheduleEvent($client);
	while (isScheduleEventUploaded($client, $scheduleEvent2->id) != true)
	{
		sleep(1);
		print (".");
	}

	$testSystemName1 = 'systemName'.rand(0,1000000);
	$testSystemName2 = 'systemName'.rand(0,1000000);
	$scheduleResource1 = createScheduleResource($client, "testResource1" , $testSystemName1);
	$scheduleResource2 = createScheduleResource($client, "testResource2" , $testSystemName2);

	createScheduleEventResource($client, $scheduleEvent1->id , $scheduleResource1->id );
	createScheduleEventResource($client, $scheduleEvent2->id , $scheduleResource1->id );
	createScheduleEventResource($client, $scheduleEvent2->id , $scheduleResource2->id );

	$failCount = 0;

	info("Testing list with filter: systemNamesLike with system name: $scheduleResource1->systemName");
	$filter = new KalturaScheduleEventFilter();
	$filter->resourceSystemNamesLike = $scheduleResource1->systemName;
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);
	info("Total list count: $result->totalCount");
	if ($result->totalCount != 2)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <1> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: systemNamesLike with system name: InvalidSystemName");
	$filter = new KalturaScheduleEventFilter();
	$filter->resourceSystemNamesLike = "InvalidSystemName";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 0)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <0> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}


	info("Testing list with filter: systemNamesMultiLikeAnd with system names: $scheduleResource1->systemName, $scheduleResource2->systemName");
	$filter = new KalturaScheduleEventFilter();
	$filter->resourceSystemNamesMultiLikeAnd = "$scheduleResource1->systemName,$scheduleResource2->systemName";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 1)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <1> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: systemNamesMultiLikeAnd with system names: $scheduleResource1->systemName, InvalidSystemName");
	$filter = new KalturaScheduleEventFilter();
	$filter->resourceSystemNamesMultiLikeAnd = "$scheduleResource1->systemName,InvalidSystemName";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 0)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <0> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: systemNamesMultiLikeAnd with system name: $scheduleResource2->systemName");
	$filter = new KalturaScheduleEventFilter();
	$filter->resourceSystemNamesMultiLikeAnd = "$scheduleResource2->systemName";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 1)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <2> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: systemNamesMultiLikeOr with system names: $scheduleResource1->systemName,$scheduleResource2->systemName");
	$filter = new KalturaScheduleEventFilter();
	$filter->resourceSystemNamesMultiLikeOr = "$scheduleResource1->systemName,$scheduleResource2->systemName";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 2)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <2> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: systemNamesMultiLikeOr with system names: $scheduleResource1->systemName,InvalidSystemName");
	$filter = new KalturaScheduleEventFilter();
	$filter->resourceSystemNamesMultiLikeOr = "$scheduleResource1->systemName,InvalidSystemName";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 2)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <2> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	info("Testing list with filter: systemNamesMultiLikeOr with system name: InvalidSystemName");
	$filter = new KalturaScheduleEventFilter();
	$filter->resourceSystemNamesMultiLikeOr = "InvalidSystemName";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 0)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <0> but resulted in <$result->totalCount> ");
	} else {
		success("Successful list count");
	}

	if($failCount )
		return  fail(__FUNCTION__." Schedule Events list with filters failed - count doesn't match");

	return success(__FUNCTION__);
}


function TestScheduleChangeRecurringEventToSingleEvent($client)
{
	info("Testing update event from recurring type to single event");
	$failCount = 0;
	$scheduleEvent = createScheduleEventRecurring($client, null );
	while (isScheduleEventUploaded($client, $scheduleEvent->id) != true)
	{
		sleep(1);
		print (".");
	}

	$filter = new KalturaEntryScheduleEventFilter();
	$filter->parentIdEqual = $scheduleEvent->id;
	$filter->recurrenceTypeEqual = KalturaScheduleEventRecurrenceType::RECURRENCE;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, null);

	info("Total list count: $result->totalCount");
	$retries = 5;
	while ($result->totalCount == 0 && $retries > 0){
		print ("Recurrences are not ready - waiting before retry...\n");
		sleep(5);
		$retries--;
		$result = $schedulePlugin->scheduleEvent->listAction($filter, null);
		info("Total list count: $result->totalCount");
	}

	if ($result->totalCount == 0)
	{
		$failCount += fail(__FUNCTION__ . " Failed to create recurrences for recurring event: " .$scheduleEvent->id );
	}

	else
	{
		$scheduleEvent->recurrenceType = KalturaScheduleEventRecurrenceType::NONE;
		$scheduleEvent->duration = 3000;
		$scheduleEvent->endDate = null;
		$scheduleEvent->createdAt = null;
		$scheduleEvent->updatedAt = null;

		$schedulePlugin = KalturaScheduleClientPlugin::get($client);
		$updatedScheduleEvent = $schedulePlugin->scheduleEvent->update($scheduleEvent->id, $scheduleEvent);

		info("Validating update from recurring type to single event");
		if ($updatedScheduleEvent->recurrenceType != KalturaScheduleEventRecurrenceType::NONE)
			$failCount += fail(__FUNCTION__ . " ScheduleEvent update failed expecting recurrence type NONE but got $updatedScheduleEvent->recurrenceType");

		if ($updatedScheduleEvent->sequence != 1)
			$failCount += fail(__FUNCTION__ . " ScheduleEvent update failed expecting sequence number 1 but got $updatedScheduleEvent->sequence");

		$val = $updatedScheduleEvent->startDate + $updatedScheduleEvent->duration;
		if ($updatedScheduleEvent->endDate != $val)
			$failCount += fail(__FUNCTION__ . " ScheduleEvent update failed expecting endDate <$updatedScheduleEvent->endDate> to match startDate + duration <$val>");

		if (!is_null($updatedScheduleEvent->recurrence))
			$failCount += fail(__FUNCTION__ . " ScheduleEvent update failed expecting no recurrence object on the event but got object exists");

		$filter = new KalturaScheduleEventFilter();
		$filter->parentIdEqual = $updatedScheduleEvent->id;
		$filter->statusEqual = KalturaScheduleEventStatus::ACTIVE;
		$result = $schedulePlugin->scheduleEvent->listAction($filter, null);

		info("Total Active recurrences count: $result->totalCount");
		if ($result->totalCount != 0)
			$failCount += fail(__FUNCTION__ . " ScheduleEvent should have deleted all recurrences but got <$result->totalCount> active recurrences");

		$filter->statusEqual = KalturaScheduleEventStatus::DELETED;
		$result = $schedulePlugin->scheduleEvent->listAction($filter, null);
		info("Total Deleted recurrences count: $result->totalCount");
		if ($result->totalCount != 0)
			$failCount += fail(__FUNCTION__ . " ScheduleEvent should have deleted all recurrences but got <$result->totalCount> deleted recurrences");

	}

	if ($failCount)
		return fail(__FUNCTION__ . " schedule event update from recurring event to single event count Failed!");

	return success(__FUNCTION__." Successful schedule event update from recurring event to single event count");
}


function TestScheduleChangeSingleEventToRecurringEvent($client)
{
	info("Testing update event from single event type to recurring event");
	$failCount = 0;

	$scheduleEvent1 = createScheduleEvent($client);
	while (isScheduleEventUploaded($client, $scheduleEvent1->id) != true)
	{
		sleep(1);
		print (".");
	}

	$scheduleEvent = createScheduleEventRecurring($client, null);
	while (isScheduleEventUploaded($client, $scheduleEvent->id) != true)
	{
		sleep(1);
		print (".");
	}

	$filter = new KalturaEntryScheduleEventFilter();
	$filter->parentIdEqual = $scheduleEvent->id;
	$filter->recurrenceTypeEqual = KalturaScheduleEventRecurrenceType::RECURRENCE;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$recurrences = $schedulePlugin->scheduleEvent->listAction($filter, null);

	info("Total list count: $recurrences->totalCount");
	$retries = 5;
	while ($recurrences->totalCount == 0 && $retries > 0)
	{
		print ("Recurrences are not ready - waiting before retry...\n");
		sleep(5);
		$retries--;
		$result = $schedulePlugin->scheduleEvent->listAction($filter, null);
		info("Total list count: $result->totalCount");
	}

	if ($recurrences->totalCount == 0)
		$failCount += fail(__FUNCTION__ . " Failed to create recurrences for recurring event: " . $scheduleEvent->id);

	$scheduleEvent1->recurrenceType = KalturaScheduleEventRecurrenceType::RECURRING;
	$scheduleEvent1->duration = $scheduleEvent->duration;
	$scheduleEvent1->startDate = $scheduleEvent->startDate;
	$scheduleEvent1->endDate = $scheduleEvent->endDate;
	$scheduleEvent1->createdAt = null;
	$scheduleEvent1->updatedAt = null;
	$scheduleEvent1->recurrence = $scheduleEvent->recurrence;

	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$updatedScheduleEvent = $schedulePlugin->scheduleEvent->update($scheduleEvent1->id, $scheduleEvent1);

	info("Validating update from single type to recurring event");
	if ($updatedScheduleEvent->recurrenceType != $scheduleEvent->recurrenceType)
		$failCount += fail(__FUNCTION__ . " ScheduleEvent update failed expecting recurrence type RECURRING but got $updatedScheduleEvent->recurrenceType");

	if ($updatedScheduleEvent->sequence != $scheduleEvent->sequence)
		$failCount += fail(__FUNCTION__ . " ScheduleEvent update failed expecting sequence number of recurrences but got $updatedScheduleEvent->sequence $scheduleEvent->sequence");

	if ($updatedScheduleEvent->duration != $scheduleEvent->duration)
		$failCount += fail(__FUNCTION__ . " ScheduleEvent update failed expecting duration <$scheduleEvent->duration> but got <$updatedScheduleEvent->duration>");

	if ($updatedScheduleEvent->startDate != $scheduleEvent->startDate)
		$failCount += fail(__FUNCTION__ . " ScheduleEvent update failed expecting startDate <$scheduleEvent->startDate> but got <$updatedScheduleEvent->startDate>");

	if ($updatedScheduleEvent->endDate != $scheduleEvent->endDate)
		$failCount += fail(__FUNCTION__ . " ScheduleEvent update failed expecting endDate <$scheduleEvent->endDate> but got <$updatedScheduleEvent->endDate>");

	if (is_null($updatedScheduleEvent->recurrence))
		$failCount += fail(__FUNCTION__ . " ScheduleEvent update failed expecting to create recurrence object on the event but got but got no recurrence");

	$filter = new KalturaScheduleEventFilter();
	$filter->parentIdEqual = $updatedScheduleEvent->id;
	$filter->statusEqual = KalturaScheduleEventStatus::ACTIVE;
	$result = $schedulePlugin->scheduleEvent->listAction($filter, null);

	info("Total Active recurrences count: $result->totalCount");
	if ($result->totalCount == 0)
		$failCount += fail(__FUNCTION__ . " ScheduleEvent should have created recurrences but got <$result->totalCount> active recurrences");

	$filter->statusEqual = KalturaScheduleEventStatus::DELETED;
	$result = $schedulePlugin->scheduleEvent->listAction($filter, null);
	info("Total Deleted recurrences count: $result->totalCount");
	if ($result->totalCount != 0)
		$failCount += fail(__FUNCTION__ . " ScheduleEvent should have no deleted recurrences but got <$result->totalCount> deleted recurrences");

	if ($failCount)
		return fail(__FUNCTION__ . " schedule event update from single event to recurring event count Failed!");

	return success(__FUNCTION__ . " Successful schedule event update from single event to recurring event count");
}

function createScheduleEventRecurring($client, $templateEntryId = null)
{
	info("Creating scheduleEvent");
	$scheduleEvent = new KalturaLiveStreamScheduleEvent();
	$scheduleEvent->summary = 'testScheduleEvent';
	$scheduleEvent->startDate = 1536326988;
	$scheduleEvent->endDate = 1536326998;
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


function TestScheduleEventFilterByTemplateEntryId($client)
{
	//create template entry1
	$MediaEntry1 = helper_createEntryAndUploaDmp4Content($client, 'scheduleEventTest');
	info("Wait for entry to be ready id =" . $MediaEntry1->id);
	while (isEntryReady($client, $MediaEntry1->id) != true)
	{
		sleep(1);
		print (".");
	}

	//create template entry2
	$MediaEntry2 = helper_createEntryAndUploaDmp4Content($client, 'scheduleEventTest');
	info("Wait for entry to be ready id =" . $MediaEntry2->id);
	while (isEntryReady($client, $MediaEntry2->id) != true)
	{
		sleep(1);
		print (".");
	}

	$scheduleEvent1 = createScheduleEvent($client, $MediaEntry1->id);
	while (isScheduleEventUploaded($client, $scheduleEvent1->id) != true)
	{
		sleep(1);
		print (".");
	}

	$scheduleEvent2 = createScheduleEvent($client, $MediaEntry2->id);
	while (isScheduleEventUploaded($client, $scheduleEvent2->id) != true)
	{
		sleep(1);
		print (".");
	}
	$failCount = 0;

	info("Testing list with filter: templateEntryIdLike with id: $MediaEntry1->id");
	$filter = new KalturaEntryScheduleEventFilter();
	$filter->templateEntryIdEqual = $MediaEntry1->id;
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);
	info("Total list count: $result->totalCount");
	if ($result->totalCount != 1)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <1> but resulted in <$result->totalCount> ");
	} else
	{
		success("Successful list count");
	}

	info("Testing list with filter: templateEntryIdLike with InvalidID");
	$filter = new KalturaEntryScheduleEventFilter();
	$filter->templateEntryIdEqual = "InvalidID";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 0)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <0> but resulted in <$result->totalCount> ");
	} else
	{
		success("Successful list count");
	}

	info("Testing list with filter: templateEntryIdLike with ids $MediaEntry2->id");
	$filter = new KalturaEntryScheduleEventFilter();
	$filter->templateEntryIdEqual = "$MediaEntry2->id";
	$pager = null;
	$schedulePlugin = KalturaScheduleClientPlugin::get($client);
	$result = $schedulePlugin->scheduleEvent->listAction($filter, $pager);

	info("Total list count: $result->totalCount");
	if ($result->totalCount != 1)
	{
		$failCount += fail(__FUNCTION__ . " ScheduleEvent list result failed. expected <1> but resulted in <$result->totalCount> ");
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
	$ret = TestScheduleEventFilterByTemplateEntryId($client);
	$ret += TestScheduleEventFilterByTemplateEntryCategoriesId($client);
	$ret += TestScheduleEventFilterByResourceSystemName($client);
	$ret += TestScheduleChangeRecurringEventToSingleEvent($client);
	$ret += TestScheduleChangeSingleEventToRecurringEvent($client);
	return ($ret);
}

goMain();