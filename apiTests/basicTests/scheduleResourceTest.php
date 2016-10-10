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

function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$ret = TestScheduleResourceWithParentId($client);
	return ($ret);
}

goMain();