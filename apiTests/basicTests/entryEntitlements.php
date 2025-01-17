<?php
require_once('/opt/kaltura/web/content/clientlibs/testsClient/KalturaClient.php');
require_once(dirname(__FILE__) . '/../testsHelpers/apiTestHelper.php');
require_once(dirname(__FILE__) . '/../testsHelpers/EntryTestHelper.php');

function testGetRestrictedEntry($client, $restrictedEntry)
{
	info('start ' .  __FUNCTION__);
	try
	{
		$result = $client->baseEntry->get($restrictedEntry->id, null);
		return (fail(__FUNCTION__."Restricted Entry should not have been found but it was"));
	}
	catch(KalturaException $e)
	{
		if($e->getCode() == 'ENTRY_ID_NOT_FOUND')
			return (success(__FUNCTION__));
		else
			return (fail(__FUNCTION__.$e->getCode()));
	}
}

function testListRestrictedEntryIdEqual($client, $restrictedEntry)
{
	info('start ' .  __FUNCTION__);
	$filter = new KalturaBaseEntryFilter();
	$filter->idEqual = $restrictedEntry->id;

	$result = $client->baseEntry->listAction($filter, null);
	foreach($result->objects as $entry)
		if($entry->id == $restrictedEntry->id)
			return (fail(__FUNCTION__."Restricted Entry should not have been found but it was"));

	return (success(__FUNCTION__));
}

function testListRestrictedEntryIdIn($client, $restrictedEntry)
{
	info('start ' .  __FUNCTION__);
	$filter = new KalturaBaseEntryFilter();
	$filter->idIn = $restrictedEntry->id;

	$result = $client->baseEntry->listAction($filter, null);
	foreach($result->objects as $entry)
		if($entry->id == $restrictedEntry->id)
			return (fail(__FUNCTION__."Restricted Entry should not have been found but it was"));

	return (success(__FUNCTION__));
}


function testLetRestrictedEntryByRefernceId($client, $restrictedRefEntry)
{
	info('start ' .  __FUNCTION__);
	$result = $client->baseEntry->listByReferenceId($restrictedRefEntry->id, null);

	foreach($result->objects as $entry)
		if($entry->id == $restrictedRefEntry->id)
			return (fail(__FUNCTION__."Restricted Entry should not have been found but it was"));

	return (success(__FUNCTION__));

}

function main($dc,$partnerId,$adminSecret,$userSecret)
{
	$client = startKalturaSession($partnerId,$adminSecret,$dc);
	$entry1 = createMediaEntry($client);
	$entry2 = createMediaEntry($client, $entry1);

	$weakClient = startKalturaSession($partnerId,$adminSecret,$dc,KalturaSessionType::USER, null, 'privacycontext:MediaSpace,enableentitlement');
	$ret = testGetRestrictedEntry($weakClient, $entry2);
	$ret += testListRestrictedEntryIdEqual($weakClient, $entry2);
	$ret += testListRestrictedEntryIdIn($weakClient, $entry2);
	$ret += testLetRestrictedEntryByRefernceId($weakClient, $entry1);
	return $ret;
}

goMain();
